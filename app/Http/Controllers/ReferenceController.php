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
        $properties = $request->get('properties');

        $service = new ReferenceService($reference);

        if ($service->hasReferenceExist($title, $publishYear, $authors, true)) {
            return response([
                'message' => 'Reference exist'
            ])->setStatusCode(409);
        } else if  ($service->hasReferenceExist($title, $publishYear, $authors, false)) {
            return response([
                'message' => 'Reference draft exist'
            ])->setStatusCode(409);
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
                $request->get('is_publish')
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

        $title = $request->get('title', '');
        $type = $request->get('type');
        $publishYear = $request->get('publish_year', '');
        $properties = $request->get('properties');

        $service = new ReferenceService(new Reference());


        if ($service->hasReferenceExist($title, $publishYear, $authors, true)) {
            return response([
                'message' => 'Reference exist',
            ])->setStatusCode(409);
        } else if  ($service->hasReferenceExist($title, $publishYear, $authors, false)) {
            return response([
                'message' => 'Reference draft exist',
            ])->setStatusCode(409);
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
                $request->get('is_publish')
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
        $request->validate([
            'doi' => 'required'
        ], ['required' => '必填']);

        $doi = $request->get('doi');
        $url = "https://api.crossref.org/works/$doi";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($curl);

        $jsonResult = json_decode($result);

        if ($jsonResult == null) {
            return response([
                'message' => 'resourceNotFound',
            ])->setStatusCode(404);
        }

        if ($jsonResult->status !== 'ok') {
            throw new \Exception();
        }

        $typeMapping = [
            'journal-article' => Reference::TYPE_JOURNAL,
            'book-chapter' => Reference::TYPE_BOOK_ARTICLE,
            'book' => Reference::TYPE_BOOK,
        ];

        $data = $jsonResult->message;

        if (!isset($typeMapping[$data->type])) {
            return response()
                ->json([
                    'message' => '不匯入資料'
                ])
                ->setStatusCode(409);
        }

        $type = $typeMapping[$data->type];
        $authors = $data->author;
        $publishYears = $data->published->{'date-parts'};
        $publishYear = $publishYears[0][0] ?? '';
        $authorPossible = [];

        foreach ($authors as $key => $author) {
            $givenName = str_replace(['.', ' '], '', $author->given);
            $person = Person::whereRaw('CONCAT(first_name, middle_name) like ?', ["%$givenName%"])
                ->where('last_name', 'like', "%$author->family%")
                ->first();
            $authorPossible[$key] = $person ? PersonCollection::collection([$person])[0] : null;
        }

        $articleTitle = $data->title[0];
        $bookTitle = implode(';', $data->{'container-title'});

        $book = Book::where('title', $bookTitle)->first();
        $bookAbbr = $book->title_abbreviation ?? '';
        $volume = $data->volume ?? '';
        $issue = $data->issue ?? '';
        $page = str_replace('-', '–', $data->page ?? '');
        $DOI = $data->DOI;
        $URL = $data->URL;
        $language = $data->language ?? '';

        return response()->json([
            'type' => $type,
            'authors' => $authors,
            'authors_possible' => $authorPossible,
            'publish_year' => $publishYear,
            'articleTitle' => $articleTitle,
            'book_title' => $bookTitle,
            'book_title_abbreviation' => $bookAbbr,
            'volume' => $volume,
            'issue' => $issue,
            'page' => $page,
            'doi' => $DOI,
            'url' => $URL,
            'language' => $language,
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

        // 檢查API限制
        $today = date('Y-m-d');
        $dailyKey = "gemini_api_calls:{$today}";
        $currentCalls = Redis::get($dailyKey) ?? 0;
        
        if ($currentCalls >= config('services.gemini.daily_limit', 1000)) {
            throw new \Exception('Daily API limit exceeded');
        }

        // 呼叫 python API

        $data = [];

        try {
            // 呼叫 Python API
            $response = Http::timeout(600)->post(env('TAICOL_AI_API_ROOT') . '/process-reference', [
                'file_path' => $filePath
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['success']) {

                    // 處理成功
                    $data = $result['result'] ?? [];

                    $metadata = $result['metadata'] ?? [];
                    
                    $jobLog->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'file_uri' => $result['file_uri'],
                        'metadata' => array_merge($jobLog->metadata ?? [], [
                            'tokens_used' => $metadata['tokens_used'],
                            'input_tokens' => $metadata['input_tokens'],
                            'output_tokens' => $metadata['output_tokens']
                        ])
                    ]);

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
                'file_path' => $request->file_path ?? null
            ]);

            $jobLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            // TODO 這邊回傳錯誤還要回傳給vue前端

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
            return response()
                ->json([
                    'message' => '不匯入資料'
                ])
                ->setStatusCode(409);
        }

        $type = $typeMapping[$dataType];
        $authors = $data['author'] ?? [];
        $publishedData = $data['published'] ?? [];
        $dateParts = $publishedData['date-parts'] ?? [];
        $publishYear = isset($dateParts[0][0]) ? $dateParts[0][0] : '';
        $authorPossible = [];

        foreach ($authors as $key => $author) {
            $givenName = isset($author['given']) ? 
                ucwords(strtolower(str_replace(['.', ' '], '', $author['given'])), '-') : '';
            $familyName = isset($author['family']) ? 
                ucwords(strtolower($author['family']), '-') : '';
            
            $authors[$key]['given'] = $givenName;
            $authors[$key]['family'] = $familyName;

            if ($givenName && $familyName) {
                $person = Person::whereRaw('CONCAT(first_name, middle_name) like ?', ["%$givenName%"])
                    ->where('last_name', 'like', "%$familyName%")
                    ->first();
                $authorPossible[$key] = $person ? PersonCollection::collection([$person])[0] : null;
            } else {
                $authorPossible[$key] = null;
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

        $book = $bookTitle ? Book::where('title', $bookTitle)->first() : null;
        $bookAbbr = $book ? ($book->title_abbreviation ?? '') : '';
        $volume = $data['volume'] ?? '';
        $issue = $data['issue'] ?? '';
        $page = isset($data['page']) ? str_replace('-', '–', $data['page']) : '';
        $DOI = $data['doi'] ?? '';
        $URL = $data['url'] ?? '';
        $language = $data['language'] ?? '';

        $service = new ReferenceService(new Reference());

        $usageCheck = $service->hasReferenceWithUsage($articleTitle, $publishYear, $authorPossibleIds, true);
        $fileCheck = $service->hasReferenceWithFile($articleTitle, $publishYear, $authorPossibleIds, true);
        $existingReferences = $service->hasReferenceExist($articleTitle, $publishYear, $authorPossibleIds, true, true);

        if ($usageCheck['exists']) {
            // 有usage 不提供匯入
            return response()->json([
                'message' => 'Reference has usage',
                'errors' => [
                    'file' => [
                            'type' => 'reference_usage',
                            'reference' => $usageCheck['reference']
                    ]
                ]
            ])->setStatusCode(409);
        } else if ($fileCheck['exists']) {
            // 有文獻PDF 不提供匯入
            return response()->json([
                'message' => "Reference exists with file",
                'errors' => [
                    'file' => [
                            'type' => 'reference_with_file',
                            'reference' => $fileCheck['reference']
                    ]
                ]
            ])->setStatusCode(409);
        } else if ($existingReferences) {
            // 有找到已建立的ref 提供匯入
                return response()->json([
                    'message' => 'Reference exists',
                    'data' => ReferenceCollection::collection($existingReferences)
                ], 409);
        } else if  ($service->hasReferenceExist($articleTitle, $publishYear, $authorPossibleIds, false)) {
            // 有文獻草稿 不提供匯入
            return response([
                'message' => '該筆資料已被建立為草稿，請到我的收藏裡的草稿確認並發布，若該筆不是您建立的草稿，請聯絡管理員。(catalogueoflife.taiwan@gmail.com)',
            ])->setStatusCode(409);
        }


        return response()->json([
            'type' => $type,
            'authors' => $authors,
            'authors_possible' => $authorPossible,
            'publish_year' => $publishYear,
            'articleTitle' => $articleTitle,
            'book_title' => $bookTitle,
            'book_title_abbreviation' => $bookAbbr,
            'volume' => $volume,
            'issue' => $issue,
            'page' => $page,
            'doi' => $DOI,
            'url' => $URL,
            'language' => $language,
            'file' => $filePath,
        ]);

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

        $files = $request->file();
        $file = $files['file'];

        $spreadsheet = IOFactory::load($file->path());
        $sheets = $spreadsheet->getAllSheets();

        try {
            $service = new ReferenceImportService($sheets[0]);
            $count = $service->handle();
        } catch (\Exception $e) {
            return response()->json([
                'data' => $service->getErrorRows(),
                'message' => $e->getMessage(),
            ])->setStatusCode(409);
        }

        return response()->json([
            'message' => 'success',
            'total' => $count,
        ]);
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
