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
use App\Http\Services\UsageAiImportService;

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

        $reference = Reference::find($this->referenceId);

        $filePath = $reference->properties['file'];

        // // 建立 JobLog
        $jobLog = ImportAiLog::create([
            'job_type' => 'usage_gemini_url',
            'status' => 'processing',
            'user_id' => $this->userId,
            'started_at' => now(),
            'file_path' => $filePath,
        ]);


        // 檢查API限制
        $today = date('Y-m-d');
        $dailyKey = "gemini_api_calls:{$today}";
        $currentCalls = Redis::get($dailyKey) ?? 0;
        
        if ($currentCalls >= config('services.gemini.daily_limit', 1000)) {
            throw new \Exception('Daily API limit exceeded');
        }

        // 呼叫 python API

        try {
            // 呼叫 Python API
            $response = Http::timeout(3600)->post(env('TAICOL_AI_API_ROOT') . '/process-usage', [
                'file_path' => $filePath
            ]);
                
            if ($response->successful()) {

                $result = $response->json();
                
                if ($result['success']) {
                    // 處理成功

                    $metadata = $result['metadata'] ?? [];
                    
                    // 更新JobLog為成功

                    // 更新API計數
                    Redis::incr($dailyKey);
                    Redis::expire($dailyKey, 86400);

                    // 讀取從api讀取後存的json檔

                    $usageJson = json_decode(file_get_contents(public_path('usage_results/' . $result['file_uri'] . '.json')), true);

                    if (is_array($usageJson)) {
                        foreach ($usageJson as $key => &$item) {
                            if (!array_key_exists('index', $item)) {
                                $item['index'] = $key +1;
                            }
                        }
                        unset($item);
                    }
                    
                    // 取得回傳判斷對應學名id
                    // 處理科學名稱，添加taxon_name_id
                    $service = new UsageAiImportService();
                    $processedData = $service->processScientificNames($usageJson);


                    // 判斷是否有學名需要新增
                    // 計算未比對到的數量
                    $unmatchedCount = $service->countUnmatchedScientificNames($processedData);

                    // 建立namespace
                    $user = User::find($this->userId);
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
                            'tokens_used' => $metadata['tokens_used'],
                            'input_tokens' => $metadata['input_tokens'],
                            'output_tokens' => $metadata['output_tokens'],
                            'unmatched_name_count' => $unmatchedCount
                        ])
                    ]);

                    // 沒有未比對到的話直接匯入usage
                    
                    if ($unmatchedCount == 0){
                        // 匯入資料庫
                        $importedCount = $service->handle($processedData, $namespace->id);
                    } 

                    // 6 寄信通知使用者
                    Mail::to($user->email)->send(new Email('您在物種學名管理工具匯入的文獻已處理完成，請至「我的名錄」查看。<br>
                                                            The reference you imported in TaiCOL - Name Tool has been processed. Please check it in "My Checklist."<br>
                                                            https://nametool.taicol.tw/', 'TaiCOL物種學名管理工具 - 文獻匯入通知',$user->name));

                                
                } else {
                    // Python API 回傳錯誤
                    throw new \Exception($result['error'] ?? 'Unknown error from Python service');
                }
                
            } else {
                // HTTP 請求失敗
                throw new \Exception("Python API HTTP error: {$response->status()} - {$response->body()}");
            }

        } catch (\Exception $e) {
            // 統一錯誤處理
            Log::error('Gemini processing failed', [
                'error' => $e->getMessage(),
                'file_path' => $filePath ?? null
            ]);

            $jobLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);


            $statusCode = $e->getCode();
            $mailSubject = 'TaiCOL物種學名管理工具 - 文獻匯入失敗';
            $errorDetail = $e->getMessage();

            // 寄信通知管理員失敗
            // Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))->send(new Email('使用者在物種學名管理工具匯入的學名使用有錯誤，錯誤訊息如下：<br>' . $e->getMessage(), 'TaiCOL物種學名管理工具 - 文獻匯入失敗', 'TaiCOL管理員'));
            
            Mail::to(env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com'))
                ->send(new Email(
                    '使用者在物種學名管理工具匯入的學名使用有錯誤，錯誤訊息如下：<br>' . nl2br($errorDetail), 
                    $mailSubject, 
                    'TaiCOL管理員'
                ));
                
            throw $e;

        }
    }
}