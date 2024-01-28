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
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReferenceController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $referenceQuery = Reference::with(['authors']);
        if ($keyword) {
            $referenceQuery->whereRaw('LOWER(`title`) LIKE ? ', ['%' . trim(strtoLower($keyword)) . '%'])
                ->orWhereRaw('LOWER(`subtitle`) LIKE ? ', ['%' . trim(strtoLower($keyword)) . '%'])
                ->orWhereHas('book', function ($query) use ($keyword) {
                    $query->whereRaw('title LIKE ? ', '%' . $keyword . '%');
                })
                ->orWhereHas('authors', function ($query) use ($keyword) {
                    $query->where('last_name', 'like', $keyword)
                        ->orWhere('first_name', 'like', $keyword)
                        ->orWhere('middle_name', 'like', $keyword);
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
            ->where('type', '!=', Reference::TYPE_BACKBONE)
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
        $reference = Reference::with(['authors', 'book'])->where('type', '!=', Reference::TYPE_BACKBONE)
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

        if ($service->checkExistWithNewMeta($title, $publishYear, $authors)) {
            return response([
                'message' => 'Reference exist'
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
            ]);

            $reference->saveAuthors($authorsWithOrder);

            $service->saveBook(
                $properties['book_title'],
                $properties['book_title_abbreviation'] ?? ''
            );

            $service->saveCoverImage($request->get('image'), $request->get('cover_path'));

            $referenceLogService->saveUpdateLog($reference, $authors);
            DB::commit();
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

        if ($service->checkExistWithNewMeta($title, $publishYear, $authors)) {
            return response([
                'message' => 'Reference exist'
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
            ]);

            $newReference->saveAuthors($authorsWithOrder);

            $service->saveBook(
                $properties['book_title'],
                $properties['book_title_abbreviation'] ?? ''
            );

            // upload image
            $service->saveCoverImage($request->get('image'), $request->get('cover_path'));

            // save to my favorite list
            $service->saveToMyFavoriteItem();

            $logService = new LogService();
            $logService->writeCreateLog(LogType::REFERENCE, $newReference->id);
            DB::commit();
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
            ->orderBy('group')
            ->orderBy('order')
            ->get();

        return response()->json([
            'data' => $usages->groupBy('group')->map(function ($group) {
                return $group->map(function ($usage) {
                    return UsageCollection::collection([$usage])->first();
                });
            })
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
}
