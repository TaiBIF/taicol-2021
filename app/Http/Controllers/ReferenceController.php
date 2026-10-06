<?php

namespace App\Http\Controllers;

use App\Book;
use App\Http\Requests\ReferenceRequest;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\UsageCollection;
use App\Http\Services\LogService;
use App\Http\Services\LogType;
use App\Http\Services\ReferenceImportService;
use App\Http\Services\ReferenceLogService;
use App\Http\Services\ReferenceService;
use App\Person;
use App\Reference;
use App\ReferenceUsage;
use App\ImportAiLog;
use App\ImportLog;
use App\Jobs\ImportReferenceJob;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\AiModelException;
use Illuminate\Support\Str;

class ReferenceController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $referenceQuery = Reference::with(['authors']);
        if ($keyword) {
            $referenceQuery->where(function ($_query) use ($keyword) {   
                $_query
                ->whereRaw('LOWER(`title`) LIKE ? ', ['%' . trim(strtoLower($keyword)) . '%'])
                ->orWhereRaw('LOWER(`subtitle`) LIKE ? ', ['%' . trim(strtoLower($keyword)) . '%'])
                ->orWhereHas('book', function ($query) use ($keyword) {
                    $query->where('is_publish', 1)->whereRaw('title LIKE ? ', '%' . $keyword . '%');
                })
                ->orWhereHas('authors', function ($query) use ($keyword) {
                    $query->where('last_name', 'like', $keyword)
                        ->orWhere('first_name', 'like', $keyword)
                        ->orWhere('middle_name', 'like', $keyword);})
                ;
            });
        }


        if ($request->get('hideCreate')==='true'){

            $referenceQuery->whereHas('usages', function ($query) {
                $query->whereNull('deleted_at');
            });
        }

        if ($request->get('sortby') === 'type') {
            $referenceQuery->orderBy('type', $request->get('direction'));
        }

        if ($request->get('sortby') === 'publish_year') {
            $referenceQuery->orderBy('publish_year', $request->get('direction'));
        }

        if ($request->get('sortby') === 'article_title') {
            $referenceQuery->orderBy('properties->article_title', $request->get('direction'));
        }

        $references = $referenceQuery
            ->where('is_publish', 1)
            ->where('type', '!=', Reference::TYPE_BACKBONE)
            ->where('type', '!=', Reference::TYPE_SUPER_BACKBONE)
            ->paginate(20);

        return response()->json([
            'total' => $references->total(),
            'data' => ReferenceCollection::collection($references->items()),
            'per_page' => $references->perPage(),
            'current_page' => $references->currentPage(),
            'last_page' => $references->lastPage(),
        ]);
    }

    public function show($id)
    {
        
        $reference = Reference::with(['authors', 'book'])
            ->where('is_publish', '=', 1)
            ->where('type', '!=', Reference::TYPE_BACKBONE)
            ->where('type', '!=', Reference::TYPE_SUPER_BACKBONE)
            ->where('id', $id)
            ->first();
        if (!$reference) {
            return response([
                'message' => 'Not Found.'
            ], 404);
        }

        return ReferenceCollection::collection([$reference])[0];
    }

    public function info($id)
    {
        
        $reference = Reference::with(['authors', 'book'])
            // ->where('is_publish', '=', 1)
            ->where('type', '!=', Reference::TYPE_BACKBONE)
            ->where('type', '!=', Reference::TYPE_SUPER_BACKBONE)
            ->where('id', $id)
            ->first();
        if (!$reference) {
            return response([
                'message' => 'Not Found.'
            ], 404);
        }

        return ReferenceCollection::collection([$reference])[0];
    }

    public function update(ReferenceRequest $request, $id)
    {
        $hasChecked = $request->boolean('has_checked_duplicates');

        $reference = Reference::with(['book', 'authors'])->find($id);

        $referenceLogService = new ReferenceLogService();
        $referenceLogService->initOriginData($reference);

        if (!$reference) {
            return response([], 404);
        }

        $authors = $request->get('authors');
        $authorsWithOrder = Person::whereIn('id', $authors)
            ->get()->sortBy(function ($model) use ($authors) {
                return array_search($model->getKey(), $authors);
            })
            ->values()
            ->map(function ($authors, $order) {
                return [
                    'person_id' => $authors->id,
                    'order' => $order
                ];
            });
        $title = $request->get('title', '') ?? '';
        $type = $request->get('type');
        $publishYear = $request->get('publish_year') ?? '';
        $bookTitle = Str::squish($request->input('properties.book_title'));
        $volume = $request->input('properties.volume');
        $pagesRange = $request->input('properties.pages_range');
        $bookId = $request->input('book_id');
        $properties = $request->get('properties');

        $service = new ReferenceService($reference);

        // if ($service->hasReferenceExist($title, $publishYear, $authors, true)) {
        //     return response([
        //         'message' => 'Reference exist'
        //     ])->setStatusCode(409);
        // } else if  ($service->hasReferenceExist($title, $publishYear, $authors, false)) {
        //     return response([
        //         'message' => 'Reference draft exist'
        //     ])->setStatusCode(409);
        // }

        if ($service->hasReferenceExist($title, $publishYear, $authors, true, $bookId, $volume, $pagesRange)) {
            return response([
                'message' => 'Reference exist',
            ])->setStatusCode(409);
        } else if ($service->hasReferenceExist($title, $publishYear, $authors, false, $bookId, $volume, $pagesRange)) {
            return response([
                'message' => 'Reference draft exist',
            ])->setStatusCode(409);
        }

        $checkData = [
                // 基礎欄位 (注意前端是用 publishYear)
                'type'          => $type,
                'publish_year'  => $publishYear, 
                // 作者群 (前端送來的就是 ID array: [1, 5, 10])
                'authors'       => $authors, 
                // 標題類 (從 properties 裡面撈，並預防性去除多餘空白)
                'article_title' => $title,
                'book_title'    => $bookTitle,
                // 詳細資訊
                'volume'        => $volume,
                'pages_range'   => $pagesRange,                
                // 嘗試撈取 book_id，如果前端 reference 物件本身有帶 book_id 就抓，沒有就 null
                // 備註：Vue 的 formData 展開了 ...this.reference，如果原本資料有 book_id 會在這裡
                'book_id'       => $bookId, 
            ];

        // Log::info($checkData);

        if (!$hasChecked){

            $duplicates = $service->getPotentialDuplicates($checkData);

            if (count($duplicates) > 0) {
                return response([
                    'message' => 'Reference possibly duplicates',
                    // 附上可綁定狀態，AI 匯入時重複文獻視窗據此停用不可綁定的選項
                    'data' => $this->withAiStatus($duplicates),
                ])->setStatusCode(409);
            }
        } 

        DB::beginTransaction();

        try {
            $service->create([
                'type' => $type,
                'title' => $request->get('title'),
                'subtitle' => $request->get('subtitle'),
                'publish_year' => $request->get('publish_year'),
                'language' => $request->get('language'),
                'properties' => $properties,
                'note' => $request->get('note'),
                'is_publish' => $request->get('is_publish'),
            ]);

            $reference->saveAuthors($authorsWithOrder);

            $service->saveBook(
                $properties['book_title'],
                $properties['book_title_abbreviation'] ?? '',
                // $request->get('is_publish')
                in_array($type, [1, 2]) ? 1 : $request->get('is_publish')
            );

            $service->saveCoverImage($request->get('image'), $request->get('cover_path'));

            $referenceLogService->saveUpdateLog($reference, $authors);
            DB::commit();

            $referenceUpdateAPI = env('TAICOL_API_ROOT') . '/update/reference?reference_id=' . $id;
            $resp = file_get_contents($referenceUpdateAPI);

        } catch (Exception $e) {
            DB::rollback();
            Log::error("[reference update]: {$e->getMessage()}");
            return response([
                'message' => $e->getMessage(),
            ])->setStatusCode(500);
        }
        return response(ReferenceCollection::collection([$reference])[0]);
    }

    public function store(ReferenceRequest $request)
    {

        $hasChecked = $request->boolean('has_checked_duplicates');

        $authors = $request->get('authors');
        $authorsWithOrder = Person::whereIn('id', $authors)
            ->get()->sortBy(function ($model) use ($authors) {
                return array_search($model->getKey(), $authors);
            })
            ->values()
            ->map(function ($authors, $order) {
                return [
                    'person_id' => $authors->id,
                    'order' => $order
                ];
            });

        $title = Str::squish($request->input('title'));
        $type = $request->get('type');
        $publishYear = $request->get('publish_year', '');
        $bookTitle = Str::squish($request->input('properties.book_title'));
        $volume = $request->input('properties.volume');
        $pagesRange = $request->input('properties.pages_range');
        $bookId = $request->input('book_id');
        $properties = $request->get('properties');


        // 判斷是否有可能重複的

        $checkData = [
                // 基礎欄位 (注意前端是用 publishYear)
                'type'          => $type,
                'publish_year'  => $publishYear, 
                // 作者群 (前端送來的就是 ID array: [1, 5, 10])
                'authors'       => $authors, 
                // 標題類 (從 properties 裡面撈，並預防性去除多餘空白)
                'article_title' => $title,
                'book_title'    => $bookTitle,
                // 詳細資訊
                'volume'        => $volume,
                'pages_range'   => $pagesRange,                
                // 嘗試撈取 book_id，如果前端 reference 物件本身有帶 book_id 就抓，沒有就 null
                // 備註：Vue 的 formData 展開了 ...this.reference，如果原本資料有 book_id 會在這裡
                'book_id'       => $bookId, 
            ];

        $service = new ReferenceService(new Reference());

        $existing = $service->hasReferenceExist($title, $publishYear, $authors, true, $bookId, $volume, $pagesRange, true);
        if ($existing) {
            return response([
                'message' => 'Reference exist',
                // 附上相同的文獻與可綁定狀態：AI 匯入時前端可直接讓使用者綁定（手動流程不使用）
                'data' => $this->withAiStatus($existing->map(fn($ref) => [
                    'id' => $ref->id,
                    'title' => $ref->title,
                    'subtitle' => $ref->subtitle,
                ])),
            ])->setStatusCode(409);
        } else if ($service->hasReferenceExist($title, $publishYear, $authors, false, $bookId, $volume, $pagesRange)) {
            return response([
                'message' => 'Reference draft exist',
            ])->setStatusCode(409);
        }

        if (!$hasChecked){

            $duplicates = $service->getPotentialDuplicates($checkData);

            if (count($duplicates) > 0) {
                return response([
                    'message' => 'Reference possibly duplicates',
                    // 附上可綁定狀態，AI 匯入時重複文獻視窗據此停用不可綁定的選項
                    'data' => $this->withAiStatus($duplicates),
                ])->setStatusCode(409);
            }
        } 

        DB::beginTransaction();

        try {
            $newReference = $service->create([
                'type' => $type,
                'title' => $title,
                'subtitle' => $request->get('subtitle'),
                'publish_year' => $publishYear,
                'language' => $request->get('language'),
                'properties' => $properties,
                'note' => $request->get('note'),
                'is_publish' => $request->get('is_publish'),
            ]);

            $newReference->saveAuthors($authorsWithOrder);

            $service->saveBook(
                $properties['book_title'],
                $properties['book_title_abbreviation'] ?? '',
                // $request->get('is_publish')
                in_array($type, [1, 2]) ? 1 : $request->get('is_publish')
            );

            // upload image
            $service->saveCoverImage($request->get('image'), $request->get('cover_path'));

            // save to my favorite list
            $service->saveToMyFavoriteItem();

            $logService = new LogService();
            $logService->writeCreateLog(LogType::REFERENCE, $newReference->id);
            DB::commit();

            $referenceUpdateAPI = env('TAICOL_API_ROOT') . '/update/reference?reference_id=' . $newReference->id;
            $resp = file_get_contents($referenceUpdateAPI);

        } catch (Exception $e) {
            DB::rollback();
            Log::error("[reference store]: {$e->getMessage()}");
            return response([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
            ])->setStatusCode(500);
        }
        return response(ReferenceCollection::collection([$newReference])[0]);
    }

    public function usages(Request $request, $id)
    {

        $offset = $request->get('offset', 0);
        $length = 100;

        $groupArray = ReferenceUsage::where('is_for_publish', 0)
                                    ->where('reference_id', $id)
                                    ->distinct('group')->pluck('group')->toArray();
        sort($groupArray);
        $groupCount = count($groupArray);
        $groupArray = array_slice($groupArray, $offset, $length);


        $usages = ReferenceUsage::with([
            'taxonName.nomenclature',
            'taxonName.reference',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
        ])
            ->where('is_for_publish', 0)
            ->where('reference_id', $id)
            ->whereIn('group', $groupArray)
            ->orderBy('group')
            ->orderBy('order')
            ->get();

        return response()->json([
            'data' => $usages->groupBy('group')->map(function ($group) {
                return $group->map(function ($usage) {
                    return UsageCollection::collection([$usage])->first();
                });
            }),
            'group_count'=> $groupCount
        ]);
    }

    public function fetchDoi(Request $request)
    {
        // 1. 驗證請求
        $request->validate([
            'doi' => 'required'
        ], ['required' => '必填']);

        $doi = $request->get('doi');
        $url = "https://api.crossref.org/works/$doi";

        // 2. 使用 Laravel Http Client 取代原生的 cURL (程式碼更簡潔、好讀)
        $response = Http::timeout(120)->get($url);

        // 如果找不到資源或解析失敗
        if ($response->notFound() || $response->object() === null) {
            return response([
                'message' => 'resourceNotFound',
            ])->setStatusCode(404);
        }

        $jsonResult = $response->object();

        // 檢查 API 狀態
        if ($jsonResult->status !== 'ok') {
            throw new \Exception('API 請求失敗或狀態異常');
        }

        // 3. 類型映射與檢查
        $typeMapping = [
            'journal-article' => Reference::TYPE_JOURNAL,
            'book-chapter'    => Reference::TYPE_BOOK_ARTICLE,
            'book'            => Reference::TYPE_BOOK,
        ];

        $data = $jsonResult->message;

        if (!isset($typeMapping[$data->type])) {
            return response()->json([
                'message' => '不匯入資料'
            ])->setStatusCode(409);
        }

        $type = $typeMapping[$data->type];
        $authors = $data->author ?? [];
        $publishYears = $data->published->{'date-parts'} ?? [];
        $publishYear = $publishYears[0][0] ?? '';
        $authorPossible = [];
        $authorCandidates = [];

        // 4. 作者比對邏輯 (去符號容錯 + 長度排序)
        foreach ($authors as $key => $author) {
            $given = $author->given ?? '';
            $family = $author->family ?? '';

            [$authorPossible[$key], $authorCandidates[$key]] = $this->matchAuthorCandidates($given, $family);
        }

        // 5. 整理其他書籍/期刊資訊
        $articleTitle = $data->title[0] ?? '';
        $bookTitle = isset($data->{'container-title'}) ? implode(';', $data->{'container-title'}) : '';

        $book = Book::where('title', $bookTitle)->first();
        $bookAbbr = $book->title_abbreviation ?? '';
        
        $volume = $data->volume ?? '';
        $issue = $data->issue ?? '';
        $page = str_replace('-', '–', $data->page ?? ''); // 將短橫線轉為 en dash
        $DOI = $data->DOI ?? $doi;
        $URL = $data->URL ?? '';
        $language = $data->language ?? '';

        // 6. 回傳整理好的資料
        return response()->json([
            'type'                    => $type,
            'authors'                 => $authors,
            'authors_possible'        => $authorPossible,
            'authors_candidates'      => $authorCandidates,
            'publish_year'            => $publishYear,
            'articleTitle'            => $articleTitle,
            'book_title'              => $bookTitle,
            'book_title_abbreviation' => $bookAbbr,
            'volume'                  => $volume,
            'issue'                   => $issue,
            'page'                    => $page,
            'doi'                     => $DOI,
            'url'                     => $URL,
            'language'                => $language,
        ]);
    }

    public function savePDF($file)
    {
        if ($file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $path = sprintf(
                'references/%s_%s.pdf',
                $originalName,
                Carbon::now()->format('Ymd')
            );

            Storage::disk('pdfs')->put($path, file_get_contents($file));
            return $path; // 回傳儲存路徑
        }

        return null;
    }

    public function fetchReferenceAi(Request $request)
    {
      
        // NOTE 這邊是測試用的

        // $existingReferences = Reference::whereIn('id', [6014])->get();


        // return response()->json([
        //     'message' => 'Reference exists',
        //     'data' => ReferenceCollection::collection($existingReferences)
        // ], 409);

        // 只允許檔案上傳
        // 先檢查是否因為 POST 太大導致資料遺失
        if (empty($request->file('file')) && empty($request->post()) && $request->header('CONTENT_LENGTH') > 0) {
            return response()->json([
                'message' => '上傳的檔案總大小超過系統限制 (Post Max Size)',
            ], 400);
        }

        $file = $request->file('file');

        if (!$file || !$file->isValid()) {
            $errorCode = $file ? $file->getError() : 'NO_FILE';
            
            // 根據不同錯誤回傳不同訊息
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => '檔案大小超過系統限制',
                UPLOAD_ERR_FORM_SIZE => '檔案大小超過表單限制', 
                UPLOAD_ERR_PARTIAL => '檔案上傳不完整',
                UPLOAD_ERR_NO_FILE => '沒有選擇檔案',
                'NO_FILE' => '沒有接收到檔案'
            ];
            
            $message = $errorMessages[$errorCode] ?? '檔案上傳失敗';
            
            return response()->json([
                'message' => $message,
            ], 400); 
        }

        // 檢查API限制（建立檔案與 log 前先擋）
        $today = date('Y-m-d');
        $dailyKey = "gemini_api_calls:{$today}";
        $currentCalls = Redis::get($dailyKey) ?? 0;

        if ($currentCalls >= config('services.gemini.daily_limit', 1000)) {
            return response()->json([
                'message' => '今日 AI 辨識次數已達上限，請明天再試。',
                'message_key' => 'aiImport.server.dailyLimit',
            ], 429);
        }

        // 檔案正常，繼續處理
        $filePath = $this->savePDF($file);

        // 建立 JobLog
        $jobLog = ImportAiLog::create([
            'job_type' => 'reference_gemini_url',
            'status' => 'processing',
            'user_id' => Auth::user()->id,
            'started_at' => now(),
            'file_path' => $filePath,
        ]);

        $data = [];

        try {
            // 呼叫 Python API
            try {
                $response = Http::timeout(600)->post(env('TAICOL_AI_API_ROOT') . '/process-reference', [
                    'file_path' => $filePath
                ]);
            } catch (ConnectionException $e) {
                // Python 服務掛掉 / 連線逾時
                throw AiModelException::connection($e);
            }

            if ($response->successful()) {
                $result = $response->json();

                if ($result['success'] ?? false) {
                    $data = $result['result'] ?? [];
                    // AI 偶爾會把沒有的欄位填成字串 "null"，統一清為空值
                    $data = $this->cleanAiNulls($data);
                    $metadata = $result['metadata'] ?? [];

                    $jobLog->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'file_uri' => $result['file_uri'],
                        'metadata' => array_merge($jobLog->metadata ?? [], [
                            'tokens_used' => $metadata['tokens_used'] ?? null,
                            'input_tokens' => $metadata['input_tokens'] ?? null,
                            'output_tokens' => $metadata['output_tokens'] ?? null,
                        ])
                    ]);

                } else {
                    throw new \Exception($result['error'] ?? 'Unknown error from Python service', 400);
                }

            } else {
                // AI 模型錯誤：轉成使用者看得懂的訊息
                if ($aiError = AiModelException::fromResponse($response)) {
                    throw $aiError;
                }
                throw new \Exception("Python API Error: " . $response->body(), $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Gemini processing failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file_path' => $filePath
            ]);

            $jobLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            if ($e instanceof AiModelException) {
                $httpStatus = 503;
                $message = $e->getMessage();
                $messageKey = 'aiImport.server.' . $e->messageKey;
                $messageParams = $e->messageParams;
            } else {
                $statusCode = (int) $e->getCode();
                $httpStatus = ($statusCode >= 400 && $statusCode < 600) ? $statusCode : 500;
                $message = '處理發生錯誤: ' . $e->getMessage();
                $messageKey = 'aiImport.server.processError';
                $messageParams = ['error' => $e->getMessage()];
            }

            return response()->json([
                'message' => $message,
                // 前端依語言顯示（aiImport.server.*）
                'message_key' => $messageKey,
                'message_params' => $messageParams,
            ], $httpStatus);
        }


        // 更新API計數
        Redis::incr($dailyKey);
        Redis::expire($dailyKey, 86400);

        // 更新JobLog為成功

        $typeMapping = [
            'journal-article' => Reference::TYPE_JOURNAL,
            'book-chapter' => Reference::TYPE_BOOK_ARTICLE,
            'book' => Reference::TYPE_BOOK,
            'checklist' => Reference::TYPE_CHECKLIST
        ];


        // 檢查 type 是否存在
        $dataType = $data['type'] ?? null;
        if (!$dataType || !isset($typeMapping[$dataType])) {
            return response()->json([
                            'code' => 'UNKNOWN_TYPE',
                            'message' => '不匯入資料（未知的文獻類型）',
                            'message_key' => 'aiImport.server.unknownType'
                        ], 409);
        }

        $type = $typeMapping[$dataType];
        $authors = $data['author'] ?? [];
        $publishedData = $data['published'] ?? [];
        $dateParts = $publishedData['date-parts'] ?? [];
        $publishYear = isset($dateParts[0][0]) ? $dateParts[0][0] : '';
        $authorPossible = [];
        $authorCandidates = [];

        foreach ($authors as $key => $author) {
            // 取得原始輸入值
            $rawGiven = $author['given'] ?? '';
            $rawFamily = $author['family'] ?? '';

            // 1. 處理要回傳給前端的資料格式 (保留你原本的邏輯)
            $displayGiven = $rawGiven ? 
                ucwords(strtolower(str_replace(['.', ' '], '', $rawGiven)), '-') : '';
            $displayFamily = $rawFamily ? 
                ucwords(strtolower($rawFamily), '-') : '';
            
            $authors[$key]['given'] = $displayGiven;
            $authors[$key]['family'] = $displayFamily;

            // 2. 進行資料庫比對
            if ($rawGiven && $rawFamily) {
                [$authorPossible[$key], $authorCandidates[$key]] = $this->matchAuthorCandidates($rawGiven, $rawFamily);
            } else {
                $authorPossible[$key] = null;
                $authorCandidates[$key] = [];
            }
        }

        $authorPossibleIds = array_filter(array_map(function($author) {
            if ($author && $author instanceof \App\Http\Resources\PersonCollection) {
                return $author->resource->id; // 取得 Person 模型的 ID
            }
            return null;
        }, $authorPossible));

        $titles = $data['title'] ?? [];
        $articleTitle = isset($titles[0]) ? $titles[0] : '';

        $containerTitles = $data['container-title'] ?? [];
        $bookTitle = !empty($containerTitles) ? implode(';', $containerTitles) : '';

        // 強化期刊/書籍比對：完全相符 → 縮寫 → 忽略大小寫與標點
        $book = $this->findBookForAi($containerTitles);
        $bookAbbr = $book ? ($book->title_abbreviation ?? '') : '';
        // 對到既有期刊時改用資料庫名稱，表單的期刊欄位才會選到同一筆
        if ($book) {
            $bookTitle = $book->title;
        }
        $bookId = $book ? ($book->id ?? '') : '';
        $volume = $data['volume'] ?? '';
        $issue = $data['issue'] ?? '';
        $page = isset($data['page']) ? str_replace('-', '–', $data['page']) : '';
        $DOI = $data['doi'] ?? '';
        $URL = $data['url'] ?? '';
        $language = $data['language'] ?? '';

        $service = new ReferenceService(new Reference());

        // 完全相符的既有文獻（標題＋年份＋作者，含草稿）
        // 原本會依狀態直接回 409 或自動寫入 PDF，改為併入相似文獻列表，由使用者確認後再綁定
        $exactIds = [];

        $usageCheck = $service->hasReferenceWithUsage($articleTitle, $publishYear, $authorPossibleIds, true);
        if ($usageCheck['exists']) {
            $exactIds[] = $usageCheck['reference']['id'];
        }

        $fileCheck = $service->hasReferenceWithFile($articleTitle, $publishYear, $authorPossibleIds, true);
        if ($fileCheck['exists']) {
            $exactIds[] = $fileCheck['reference']['id'];
        }

        foreach ([true, false] as $isPublish) {
            $matched = $service->hasReferenceExist($articleTitle, $publishYear, $authorPossibleIds, $isPublish, $bookId, $volume, $page, true);
            if ($matched) {
                foreach ($matched as $ref) {
                    $exactIds[] = $ref->id;
                }
            }
        }

        $exactIds = array_values(array_unique($exactIds));

        // 情形五：填入表單前先找出可能重複的文獻
        // 作者只取「唯一候選」者，避免同名人選誤判
        $unambiguousAuthorIds = [];
        foreach ($authorCandidates as $list) {
            if (is_array($list) && count($list) === 1) {
                $unambiguousAuthorIds[] = $list[0]['id'];
            }
        }

        $similarReferences = $service->getPotentialDuplicates([
            'type'          => $type,
            'publish_year'  => $publishYear,
            'authors'       => $unambiguousAuthorIds,
            'book_id'       => $bookId ?: null,
            'volume'        => $volume,
            'pages_range'   => $page,
            'article_title' => $articleTitle,
        ]);

        // 完全相符者排在最前面並標示 exact，其餘為相似文獻；每筆標示可綁定狀態
        $similarIds = collect($similarReferences)->pluck('id')->diff($exactIds);
        $orderedIds = collect($exactIds)->merge($similarIds)->values();
        $models = Reference::whereIn('id', $orderedIds)->get()->keyBy('id');

        $similarReferences = $orderedIds->map(function ($id) use ($models, $filePath, $exactIds) {
            $model = $models->get($id);
            if (!$model) return null;
            return array_merge(
                $this->referenceAiPayload($model, $this->referenceAiStatus($model), $filePath),
                ['exact' => in_array($id, $exactIds)]
            );
        })->filter()->values();

        return response()->json([
            'code' => 'SUCCESS',
            'data' => [
                'aiLogId' => $jobLog->id,
                'similarReferences' => $similarReferences,
                'type' => $type,
                'authors' => $authors,
                'authorsPossible' => $authorPossible,
                'authorsCandidates' => $authorCandidates,
                'publishYear' => $publishYear,
                'articleTitle' => $articleTitle,
                'bookTitle' => $bookTitle,
                'bookTitleAbbreviation' => $bookAbbr,
                'bookId' => $bookId,
                'volume' => $volume,
                'issue' => $issue,
                'page' => $page,
                'doi' => $DOI,
                'url' => $URL,
                'language' => $language,
                'file' => $filePath,
                'fileUrl' => $this->pdfUrl($filePath),
            ]
        ]);
    }

    /**
     * 作者人名比對：回傳 [第一順位人選, 所有候選人]
     * 第一順位仍供重複文獻檢查使用；有多筆候選時由前端讓使用者確認
     */
    private function matchAuthorCandidates(?string $given, ?string $family, int $limit = 10): array
    {
        if (!$given || !$family) {
            return [null, []];
        }

        $persons = Person::with('country')
            ->matchFuzzyName($given, $family)
            ->reorder()
            // 姓氏完全相符優先，其次名字長度較短（較接近）
            ->orderByRaw('CASE WHEN LOWER(last_name) = ? THEN 0 ELSE 1 END', [mb_strtolower($family)])
            ->orderByRaw("LENGTH(REPLACE(REPLACE(REPLACE(CONCAT(IFNULL(first_name,''), IFNULL(middle_name,'')), '-', ''), ' ', ''), '.', '')) ASC")
            ->limit($limit)
            ->get();

        if ($persons->isEmpty()) {
            return [null, []];
        }

        $candidates = PersonCollection::collection($persons)->resolve();

        return [PersonCollection::collection([$persons->first()])[0], $candidates];
    }

    /**
     * AI 解析出的期刊/書名比對既有 Book
     * 1. 完全相符（整串或任一 container-title，含縮寫）
     * 2. 忽略大小寫、空白、標點後相符
     */
    private function findBookForAi(array $containerTitles): ?Book
    {
        $candidates = array_values(array_filter(array_map('trim', $containerTitles)));
        if (empty($candidates)) {
            return null;
        }

        $candidatesWithJoined = array_unique(array_merge([implode(';', $candidates)], $candidates));

        $book = Book::where(function ($q) use ($candidatesWithJoined) {
                $q->whereIn('title', $candidatesWithJoined)
                  ->orWhereIn('title_abbreviation', $candidatesWithJoined);
            })
            ->orderByDesc('is_publish')
            ->first();

        if ($book) {
            return $book;
        }

        // 正規化：轉小寫，移除空白與常見標點
        $chars = [' ', '.', ',', '-', '–', ':', ';', '&', '(', ')', "'"];
        $normalize = fn($v) => str_replace($chars, '', mb_strtolower($v));
        $sqlExpr = function ($column) use ($chars) {
            $expr = "LOWER({$column})";
            foreach ($chars as $c) {
                $expr = "REPLACE({$expr}, " . DB::getPdo()->quote($c) . ", '')";
            }
            return $expr;
        };

        $normalized = array_values(array_filter(array_unique(array_map($normalize, $candidatesWithJoined))));
        if (empty($normalized)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($normalized), '?'));

        $book = Book::where(function ($q) use ($sqlExpr, $placeholders, $normalized) {
                $q->whereRaw($sqlExpr('title') . " IN ({$placeholders})", $normalized)
                  ->orWhereRaw($sqlExpr('title_abbreviation') . " IN ({$placeholders})", $normalized);
            })
            ->orderByDesc('is_publish')
            ->first();

        if ($book) {
            return $book;
        }

        // 3. 並列題名：資料庫名稱如「Quarterly Journal of Forest Research = 林業研究季刊」
        //    依 = 或 ; 拆段，任一段與 AI 解析的任一名稱相符即視為同一期刊
        $splitTitles = fn($v) => array_filter(array_map('trim', preg_split('/\s*[=;]\s*/u', (string) $v)));

        $segments = [];
        foreach ($candidates as $c) {
            foreach ($splitTitles($c) as $seg) {
                $segments[] = $seg;
            }
        }
        $segments = array_values(array_unique($segments));

        $targetSet = array_flip(array_filter(array_map($normalize, $segments)));
        if (empty($targetSet)) {
            return null;
        }

        // 先用 LIKE 縮小範圍，再在 PHP 端逐段比對
        $pool = Book::where(function ($q) use ($segments) {
                foreach ($segments as $seg) {
                    $like = '%' . addcslashes($seg, '%_\\') . '%';
                    $q->orWhere('title', 'like', $like)
                      ->orWhere('title_abbreviation', 'like', $like);
                }
            })
            ->orderByDesc('is_publish')
            ->limit(100)
            ->get();

        foreach ($pool as $b) {
            $dbSegments = array_merge($splitTitles($b->title), $splitTitles($b->title_abbreviation));
            foreach ($dbSegments as $seg) {
                if (isset($targetSet[$normalize($seg)])) {
                    return $b;
                }
            }
        }

        return null;
    }

    /**
     * 情形四、五：使用者手動 / 從相似文獻選擇綁定，將 AI 上傳的 PDF 綁到既有文獻
     */
    public function bindReferenceAi(Request $request)
    {
        $request->validate([
            'reference_id' => 'required|integer',
            'ai_log_id' => 'required|integer',
            'overwrite' => 'nullable|boolean',
        ]);

        // 只能使用自己這次上傳的 PDF
        $jobLog = ImportAiLog::where('id', $request->input('ai_log_id'))
            ->where('user_id', Auth::user()->id)
            ->where('job_type', 'reference_gemini_url')
            ->first();

        if (!$jobLog || !$jobLog->file_path || !Storage::disk('pdfs')->exists($jobLog->file_path)) {
            return response()->json([
                'message' => '找不到本次上傳的 PDF，請重新上傳',
                'message_key' => 'aiImport.server.uploadedPdfNotFound',
            ], 400);
        }

        $reference = Reference::find($request->input('reference_id'));
        if (!$reference) {
            return response()->json([
                'message' => '找不到此文獻',
                'message_key' => 'aiImport.server.referenceNotFound',
            ], 404);
        }

        $status = $this->referenceAiStatus($reference);
        $payload = $this->referenceAiPayload($reference, $status, $jobLog->file_path);

        // 草稿 / 已有學名使用 / 解析中 → 不可綁定
        $blocked = [
            'draft'      => ['DRAFT_EXISTS', '此文獻為草稿，請先至我的收藏確認並發布。'],
            'has_usage'  => ['REF_HAS_USAGE', 'Reference has usage'],
            'processing' => ['REF_PROCESSING', '此文獻的學名使用正在解析中，請待完成後再確認。'],
        ];
        if (isset($blocked[$status['status']])) {
            [$code, $message] = $blocked[$status['status']];
            return response()->json([
                'message' => $message,
                'data' => ['code' => $code, 'payload' => $payload],
            ], 409);
        }

        // 已有 PDF 但沒有學名使用 → 需使用者確認覆蓋
        if ($status['status'] === 'has_file' && !$request->boolean('overwrite')) {
            return response()->json([
                'message' => 'Reference exists with file',
                'data' => ['code' => 'REF_WITH_FILE', 'payload' => $payload],
            ], 409);
        }

        // 覆蓋時原有 PDF 檔案保留在磁碟，只更新文獻指向的檔案
        $properties = $reference->properties ?? [];
        $previousFile = $properties['file'] ?? null;
        $properties['file'] = $jobLog->file_path;
        $reference->properties = $properties;
        $reference->save();

        if ($previousFile) {
            Log::info('AI import overwrote reference PDF', [
                'reference_id' => $reference->id,
                'previous_file' => $previousFile,
                'new_file' => $jobLog->file_path,
                'user_id' => Auth::user()->id,
            ]);
        }

        return response()->json([
            'data' => ReferenceCollection::collection(collect([$reference]))->first(),
        ]);
    }

    /**
     * AI 匯入時文獻的可綁定狀態
     * draft：草稿 / has_usage：已有學名使用 / processing：學名使用解析中
     * has_file：已有 PDF 但無學名使用（可確認覆蓋）/ bindable：可直接綁定
     */
    private function referenceAiStatus(Reference $reference): array
    {
        if (!$reference->is_publish) {
            return ['status' => 'draft'];
        }

        $hasUsage = ReferenceUsage::where('reference_id', $reference->id)
            ->whereNull('deleted_at')
            ->exists();
        if ($hasUsage) {
            return ['status' => 'has_usage'];
        }

        $file = $reference->properties['file'] ?? null;
        if (!empty($file)) {
            $processing = ImportAiLog::where('job_type', 'usage_gemini_url')
                ->where('status', 'processing')
                ->where('file_path', $file)
                ->exists();

            return ['status' => $processing ? 'processing' : 'has_file', 'file' => $file];
        }

        return ['status' => 'bindable'];
    }

    /**
     * 回傳給前端的文獻資訊（含原有 PDF 與本次上傳 PDF 的連結，供使用者比對）
     */
    private function referenceAiPayload(Reference $reference, array $status, ?string $newFile = null): array
    {
        return [
            'id' => $reference->id,
            'title' => $reference->title,
            'subtitle' => $reference->subtitle,
            'status' => $status['status'],
            'file_url' => $this->pdfUrl($status['file'] ?? null),
            'new_file_url' => $this->pdfUrl($newFile),
        ];
    }

    /**
     * 重複文獻清單附上可綁定狀態（status）
     */
    private function withAiStatus($duplicates)
    {
        $models = Reference::whereIn('id', collect($duplicates)->pluck('id'))->get()->keyBy('id');

        return collect($duplicates)->map(function ($item) use ($models) {
            $model = $models->get($item['id']);
            if ($model) {
                $item['status'] = $this->referenceAiStatus($model)['status'];
            }
            return $item;
        })->values();
    }

    /**
     * 將 AI 回傳的 "null"、"None"、"N/A" 等字串視為空值（遞迴處理）
     */
    private function cleanAiNulls($value)
    {
        if (is_array($value)) {
            return array_map(fn($v) => $this->cleanAiNulls($v), $value);
        }
        if (is_string($value) && in_array(mb_strtolower(trim($value)), ['null', 'none', 'n/a', 'nan', 'undefined'], true)) {
            return '';
        }
        return $value;
    }

    private function pdfUrl(?string $path): ?string
    {
        return $path ? '/pdfs/' . ltrim($path, '/') : null;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:1000|mimes:xls,xlsx',
        ], [
            'max' => '超過上傳限制 1MB',
            'required' => '必填',
            'mimes' => '檔案類型必須為 :values'
        ]);

        $userId = $request->user()->id;

        $inProgress = ImportLog::where('user_id', $userId)
            ->where('type', 'reference')
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($inProgress) {
            return response()->json([
                'message' => '您已有一筆文獻匯入正在處理中，請待其完成後再上傳。',
            ])->setStatusCode(409);
        }

        $file = $request->file('file');
        $path = $file->store('imports');

        $log = ImportLog::create([
            'type' => 'reference',
            'status' => 'pending',
            'user_id' => $userId,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
        ]);

        ImportReferenceJob::dispatch($log->id);

        return response()->json([
            'message' => 'accepted',
            'log_id' => $log->id,
        ])->setStatusCode(202);
    }

    public function citations(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $query = DB::table('api_citations')
            ->join('references', 'api_citations.reference_id', '=', 'references.id')
            ->where('references.is_publish', 1)
            ->where('references.type', '!=', Reference::TYPE_BACKBONE)
            ->where('references.type', '!=', Reference::TYPE_SUPER_BACKBONE);

        // 只有當有關鍵字時才加入搜尋條件
        if (!empty($keyword)) {
            $query->where(function($subQuery) use ($keyword) {
                $subQuery->where('api_citations.author', 'LIKE', "%{$keyword}%")
                    ->orWhere('api_citations.short_author', 'LIKE', "%{$keyword}%")
                    ->orWhere('api_citations.content', 'LIKE', "%{$keyword}%");
            });
        }

        $references = $query->select(
                'api_citations.reference_id',
                DB::raw("CONCAT(api_citations.author, ' ', api_citations.content) as citation")
            )
            ->paginate(20);

        return response()->json([
            'total' => $references->total(),
            'data' => $references->items(),
            'per_page' => $references->perPage(),
            'current_page' => $references->currentPage(),
            'last_page' => $references->lastPage(),
        ]);
    }

}