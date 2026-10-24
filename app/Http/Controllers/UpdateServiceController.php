<?php

namespace App\Http\Controllers;

use App\Http\Services\UpdateJobNotifier;
use App\Http\Services\UpdateServiceClient;
use App\UpdateJobNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 名錄更新（Step 07）
 * - 管理員：觸發 taxon／release、執行紀錄、log、下載匯出檔（auth.admin）
 * - 更新服務：job blocked／failed 時呼叫 notify，由這裡寄信給管理員（X-Notify-Token）
 */
class UpdateServiceController extends Controller
{
    /** 後台可以觸發的 job；base 由排程跑，usage_check 在學名使用檢查頁 */
    const TRIGGERABLE = ['taxon', 'release'];

    private function unavailable()
    {
        return response()->json(['message' => '尚未設定更新服務（UPDATE_SERVICE_ROOT）'], 503);
    }

    private function serviceError(\Throwable $e, string $what)
    {
        Log::error("[Update Service] {$what} failed: {$e->getMessage()}");
        return response()->json(['message' => '連不到更新服務'], 502);
    }

    public function index(Request $request)
    {
        if (!UpdateServiceClient::enabled()) {
            return $this->unavailable();
        }
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        $page = max((int) $request->get('page', 1), 1);
        $job = $request->get('job');
        if ($job !== null && $job !== '' && !array_key_exists($job, UpdateJobNotifier::JOB_LABELS)) {
            return response()->json(['message' => 'invalid job'], 422);
        }

        try {
            $res = UpdateServiceClient::jobs($perPage, 'main', $job ?: null, ($page - 1) * $perPage);
            // 執行中的任務不一定在這一頁／這個篩選裡：main 一次只跑一個，最新一筆就是
            $latest = UpdateServiceClient::jobs(1, 'main')->json()[0] ?? null;
        } catch (\Throwable $e) {
            return $this->serviceError($e, 'GET /jobs');
        }
        if (!$res->successful()) {
            return response()->json(['message' => '更新服務回應錯誤'], 502);
        }

        return response()->json([
            'jobs' => $res->json() ?? [],
            'total' => (int) $res->header('X-Total-Count'),
            'page' => $page,
            'per_page' => $perPage,
            'active' => $latest && in_array($latest['status'], ['queued', 'running'], true) ? $latest : null,
            'labels' => UpdateJobNotifier::labels(),
        ]);
    }

    public function show(int $id)
    {
        if (!UpdateServiceClient::enabled()) {
            return $this->unavailable();
        }
        try {
            $res = UpdateServiceClient::job($id);
        } catch (\Throwable $e) {
            return $this->serviceError($e, "GET /jobs/{$id}");
        }
        return response()->json($res->json(), $res->status());
    }

    public function log(Request $request, int $id)
    {
        if (!UpdateServiceClient::enabled()) {
            return $this->unavailable();
        }
        $tail = min(max((int) $request->get('tail', 200), 1), 5000);
        try {
            $res = UpdateServiceClient::log($id, $tail);
        } catch (\Throwable $e) {
            return $this->serviceError($e, "GET /jobs/{$id}/log");
        }
        // 放在 JSON 裡回傳：前端 axios 會把回應轉成 camelCase，純文字也能照常處理
        return response()->json(['log' => $res->successful() ? $res->body() : ''], $res->status());
    }

    public function export(int $id)
    {
        if (!UpdateServiceClient::enabled()) {
            return $this->unavailable();
        }
        try {
            $file = UpdateServiceClient::downloadExport($id);
        } catch (\Throwable $e) {
            return $this->serviceError($e, "GET /jobs/{$id}/export");
        }
        if ($file === null) {
            return response()->json(['message' => '此 job 沒有匯出檔'], 404);
        }
        [$path, $filename] = $file;
        return response()->download($path, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        if (!UpdateServiceClient::enabled()) {
            return $this->unavailable();
        }
        $data = $request->validate([
            'job' => 'required|in:' . implode(',', self::TRIGGERABLE),
            'force' => 'boolean',
        ]);
        $extra = $data['job'] === 'release' ? ['force' => (bool) ($data['force'] ?? false)] : [];

        try {
            $res = UpdateServiceClient::createJob($data['job'], $request->user()->id, $extra);
        } catch (\Throwable $e) {
            return $this->serviceError($e, 'POST /jobs');
        }

        if ($res->status() === 202) {
            return response()->json(['id' => $res->json('id'), 'status' => 'queued'], 202);
        }
        if ($res->status() === 409) {
            $active = $res->json('job') ?? [];
            return response()->json([
                'message' => '已有任務執行中' . (isset($active['id']) ? "（{$active['job']} #{$active['id']}）" : ''),
                'job' => $active,
            ], 409);
        }
        Log::error('[Update Service] POST /jobs ' . $res->status() . ' ' . $res->body());
        return response()->json(['message' => $res->json('detail') ?? '更新服務回應錯誤'], $res->status() === 403 ? 403 : 502);
    }

    /**
     * 更新服務的通知：job blocked／failed 時呼叫一次，送不到會重試
     * 回 2xx 才算收到；寄信失敗回 502，服務會再送
     */
    public function notify(Request $request)
    {
        $token = (string) config('services.update_service.notify_token');
        if (strlen($token) < 32 || !hash_equals($token, (string) $request->header('X-Notify-Token'))) {
            return response()->json(['message' => 'invalid token'], 401);
        }

        // 不用 $request->validate()：呼叫端不是瀏覽器，驗證失敗要回 422 JSON，不能轉址
        $validator = Validator::make($request->all(), [
            'env' => 'required|string|max:10',
            'id' => 'required|integer',
            'job' => 'required|string|max:32',
            'status' => 'required|in:blocked,failed',
            'current_step' => 'nullable|string|max:32',
            'exit_code' => 'nullable|integer',
            'message' => 'nullable|string',
            'trigger_by' => 'nullable|string|max:16',
            'created_at' => 'nullable|string|max:19',
            'started_at' => 'nullable|string|max:19',
            'finished_at' => 'nullable|string|max:19',
            'has_export' => 'nullable|boolean',
            'log_tail' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'invalid payload', 'errors' => $validator->errors()], 422);
        }
        $job = $validator->validated();

        $record = UpdateJobNotification::firstOrCreate([
            'service_env' => $job['env'],
            'job_id' => $job['id'],
            'job_created_at' => $job['created_at'] ?? '',
        ], [
            'job' => $job['job'],
            'status' => $job['status'],
            'current_step' => $job['current_step'] ?? null,
            'message' => $job['message'] ?? null,
        ]);

        if ($record->sent_at) {
            return response()->json(['message' => 'already sent']);
        }

        try {
            $to = UpdateJobNotifier::send($job);
        } catch (\Throwable $e) {
            Log::error("[Update Service] job {$job['id']} 通知信寄送失敗：{$e->getMessage()}");
            $record->update(['error' => mb_substr($e->getMessage(), 0, 1000)]);
            return response()->json(['message' => 'mail failed'], 502);
        }

        $record->update(['sent_at' => now(), 'recipients' => implode(',', $to), 'error' => null]);
        return response()->json(['message' => 'sent', 'recipients' => count($to)]);
    }
}
