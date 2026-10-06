<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\ImportAiLog;
use App\Reference;
use App\User;
use App\MyNamespace;
use App\MyNamespaceUsage;

use Illuminate\Support\Facades\Log;
use App\Mail\Email;
use Illuminate\Support\Facades\Mail;
use App\Http\Services\UsageAiImportService;
use App\Exceptions\AiModelException;
use Illuminate\Http\Client\ConnectionException;

class ProcessAiUsageRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;        // 涉及寫入，不自動重試
    public $timeout = 3700;   // 需大於 Python 呼叫的 Http timeout(3600)

    private $referenceId;
    private $userId;
    private $jobLogId;

    public function __construct($referenceId, $userId, $jobLogId)
    {
        $this->referenceId = $referenceId;
        $this->userId = $userId;
        $this->jobLogId = $jobLogId;
    }

    public function handle()
    {
        $jobLog = ImportAiLog::find($this->jobLogId);
        $reference = Reference::find($this->referenceId);
        $user = User::find($this->userId);

        if (!$jobLog || !$reference || !$user) {
            Log::warning('ProcessAiUsageRequest: missing log/reference/user', [
                'job_log_id' => $this->jobLogId,
                'reference_id' => $this->referenceId,
                'user_id' => $this->userId,
            ]);
            if ($jobLog) {
                $jobLog->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => 'Reference or user not found',
                ]);
            }
            return;
        }

        $filePath = $reference->properties['file'] ?? null;
        $today = date('Y-m-d');

        // 檢查API限制（排隊期間可能已達上限）
        $dailyKey = "gemini_api_calls:{$today}";
        $currentCalls = Redis::get($dailyKey) ?? 0;

        if ($currentCalls >= config('services.gemini.daily_limit', 1000)) {
            $jobLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Daily API limit exceeded',
            ]);
            try {
                $this->notifyUserFailed(
                    $user,
                    $reference,
                    '今日 AI 辨識次數已達上限，請明天再重新送出。',
                    'The daily AI processing limit has been reached. Please submit again tomorrow.'
                );
            } catch (\Throwable $mailError) {
                Log::error('Notify user failed', ['error' => $mailError->getMessage()]);
            }
            return;
        }

        $namespace = null;

        try {
            // 呼叫 Python API
            try {
                $response = Http::timeout(3600)->post(env('TAICOL_AI_API_ROOT') . '/process-usage', [
                    'file_path' => $filePath
                ]);
            } catch (ConnectionException $e) {
                throw AiModelException::connection($e);
            }

            if ($response->successful()) {
                $result = $response->json();

                if ($result['success'] ?? false) {
                    $metadata = $result['metadata'] ?? [];

                    // 更新API計數
                    Redis::incr($dailyKey);
                    Redis::expire($dailyKey, 86400);

                    // 讀取從api讀取後存的json檔
                    $usageJson = json_decode(file_get_contents(public_path('usage_results/' . $result['file_uri'] . '.json')), true);

                    if (is_array($usageJson) && isset($usageJson['scientific_names'])) {
                        foreach ($usageJson['scientific_names'] as $key => &$item) {
                            if (!array_key_exists('index', $item)) {
                                $item['index'] = (int)$key + 1;
                            }
                        }
                        unset($item);
                    }

                    // 取得回傳判斷對應學名id
                    $service = app(UsageAiImportService::class);
                    $processedData = $service->processScientificNames($usageJson);

                    // 判斷是否有學名需要新增
                    $unmatchedCount = $service->countUnmatchedScientificNames($processedData);

                    // 建立namespace
                    $namespace = new MyNamespace();
                    $namespace->title = $reference->title . '-' . $today;
                    $namespace->reference_id = $reference->id;
                    $namespace->type = 0;

                    $user->namespaces()->save($namespace);

                    $jobLog->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'file_uri' => $result['file_uri'],
                        'import_to_id' => $namespace->id,
                        'metadata' => array_merge($jobLog->metadata ?? [], [
                            'tokens_used' => $metadata['tokens_used'] ?? null,
                            'input_tokens' => $metadata['input_tokens'] ?? null,
                            'output_tokens' => $metadata['output_tokens'] ?? null,
                            'unmatched_name_count' => $unmatchedCount
                        ])
                    ]);

                    // 沒有未比對到的話直接匯入usage
                    if ($unmatchedCount == 0) {
                        $importedCount = $service->handle($processedData, $namespace->id);
                    }

                    // 寄信通知使用者（解析已完成，寄信失敗不可讓 Job 變成失敗）
                    try {
                        Mail::to($user->email)->send(new Email(
                            '您在物種學名管理工具匯入的文獻已處理完成，請至「我的名錄」查看。<br>
                            The reference you imported in TaiCOL - Name Tool has been processed. Please check it in "My Checklist."<br>
                            https://nametool.taicol.tw/',
                            'TaiCOL物種學名管理工具 - 文獻匯入通知',
                            $user->name
                        ));
                    } catch (\Throwable $mailError) {
                        Log::error('Notify user completed failed', ['error' => $mailError->getMessage()]);
                    }

                } else {
                    throw new \Exception($result['error'] ?? 'Unknown error from Python service');
                }
            } else {
                if ($aiError = AiModelException::fromResponse($response)) {
                    throw $aiError;
                }
                throw new \Exception("Python API HTTP error: {$response->status()} - {$response->body()}");
            }

        } catch (\Throwable $e) {
            Log::error('Gemini processing failed', [
                'error' => $e->getMessage(),
                'file_path' => $filePath ?? null
            ]);

            // 已建立名錄但匯入失敗（沒有任何學名使用）：刪除空名錄，避免重送時留下多個空名錄
            $removeNamespace = $namespace
                && !MyNamespaceUsage::where('namespace_id', $namespace->id)->exists();
            if ($removeNamespace) {
                $namespace->delete();
            }

            $jobLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'import_to_id' => $removeNamespace ? null : $jobLog->import_to_id,
                'error_message' => $e instanceof AiModelException
                    ? $e->getMessage() . ' | ' . $e->detail
                    : $e->getMessage(),
            ]);

            // 使用者與管理員通知統一在 failed() 處理

            // Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))
            //     ->send(new Email(
            //         '使用者在物種學名管理工具匯入的學名使用有錯誤，錯誤訊息如下：<br>' . nl2br($e->getMessage()),
            //         'TaiCOL物種學名管理工具 - 文獻匯入失敗',
            //         'TaiCOL管理員'
            //     ));

            throw $e;
        }
    }

    /**
     * Job 最終失敗時觸發（handle 拋出例外、逾時、worker 中止皆會進來）
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAiUsageRequest failed', [
            'job_log_id' => $this->jobLogId,
            'reference_id' => $this->referenceId,
            'user_id' => $this->userId,
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]);

        $log = ImportAiLog::find($this->jobLogId);
        $reference = Reference::find($this->referenceId);
        $user = User::find($this->userId);

        // 逾時 / worker 中止時 handle 的 catch 不會執行，這裡補更新
        if ($log && $log->status === 'processing') {
            $log->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => get_class($exception) . ': ' . $exception->getMessage(),
            ]);
        }

        // 通知使用者
        if ($user && $reference) {
            try {
                [$reasonZh, $reasonEn] = $this->userReason($exception);
                $this->notifyUserFailed($user, $reference, $reasonZh, $reasonEn);
            } catch (\Throwable $mailError) {
                Log::error('Notify user failed', ['error' => $mailError->getMessage()]);
            }
        }

        // 通知管理員（寄信失敗只記 log，不再往外拋）
        try {
            Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))
                ->send(new Email(
                    $this->adminDetail($exception, $log, $reference, $user),
                    'TaiCOL物種學名管理工具 - AI 學名使用匯入失敗',
                    'TaiCOL管理員'
                ));
        } catch (\Throwable $mailError) {
            Log::error('Notify admin failed', ['error' => $mailError->getMessage()]);
        }
    }

    /** 給使用者看的原因 */
    /** 給使用者看的原因 [中文, 英文] */
    private function userReason(\Throwable $e): array
    {
        if ($e instanceof AiModelException) {
            return [
                $e->getMessage() . '<br>請稍後重新送出。',
                ($e->messageEn ?: $e->getMessage()) . ' Please submit again later.',
            ];
        }

        if ($this->isTimeout($e)) {
            return [
                '處理時間過長而中止，文件可能過大，請嘗試拆分檔案後重新送出。',
                'Processing was stopped because it took too long. The document may be too large; please split the file and submit again.',
            ];
        }

        return [
            '系統處理時發生錯誤，管理員已收到通知，請稍後再試。',
            'A system error occurred. The administrator has been notified. Please try again later.',
        ];
    }

    private function isTimeout(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Queue\MaxAttemptsExceededException
            || (class_exists(\Illuminate\Queue\TimeoutExceededException::class)
                && $e instanceof \Illuminate\Queue\TimeoutExceededException);
    }

    /** 給管理員看的完整資訊 */
    private function adminDetail(\Throwable $e, $log, $reference, $user): string
    {
        if ($e instanceof AiModelException) {
            $type = 'AI 模型 / AI 服務錯誤';
        } elseif ($this->isTimeout($e)) {
            $type = 'Job 逾時或被中止';
        } else {
            $type = '系統錯誤';
        }

        $rows = [
            '錯誤類型' => $type,
            '使用者' => $user ? "{$user->name} ({$user->email}, id={$user->id})" : "id={$this->userId}",
            '文獻' => $reference ? "{$reference->title} (id={$reference->id})" : "id={$this->referenceId}",
            'PDF' => $reference->properties['file'] ?? '-',
            'Import AI Log' => "id={$this->jobLogId}" . ($log ? "，開始於 {$log->started_at}" : ''),
            '時間' => now()->toDateTimeString(),
            'Exception' => get_class($e),
            '訊息' => $e->getMessage(),
        ];

        if ($e instanceof AiModelException && $e->detail) {
            $rows['AI 原始錯誤'] = $e->detail;
        }

        $rows['位置'] = $e->getFile() . ':' . $e->getLine();

        $html = '<table border="1" cellpadding="4" style="border-collapse:collapse">';
        foreach ($rows as $k => $v) {
            $html .= '<tr><th align="left">' . e($k) . '</th><td>' . nl2br(e(mb_substr((string) $v, 0, 2000))) . '</td></tr>';
        }
        $html .= '</table>';

        // 非 AI 錯誤附上 trace 前幾行，方便追查
        if (!$e instanceof AiModelException) {
            $trace = implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 10));
            $html .= '<p><b>Trace</b></p><pre>' . e($trace) . '</pre>';
        }

        return $html;
    }

    private function notifyUserFailed($user, $reference, string $reasonZh, string $reasonEn): void
    {
        Mail::to($user->email)->send(new Email(
            '您在物種學名管理工具使用 AI 匯入的文獻《' . e($reference->title) . '》學名使用解析失敗。<br><br>'
            . '原因：' . $reasonZh . '<br><br>'
            . '如問題持續發生，請聯繫 TaiCOL 管理員。<br><br>'
            . 'The AI parsing of name usages for your reference "' . e($reference->title) . '" has failed.<br><br>'
            . 'Reason: ' . e($reasonEn) . '<br><br>'
            . 'If the problem persists, please contact the TaiCOL administrator.',
            'TaiCOL物種學名管理工具 - AI 學名使用解析失敗',
            $user->name
        ));
    }
}
