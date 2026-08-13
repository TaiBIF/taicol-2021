<?php

namespace App\Http\Controllers;

use App\Country;
use App\Http\Requests\PersonRequest;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Services\LogService;
use App\Http\Services\LogType;
use App\Http\Services\PersonImportService;
use App\Http\Services\PersonService;
use App\Person;
use App\Reference;
use App\TaxonName;
use App\ReferenceUsage;
use App\TypeSpecimen;
use App\ImportLog;
use App\Jobs\ImportPersonJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class PersonController extends Controller
{
    /**
     * 下拉式人名選單
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keyword = trim($request->get('keyword', ''));
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');

        $cleanKeyword = preg_replace('/[\p{P}\p{S}]/u', ' ', $keyword_lower);
        $words = array_filter(explode(' ', $cleanKeyword));

        $tightKeyword = preg_replace('/[\p{P}\p{S}\s]/u', '', $keyword_lower);

        $abbrNormalized = preg_replace('/\.\s+/', '.', $keyword_lower);
        $abbrNormalized = preg_replace('/[+\-><\(\)~*\"@]/', '', $abbrNormalized);
        $safeAbbrNormalized = addslashes($abbrNormalized);
        $abbrAlpha = preg_replace('/[^a-z]/u', '', $abbrNormalized);

        $query = Person::query();

        if (!empty($words)) {
            $query->where(function($q) use ($words, $tightKeyword, $abbrNormalized, $abbrAlpha) {
                $q->where(function($inner) use ($words) {
                    foreach ($words as $word) {
                        $inner->where('search_raw', 'like', '%' . $word . '%');
                    }
                });
                if (!empty($tightKeyword) && !in_array($tightKeyword, $words)) {
                    $q->orWhere('search_raw', 'like', '%' . $tightKeyword . '%');
                }
                if (strlen($abbrAlpha) >= 1) {
                    $q->orWhereRaw("LOWER(REPLACE(abbreviation_name, ' ', '')) LIKE ?", ['%' . $abbrNormalized . '%'])
                      ->orWhereRaw("LOWER(REGEXP_REPLACE(abbreviation_name, '[^a-zA-Z]', '')) LIKE ?", ['%' . $abbrAlpha . '%']);
                }
            });
        }

        $safeKeyword = addslashes($keyword_lower);
        $query->orderByRaw("CASE 
            WHEN LOWER(REPLACE(abbreviation_name, ' ', '')) = '{$safeAbbrNormalized}' THEN 0
            WHEN LOWER(REPLACE(abbreviation_name, ' ', '')) LIKE '%{$safeAbbrNormalized}%' THEN 1
            WHEN LOWER(abbreviation_name) LIKE '{$safeAbbrNormalized}%' THEN 2
            WHEN LOWER(last_name) = '{$safeKeyword}' OR LOWER(first_name) = '{$safeKeyword}' THEN 3
            WHEN LOWER(last_name) LIKE '{$safeKeyword}%' OR LOWER(first_name) LIKE '{$safeKeyword}%' THEN 4
            ELSE 5 END")
        ->orderBy('last_name', 'asc');

        return response(PersonCollection::collection($query->with('country')->limit(10)->get()));
    }

    public function show(Request $request, $id)
    {
        $person = Person::with('country')->findOrFail($id);

        return response(PersonCollection::collection([$person])->first());
    }

    public function references(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        $references = Reference:: where('is_publish', '=', 1)
        ->where('type', '!=', Reference::TYPE_BACKBONE)
        ->where('type', '!=', Reference::TYPE_SUPER_BACKBONE)
        ->whereHas('authors', function (Builder $query) use ($person) {
            $query->where('persons.id', $person->id);
        })->get();

        return response(ReferenceCollection::collection($references));
    }

    public function typeSpecimens(Request $request, $id)
    {
        Person::findOrFail($id);

        // 2025-02 改成用學名表單取資料

        $usages = TaxonName
        ::whereRaw('JSON_CONTAINS(JSON_EXTRACT(`type_specimens`, \'$[*].collectors[*].id\'), ?)', $id)
        ->get();

        $typeSpecimens = [];
        $usages->each(function ($usage) use ($id, &$typeSpecimens) {
            $usagesTypeSpecimens = collect($usage->type_specimens)
                ->filter(function ($usageTypeSpecimen) use ($id) {
                    return $usageTypeSpecimen['kind'] === TypeSpecimen::TYPE_SPECIMEN && in_array($id, array_column($usageTypeSpecimen['collectors'], 'id'));
                })->map(function ($typeSpecimen) use ($usage) {

                    return [
                        'collection_day' => isset($typeSpecimen['collection_day']) ? $typeSpecimen['collection_day'] : null,
                        'collection_year' => isset($typeSpecimen['collection_year']) ? $typeSpecimen['collection_year'] : null,
                        'collection_month' => isset($typeSpecimen['collection_month']) ? $typeSpecimen['collection_month'] : null,
                        'collector_ids' => isset($typeSpecimen['collectors']) ? array_column($typeSpecimen['collectors'], 'id') : null,
                        'country_id' => isset($typeSpecimen['country_id']) ? $typeSpecimen['country_id'] : null,
                        'locality' => isset($typeSpecimen['locality']) ? $typeSpecimen['locality'] : null,
                        'collector_number' => isset($typeSpecimen['collector_number']) ? $typeSpecimen['collector_number'] : null,
                        'specimens' => isset($typeSpecimen['specimens']) ? $typeSpecimen['specimens'] : null,
                        'taxon_name' => $usage,
                    ];
                })->toArray();

            $typeSpecimens = array_merge($typeSpecimens, $usagesTypeSpecimens);
        });

        return response(
            collect(array_unique($typeSpecimens, SORT_REGULAR))
                ->map(function ($typeSpecimen) {
                    $typeSpecimen['country'] = isset($typeSpecimen['country_id']) ? Country::find($typeSpecimen['country_id']) : null;
                    $typeSpecimen['collectors'] = PersonCollection::collection(Person::whereIn('id', $typeSpecimen['collector_ids'] ?? [])->get());
                    $typeSpecimen['taxon_name'] = TaxonNameCollection::collection([$typeSpecimen['taxon_name']])->first();
                    return $typeSpecimen;
                })
        );
    }

    public function store(PersonRequest $request)
    {
        $lastName = $request->get('last_name') ?? '';
        $firstName = $request->get('first_name') ?? '';
        $middleName = $request->get('middle_name') ?? '';
        $yearBirth = $request->get('year_of_birth') ?? '';

        $service = new PersonService(new Person());

        if ($service->hasPersonExist($lastName, $middleName, $firstName, $yearBirth)) {
            return response([
                'message' => 'Person exist.'
            ])->setStatusCode(409);
        }

        $originalFullName = $request->get('original_full_name') ?? '';
        $abbreviationName = $request->get('abbreviation_name') ?? '';

        if (!$request->boolean('has_checked_duplicates')) {

            $duplicates = $service->getPotentialDuplicates(
                $lastName, $firstName, $middleName, $originalFullName, $abbreviationName, $yearBirth
            );

            if (count($duplicates) > 0) {
                return response()->json([
                    'message' => 'Person possibly duplicates',
                    'data' => $duplicates,
                ])->setStatusCode(409);
            }
        }

        $person = $service->saveAll($request->all());
        $logService = new LogService();
        $logService->writeCreateLog(LogType::PERSON, $person->id);

        return response(PersonService::fetchById($person->id));
    }

    public function update(PersonRequest $request, $id)
    {
        $person = Person::find($id);

        if (!$person) {
            return response([])->setStatusCode(404);
        }

        // 在 saveAll 之前先保留更新前的狀態
        $oldPerson = clone $person;

        $lastName = $request->get('last_name') ?? '';
        $firstName = $request->get('first_name') ?? '';
        $middleName = $request->get('middle_name') ?? '';
        $yearBirth = $request->get('year_of_birth') ?? '';

        $service = new PersonService($person);

        if ($service->hasPersonExist($lastName, $middleName, $firstName, $yearBirth)) {
            return response([
                'message' => 'Person exist.'
            ])->setStatusCode(409);
        }

        $originalFullName = $request->get('original_full_name') ?? '';
        $abbreviationName = $request->get('abbreviation_name') ?? '';

        if (!$request->boolean('has_checked_duplicates')) {
            $duplicates = $service->getPotentialDuplicates(
                $lastName, $firstName, $middleName, $originalFullName, $abbreviationName, $yearBirth
            );

            if (count($duplicates) > 0) {
                return response()->json([
                    'message' => 'Person possibly duplicates',
                    'data' => $duplicates,
                ])->setStatusCode(409);
            }
        }

        $person = $service->saveAll($request->all());

        $logService = new LogService();
        $logService->writeUpdateLogWithComparison(LogType::PERSON, $person, $oldPerson, excludeColumns: [
            'search_raw',
        ]);

        $nameUpdateAPI = env('TAICOL_API_ROOT') . '/update/name?person_id=' . $id;
        $resp = file_get_contents($nameUpdateAPI);

        $referenceUpdateAPI = env('TAICOL_API_ROOT') . '/update/reference?person_id=' . $id;
        $resp = file_get_contents($referenceUpdateAPI);

        return response()->json([
            'id' => $person->id
        ]);
    }

    public function validateSheet(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10000|mimes:xls,xlsx',
        ], [
            'max' => '超過上傳限制 1MB',
            'required' => '必填',
            'mimes' => '檔案類型必須為 :values'
        ]);

        $files = $request->file();
        $file = $files['file'];

        $spreadsheet = IOFactory::load($file->path());
        $sheets = $spreadsheet->getAllSheets();

        $service = new PersonImportService($sheets[0]);
        $validationResult = $service->getErrorRows();

        return response()->json($validationResult);
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
            ->where('type', 'person')
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($inProgress) {
            return response()->json([
                'message' => '您已有一筆人名匯入正在處理中，請待其完成後再上傳。',
            ])->setStatusCode(409);
        }

        $file = $request->file('file');
        $path = $file->store('imports');

        $log = ImportLog::create([
            'type' => 'person',
            'status' => 'pending',
            'user_id' => $userId,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
        ]);

        ImportPersonJob::dispatch($log->id);

        return response()->json([
            'message' => 'accepted',
            'log_id' => $log->id,
        ])->setStatusCode(202);
    }
}
