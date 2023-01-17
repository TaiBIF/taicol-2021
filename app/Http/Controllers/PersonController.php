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
use App\ReferenceUsage;
use App\TypeSpecimen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PersonController extends Controller
{
    /**
     * 下拉式人名選單
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $persons = Person::where('abbreviation_name', 'like', sprintf('%%%s%%', $keyword))
            ->orWhere('original_full_name', 'like', sprintf('%%%s%%', $keyword))
            ->orWhere('other_names', 'like', sprintf('%%%s%%', $keyword))
            ->orWhereRaw('CONCAT(last_name, \' \', first_name, \' \', middle_name) like ? ', ['%' . $keyword . '%'])
            ->limit(10)
            ->get();

        return response(
            PersonCollection::collection($persons->load('country'))
        );
    }

    public function show(Request $request, $id)
    {
        $person = Person::with('country')->findOrFail($id);

        return response(PersonCollection::collection([$person])->first());
    }

    public function references(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        $references = Reference::whereHas('authors', function (Builder $query) use ($person) {
            $query->where('persons.id', $person->id);
        })->get();

        return response(ReferenceCollection::collection($references));
    }

    public function typeSpecimens(Request $request, $id)
    {
        Person::findOrFail($id);

        $usages = ReferenceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
        ])
            ->whereRaw('JSON_CONTAINS(JSON_EXTRACT(`type_specimens`, \'$[*].collector_ids\'), ?)', $id)
            ->get();

        $typeSpecimens = [];
        $usages->each(function ($usage) use ($id, &$typeSpecimens) {
            $usagesTypeSpecimens = collect($usage->type_specimens)
                ->filter(function ($usageTypeSpecimen) use ($id) {
                    return $usageTypeSpecimen['kind'] === TypeSpecimen::TYPE_SPECIMEN && in_array($id, $usageTypeSpecimen['collector_ids']);
                })->map(function ($typeSpecimen) use ($usage) {
                    return [
                        'collection_day' => $typeSpecimen['collection_day'],
                        'collection_year' => $typeSpecimen['collection_year'],
                        'collection_month' => $typeSpecimen['collection_month'],
                        'collector_ids' => $typeSpecimen['collector_ids'],
                        'country_id' => $typeSpecimen['country_id'],
                        'locality' => $typeSpecimen['locality'],
                        'collector_number' => $typeSpecimen['collector_number'],
                        'specimens' => $typeSpecimen['specimens'],
                        'taxon_name' => $usage->taxonName,
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

        if ($service->hasExist($lastName, $middleName, $firstName, $yearBirth)) {
            return response([
                'message' => 'Person exist.'
            ])->setStatusCode(409);
        }

        $person = $service->saveAll($request->all());
        $logService = new LogService();
        $logService->writeCreateLog(LogType::PERSON(), $person->id);

        return response(PersonService::fetchById($person->id));
    }

    public function update(PersonRequest $request, $id)
    {
        $person = Person::find($id);

        if (!$person) {
            return response([])->setStatusCode(404);
        }

        $lastName = $request->get('last_name') ?? '';
        $firstName = $request->get('first_name') ?? '';
        $middleName = $request->get('middle_name') ?? '';
        $yearBirth = $request->get('year_of_birth') ?? '';

        $service = new PersonService($person);

        if ($service->hasExist($lastName, $middleName, $firstName, $yearBirth)) {
            return response([
                'message' => 'Person exist.'
            ])->setStatusCode(409);
        }

        $person = $service->saveAll($request->all());

        $logService = new LogService();
        $logService->writeUpdateLog(LogType::PERSON(), $person);

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

        $files = $request->file();
        $file = $files['file'];

        $spreadsheet = IOFactory::load($file->path());
        $sheets = $spreadsheet->getAllSheets();

        try {
            $service = new PersonImportService($sheets[0]);
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
