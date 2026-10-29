<?php

namespace App\Http\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * 呼叫 TaiCOL 更新服務的 /jobs API（觸發 job、執行紀錄、log、匯出檔）
 * - root、key 與 TaicolApiService 的 /update/* 共用：config('services.update_service')
 * - 連線失敗會丟例外，由呼叫端轉成 502；HTTP 狀態碼（202、409、403…）原樣回傳給呼叫端判斷
 */
class UpdateServiceClient
{
    public static function enabled(): bool
    {
        return (bool) config('services.update_service.root');
    }

    private static function url(string $path): string
    {
        return rtrim(config('services.update_service.root'), '/') . '/' . ltrim($path, '/');
    }

    private static function client(int $timeout = 15): PendingRequest
    {
        return Http::withHeaders(['X-API-Key' => (string) config('services.update_service.key')])
            ->acceptJson()
            ->timeout($timeout);
    }

    /** POST /jobs：202 已排入、409 已有任務執行中、403 此環境未開放 */
    public static function createJob(string $job, ?int $userId, array $extra = []): Response
    {
        return self::client()->post(self::url('/jobs'), array_merge([
            'job' => $job,
            'trigger_by' => 'manual',
            'user_id' => $userId,
        ], $extra));
    }

    /** 符合條件的總筆數在 header X-Total-Count */
    public static function jobs(int $limit = 20, ?string $lane = 'main', ?string $job = null, int $offset = 0): Response
    {
        return self::client()->get(self::url('/jobs'), array_filter([
            'limit' => $limit,
            'offset' => $offset,
            'lane' => $lane,
            'job' => $job,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public static function job(int $id): Response
    {
        return self::client()->get(self::url("/jobs/{$id}"));
    }

    /** GET /status：最後一次增量更新、上一次版本名錄檢核（時間、筆數、之後是否又跑過增量更新） */
    public static function status(): Response
    {
        return self::client()->get(self::url('/status'));
    }

    /** GET /jobs/{id}/publish-preview：發佈前的月份、檔案大小、本次與上一次發佈的統計（id 是 release job） */
    public static function publishPreview(int $id): Response
    {
        return self::client()->get(self::url("/jobs/{$id}/publish-preview"));
    }

    public static function log(int $id, int $tail = 200): Response
    {
        return self::client()->get(self::url("/jobs/{$id}/log"), ['tail' => $tail]);
    }

    /**
     * 下載 /jobs/{id}/export 到暫存檔（release 的下載檔約 20 MB，不放進記憶體）
     * 回傳 [暫存檔路徑, 檔名]；沒有匯出檔（404）回傳 null
     */
    public static function downloadExport(int $id): ?array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'update_export_');
        $res = self::client(300)->sink($tmp)->get(self::url("/jobs/{$id}/export"));
        if ($res->status() === 404) {
            @unlink($tmp);
            return null;
        }
        if (!$res->successful()) {
            @unlink($tmp);
            $res->throw();
        }
        $filename = "job_{$id}_export.zip";
        if (preg_match('/filename="([^"]+)"/', (string) $res->header('Content-Disposition'), $m)) {
            $filename = $m[1];
        }
        return [$tmp, $filename];
    }
}
