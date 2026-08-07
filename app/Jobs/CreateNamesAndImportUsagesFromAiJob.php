<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\ImportLog;
use App\ImportAiLog;
use App\User;
use App\Http\Services\TaxonNameAiImportService;
use App\Http\Services\UsageAiImportService;
use App\Mail\Email;

class CreateNamesAndImportUsagesFromAiJob implements ShouldQueue
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
        $logId = $this->logId;
        register_shutdown_function(function () use ($logId) {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $log = ImportLog::find($logId);
                if ($log && in_array($log->status, ['pending', 'processing'], true)) {
                    $log->update([
                        'status'        => 'failed',
                        'phase'         => null,
                        'completed_at'  => now(),
                        'error_message' => '處理時發生嚴重錯誤（可能記憶體不足），請縮小批次或聯絡管理者',
                    ]);
                }
            }
        });

        $context   = $log->context ?? [];
        $namespaceId = $context['namespace_id'] ?? null;
        $aiLogId   = $context['ai_log_id'] ?? null;
        $submitData = $context['submit_data'] ?? [];

        // 背景 Job 無 session，補回發起者身份（TaxonNameAiImportService→LogService 會讀 Auth::id()）
        if ($log->user_id) {
            Auth::setUser(User::find($log->user_id));
        }

        try {
            // 對應的 AI log（收尾要把 status 設回 'added'，index() 靠它判斷是否還導去補學名頁）
            $aiLog = $aiLogId
                ? ImportAiLog::find($aiLogId)
                : ImportAiLog::where('import_to_id', $namespaceId)->first();

            // 要跳過的 original_name
            $skipNames = collect($submitData)
                ->filter(fn($item) => $item['skip'] ?? false)
                ->pluck('original_name')
                ->toArray();

            // 1. 建名：挑出沒有 selected_name 且未 skip 的資料
            $dataWithoutSelectedName = collect($submitData)
                ->filter(fn($item) => empty($item['selected_name']) && empty($item['skip']))
                ->values()
                ->toArray();

            // handle() 內含 transaction，任一列失敗 rollback，不會有半套學名
            $result = ['imported_taxon_names' => []];
            if (!empty($dataWithoutSelectedName)) {
                $result = (new TaxonNameAiImportService([
                    'scientific_names' => $dataWithoutSelectedName,
                ]))->handle();
            }

            // 2. 讀 usage json，補 index
            $fileUri = $aiLog->file_uri;
            $usageJson = json_decode(file_get_contents(public_path('usage_results/' . $fileUri . '.json')), true);

            if (is_array($usageJson) && isset($usageJson['scientific_names'])) {
                foreach ($usageJson['scientific_names'] as $key => &$item) {
                    if (!array_key_exists('index', $item)) {
                        $item['index'] = (int)$key + 1;
                    }
                }
                unset($item);
            }

            // 3. 計算要跳過的 usage index
            $skipIndexes = $this->calculateSkipIndexes($usageJson['scientific_names'], $skipNames);

            // 4. original_name → taxon_name_id 映射（沿用原控制器邏輯：建名結果 + 既有 selected_name）
            $nameToTaxonNameId = [];
            foreach ($result['imported_taxon_names'] as $importedTaxon) {
                $nameToTaxonNameId[$importedTaxon['original_name']] = $importedTaxon['taxon_name_id'];
            }
            foreach ($submitData as $item) {
                if (!empty($item['selected_name']) && empty($item['skip'])) {
                    $nameToTaxonNameId[$item['original_name']] = $item['selected_name'];
                }
            }

            // 5. 匯入 usage（此階段更新輪詢文字為「匯入中」）
            $log->update(['phase' => 'importing_usages']);

            $service = new UsageAiImportService();
            $processedData = $service->processScientificNames($usageJson);

            // 過濾要跳過的 usage
            $processedData['scientific_names'] = array_values(
                array_filter($processedData['scientific_names'], function ($item) use ($skipIndexes) {
                    return !in_array($item['index'], $skipIndexes);
                })
            );

            // 更新 taxon_name_id
            foreach ($processedData['scientific_names'] as &$scientificName) {
                if (isset($nameToTaxonNameId[$scientificName['latin_name']])) {
                    $scientificName['taxon_name_id'] = $nameToTaxonNameId[$scientificName['latin_name']];
                }
            }
            unset($scientificName);

            // handle() 具原子性，失敗會 rollback
            $importedCount = $service->handle($processedData, $namespaceId);

            // 收尾：保留既有語意，index() 不再誤導回補學名頁
            if ($aiLog) {
                $aiLog->update(['status' => 'added']);
            }

            $log->update([
                'status'        => 'completed',
                'phase'         => null,
                'success_count' => $importedCount,
                'completed_at'  => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('CreateNamesAndImportUsagesFromAiJob failed', [
                'log_id' => $this->logId,
                'error'  => $e->getMessage(),
                'at'     => $e->getFile() . ':' . $e->getLine(),
                'trace'  => $e->getTraceAsString(),
            ]);

            // 兩個 service 的 transaction 都已 rollback，無殘留
            $log->update([
                'status'        => 'failed',
                'phase'         => null,
                'completed_at'  => now(),
                'error_message' => $e->getMessage(),
            ]);

            // 使用者資料錯誤（「第 N 筆…」）靜默；其他例外 rethrow → 觸發 failed() 寄信示警
            if (!Str::startsWith($e->getMessage(), '第 ')) {
                throw $e;
            }
        }
    }

    /**
     * 計算要跳過的 usage index（自 MyNamespaceUsageController 搬入）
     */
    private function calculateSkipIndexes(array $scientificNames, array $skipNames): array
    {
        $skipIndexes = [];

        foreach ($scientificNames as $i => $item) {
            if (!in_array($item['latin_name'], $skipNames)) {
                continue;
            }

            // accepted：跳過到下一個 accepted 之前的所有 usage
            if ($item['status'] === 'accepted') {
                $skipIndexes[] = $item['index'];
                for ($j = $i + 1; $j < count($scientificNames); $j++) {
                    if ($scientificNames[$j]['status'] === 'accepted') {
                        break;
                    }
                    $skipIndexes[] = $scientificNames[$j]['index'];
                }
            } else {
                $skipIndexes[] = $item['index'];
            }
        }

        return $skipIndexes;
    }

    public function failed(\Throwable $e): void
    {
        // 僅框架層失敗（如 timeout）會走到這；使用者資料錯誤已於 handle() 內處理、不 rethrow
        Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))
            ->send(new Email(
                'AI 匯入批次建名／匯入任務最終失敗：<br>' . nl2br($e->getMessage()),
                'TaiCOL - AI 匯入失敗',
                'TaiCOL管理員'
            ));
    }
}