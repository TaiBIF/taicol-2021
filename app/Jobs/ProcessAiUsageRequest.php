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
use Illuminate\Support\Facades\Log;
use App\Mail\Email;
use Illuminate\Support\Facades\Mail;

class ProcessAiUsageRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $referenceId;
    private $userId;

    public function __construct($referenceId, $userId)
    {
        $this->referenceId = $referenceId;
        $this->userId = $userId;
    }

    public function handle()
    {

        Log::info('hello!');

        $reference = Reference::find($this->referenceId);

        $filePath = $reference->properties['file'];

        Log::info( $filePath);
        Log::info( $this->referenceId);
        Log::info( $this->userId);

        // // 建立 JobLog
        $jobLog = ImportAiLog::create([
            'job_type' => 'usage_gemini_url',
            'job_class' => 'UsageGeminiController',
            'status' => 'processing',
            'user_id' => $this->userId,
            'started_at' => now(),
            'metadata' => [
                'file_url' => $filePath,
            ]
        ]);


        // // 檢查API限制
        // $today = date('Y-m-d');
        // $dailyKey = "gemini_api_calls:{$today}";
        // $currentCalls = Redis::get($dailyKey) ?? 0;
        
        // if ($currentCalls >= config('services.gemini.daily_limit', 1000)) {
        //     throw new \Exception('Daily API limit exceeded');
        // }

        // // 呼叫 python API

        // $data = [];

        // try {
        //     // 呼叫 Python API
        //     Log::info('usageeeeee');
        //     $response = Http::timeout(120)->post('http://127.0.0.1:8009/process-usage', [
        //         'file_path' => $filePath
        //     ]);

        //     Log::info( $result = $response->json());
            
        //     if ($response->successful()) {
        //         $result = $response->json();
                
        //         if ($result['success']) {

        //             // 處理成功
        //             $data = $result['result'] ?? [];

        //             $metadata = $result['metadata'] ?? [];
                    
        //             // 更新JobLog為成功

        //             $jobLog->update([
        //                 'status' => 'completed',
        //                 'completed_at' => now(),
        //                 'file_uri' => $result['file_uri'],
        //                 'metadata' => array_merge($jobLog->metadata ?? [], [
        //                     'tokens_used' => $metadata['tokens_used'],
        //                     'input_tokens' => $metadata['input_tokens'],
        //                     'output_tokens' => $metadata['output_tokens']
        //                 ])
        //             ]);

        //             Log::info('API Response: ' . json_encode($data));

        //             // 更新API計數
        //             Redis::incr($dailyKey);
        //             Redis::expire($dailyKey, 86400);


        //             // TODO-1 取得回傳判斷對應學名id

        //             // TODO-2 判斷是否有學名需要新增

        //             // TODO-3 沒有的話直接匯入usage

        //             // TODO-4 若有的話暫存處理好的usage到json檔 並且標記哪些是待新增的學名

        // 5 建立namespace
        $user = User::find($this->userId);
        $namespace = new MyNamespace();
        $namespace->title = $reference->title;
        $namespace->type = 0;

        $user->namespaces()->save($namespace);


        // 6 寄信通知使用者
        Mail::to($user->email)->send(new Email('您在物種學名管理工具匯入的文獻已處理完成，請至「我的名錄」查看。<br>
                                                The reference you imported in TaiCOL - Name Tool has been processed. Please check it in "My Checklist."<br>
                                                https://nametool.taicol.tw/', 'TaiCOL物種學名管理工具 - 文獻匯入通知',$user->name));

                    
        //         } else {
        //             // Python API 回傳錯誤
        //             throw new \Exception($result['error'] ?? 'Unknown error from Python service');
        //         }
                
        //     } else {
        //         // HTTP 請求失敗
        //         throw new \Exception("Python API HTTP error: {$response->status()} - {$response->body()}");
        //     }

        // } catch (\Exception $e) {
        //     // 統一錯誤處理
        //     Log::error('Gemini processing failed', [
        //         'error' => $e->getMessage(),
        //         'file_path' => $request->file_path ?? null
        //     ]);

        //     $jobLog->update([
        //         'status' => 'failed',
        //         'completed_at' => now(),
        //         'error_message' => $e->getMessage()
        //     ]);

        //     // 寄信通知管理員失敗
        //     Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))->send(new Email('使用者在物種學名管理工具匯入的學名使用有錯誤，錯誤訊息如下：<br>' . $e->getMessage(), 'TaiCOL物種學名管理工具 - 文獻匯入失敗', 'TaiCOL管理員'));
        //     throw $e;

        // }
    }
}