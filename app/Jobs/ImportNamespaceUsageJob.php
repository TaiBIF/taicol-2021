<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\ImportLog;
use App\Http\Services\UsageImportService;
use App\Mail\Email;

class ImportNamespaceUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;        // 匯入涉及寫入，不自動重試避免重複匯入
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

        // 背景 Job 無 session，補回發起者身份，讓匯入寫的 log／建立者正確歸戶
        if ($log->user_id) {
            \Illuminate\Support\Facades\Auth::setUser(\App\User::find($log->user_id));
        }

        // fatal error（OOM/timeout）catch 抓不到，用 shutdown 補救
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
                        'error_message' => '處理時發生嚴重錯誤（可能記憶體不足），請縮小檔案或聯絡管理者',
                    ]);
                    if ($log->file_path && Storage::exists($log->file_path)) {
                        Storage::delete($log->file_path);
                    }
                }
            }
        });

        if ($log->cancel_requested_at) {
            $log->update([
                'status' => 'cancelled',
                'dismissed_at' => now(),
                'completed_at' => now(),
                'error_message' => '使用者已取消（尚未寫入任何資料）',
            ]);
            if ($log->file_path && Storage::exists($log->file_path)) {
                Storage::delete($log->file_path);
            }
            return;
        }

        // namespace id 由 controller 存進 context，Job 讀出傳給 service
        $namespaceId = (int) ($log->context['namespace_id'] ?? 0);

        $spreadsheet = null;

        try {
            $reader = IOFactory::createReaderForFile(Storage::path($log->file_path));
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load(Storage::path($log->file_path));
            $sheet = $spreadsheet->getAllSheets()[0];

            $service = new UsageImportService($sheet, $namespaceId);

            $log->update([
                'status' => 'processing',
                'phase' => 'validating',
                'started_at' => now(),
                'total_rows' => $service->totalRows(),
                'processed_rows' => 0,
            ]);

            // 邊跑邊回報進度；被取消就中止
            $service->onProgress = function ($phase, $done) use ($log) {
                if (ImportLog::whereKey($log->id)->value('cancel_requested_at')) {
                    throw new \RuntimeException('__cancelled__');
                }
                $log->update(['phase' => $phase, 'processed_rows' => $done]);
            };

            // 驗證階段（唯讀）
            $errorCount = $service->validate();

            // 驗證後、寫錯誤檔前，先看有沒有被取消
            $log->refresh();
            if ($log->cancel_requested_at) {
                $log->update([
                    'status' => 'cancelled',
                    'phase' => null,
                    'completed_at' => now(),
                    'dismissed_at' => now(),
                    'error_message' => '使用者已取消（尚未寫入任何資料）',
                ]);
                return;
            }

            if ($errorCount > 0) {
                $namesResolved = $log->context['names_resolved'] ?? false;

                // 第一次遇到查無此 Taxon → 先去批次新增（其他錯誤留待重跑後再回報）
                if (!$namesResolved && $service->hasUnmatchedNames()) {
                    $log->update([
                        'status'        => 'awaiting_names',
                        'phase'         => null,
                        'completed_at'  => now(),
                        'error_message' => null,
                        'context'       => array_merge($log->context ?? [], [
                            'unmatched_names' => $service->getUnmatchedNames(),
                        ]),
                    ]);
                    return;
                }

                // 已建過名 或 已無查無此 Taxon → 照常寫錯誤檔
                $errorRows = $service->getErrorRows()['error_rows'];
                $errorFilePath = $this->writeErrorFile($sheet, $spreadsheet, $errorRows, $log->id);

                $log->update([
                    'status' => 'failed',
                    'phase' => null,
                    'completed_at' => now(),
                    'error_message' => '匯入資料有誤，請下載錯誤檔查看標記',
                    'error_file_path' => $errorFilePath,
                ]);
                return;
            }

            // 寫入階段（分批）
            $count = $service->save();

            $log->update([
                'status' => 'completed',
                'phase' => null,
                'completed_at' => now(),
                'processed_rows' => $count,
                'success_count' => $count,
            ]);
        } catch (\Exception $e) {
            $log->refresh();

            if ($log->cancel_requested_at) {
                $wasSaving = $log->phase === 'saving';
                $written = (int) $log->processed_rows;
                $msg = $wasSaving
                    ? "使用者已取消，已寫入 {$written} 筆（前面批次已 commit、未回滾）"
                    : '使用者已取消（尚未寫入任何資料）';

                $log->update([
                    'status' => 'cancelled',
                    'phase' => null,
                    'completed_at' => now(),
                    'dismissed_at' => now(),
                    'error_message' => $msg,
                    'success_count' => $wasSaving ? $written : 0,
                ]);
                return;
            }

            Log::error('ImportNamespaceUsageJob failed', [
                'log_id' => $this->logId,
                'error' => $e->getMessage(),
            ]);
            $log->update([
                'status' => 'failed',
                'phase' => null,
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // awaiting_names 需要保留原始檔以便建名後續跑，其餘一律刪
            if ($log->status !== 'awaiting_names'
                && $log->file_path && Storage::exists($log->file_path)) {
                Storage::delete($log->file_path);
            }
        }
    }

    /** 在原 Excel 每列加上 error_message 欄，存到 public/import/namespace-usage 供下載 */
    private function writeErrorFile($sheet, $spreadsheet, array $errorRows, int $logId): string
    {
        $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $errorCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex + 1);

        $sheet->setCellValue($errorCol . '1', 'error_message');
        foreach ($errorRows as $key => $info) {
            $ssRow = ((int) $key) + 1; // key = 試算表列號 - 1
            $sheet->setCellValue($errorCol . $ssRow, $info['message'] ?? '');
        }

        $dir = public_path('import/namespace-usage');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $relative = 'import/namespace-usage/error_' . $logId . '_' . time() . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save(public_path($relative));

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        return $relative;
    }

    public function failed(\Throwable $e): void
    {
        Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))
            ->send(new Email(
                '名錄 Usage Excel 匯入任務最終失敗：<br>' . nl2br($e->getMessage()),
                'TaiCOL - 名錄匯入失敗',
                'TaiCOL管理員'
            ));
    }
}