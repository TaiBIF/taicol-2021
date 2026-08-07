<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\ImportLog;
use App\Http\Services\TaxonNameAiImportService;
use App\Mail\Email;

class CreateNamesForNamespaceUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;        // 建名涉及寫入，不自動重試避免重複建名
    public $timeout = 3600;

    private $logId;

    public function __construct($logId)
    {
        $this->logId = $logId;
    }

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        $log = ImportLog::find($this->logId);
        if (!$log) {
            return;
        }

        // fatal error（OOM/timeout）catch 抓不到，用 shutdown 補救
        // 建名失敗一律保留上傳檔，供使用者修正後重傳
        $logId = $this->logId;
        register_shutdown_function(function () use ($logId) {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $log = ImportLog::find($logId);
                if ($log && in_array($log->status, ['pending', 'processing'], true)) {
                    $log->update([
                        'status' => 'failed',
                        'phase' => null,
                        'completed_at' => now(),
                        'error_message' => '新增學名時發生嚴重錯誤（可能記憶體不足），請縮小批次或聯絡管理者',
                    ]);
                }
            }
        });

        $names = $log->context['names_to_create'] ?? [];

        // 背景 Job 無 session，補回發起者身份，讓建名寫的 log 正確歸戶
        if ($log->user_id) {
            \Illuminate\Support\Facades\Auth::setUser(\App\User::find($log->user_id));
        }

        try {
            // handle() 內含 transaction，任一列失敗會 rollback，不會有半套學名
            if (!empty($names)) {
                (new TaxonNameAiImportService(['scientific_names' => $names]))->handle();
            }

            // 建名完成：清暫存、標記已解析，接續原本的 Excel 重新匯入（上傳檔仍保留在磁碟）
            $context = $log->context ?? [];
            unset($context['names_to_create'], $context['unmatched_names']);
            $context['names_resolved'] = true;

            $log->update([
                'status'          => 'pending',
                'phase'           => null,
                'error_message'   => null,
                'error_file_path' => null,
                'processed_rows'  => 0,
                'success_count'   => null,
                'context'         => $context,
            ]);

            ImportNamespaceUsageJob::dispatch($log->id);
        } catch (\Exception $e) {
            Log::error('CreateNamesForNamespaceUsageJob failed', [
                'log_id' => $this->logId,
                'error'  => $e->getMessage(),
                'at'     => $e->getFile() . ':' . $e->getLine(),
                'trace'  => $e->getTraceAsString(),
            ]);

            // transaction 已 rollback，無殘留學名；上傳檔保留供修正後重傳
            $log->update([
                'status'        => 'failed',
                'phase'         => null,
                'completed_at'  => now(),
                'error_message' => $e->getMessage(),
            ]);

            // 使用者資料錯誤（「第 N 筆…」）靜默；其他例外 rethrow → 觸發 failed() 寄信示警
            if (! \Illuminate\Support\Str::startsWith($e->getMessage(), '第 ')) {
                throw $e;
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        // 僅框架層失敗（如 timeout）會走到這；使用者資料錯誤已於 handle() 內處理、不 rethrow
        Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))
            ->send(new Email(
                '名錄 Usage 批次建名任務最終失敗：<br>' . nl2br($e->getMessage()),
                'TaiCOL - 批次建名失敗',
                'TaiCOL管理員'
            ));
    }
}