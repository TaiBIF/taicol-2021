<?php

namespace App\Http\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 呼叫 TaiCOL API 的共用入口
 * - API root 由 config('services.taicol_api.root') 管理（.env: TAICOL_API_ROOT）
 * - 自動帶上 Bearer token（.env: TAICOL_API_TOKEN）
 */
class TaicolApiService
{
    public static function url(string $path): string
    {
        return rtrim(config('services.taicol_api.root'), '/') . '/' . ltrim($path, '/');
    }

    public static function client(int $timeout = 60): PendingRequest
    {
        return Http::withToken((string) config('services.taicol_api.token'))->timeout($timeout);
    }

    /**
     * GET 呼叫，非 2xx 或連線失敗會寫 log
     * $silent = true 時失敗只記 log 不丟例外（對應原本 @file_get_contents 的行為）
     */
    public static function get(string $path, array $query = [], bool $silent = false, int $timeout = 60)
    {
        try {
            return self::client($timeout)->get(self::url($path), $query)->throw();
        } catch (\Throwable $e) {
            Log::error("[TaiCOL API] GET {$path} " . json_encode($query) . " failed: {$e->getMessage()}");
            if ($silent) {
                return null;
            }
            throw $e;
        }
    }

    public static function updateName(array $query, bool $silent = false)
    {
        return self::update('/update/name', $query, $silent);
    }

    public static function updateReference(array $query, bool $silent = false)
    {
        return self::update('/update/reference', $query, $silent);
    }

    /**
     * 學名／文獻更新：有設定更新服務就打更新服務（回 202，背景執行），沒有就沿用 TaiCOL API
     */
    private static function update(string $path, array $query, bool $silent)
    {
        $root = config('services.update_service.root');
        if (!$root) {
            return self::get($path, $query, $silent);
        }

        $url = rtrim($root, '/') . '/' . ltrim($path, '/');
        try {
            return Http::withHeaders(['X-API-Key' => (string) config('services.update_service.key')])
                ->timeout(10)
                ->get($url, $query)
                ->throw();
        } catch (\Throwable $e) {
            Log::error("[Update Service] GET {$path} " . json_encode($query) . " failed: {$e->getMessage()}");
            if ($silent) {
                return null;
            }
            throw $e;
        }
    }
}
