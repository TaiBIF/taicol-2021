<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyNamespaceCollection;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Resources\UsageCollection;
use App\Http\Services\UsageImportService;
use App\MyNamespace;
use App\MyNamespaceUsage;
use App\Person;
use App\Rank;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MyNamespaceUsageController extends Controller
{

    public function index(Request $request, $namespaceId)
    {
        $namespace = MyNamespace::find($namespaceId);

        if ($namespace->user_id !== $request->user()->id) {
            return response()->json([], 401);
        }

        $usages = MyNamespaceUsage::where('namespace_id', $namespaceId)->orderBy('group')->orderBy('order')->get();

        $data = MyNamespaceCollection::collection([$namespace])->first()->toArray($request);
        $data['usages'] = UsageCollection::collection($usages);

        return response($data);
    }

    public function show($namespaceId, $usageId)
    {
        $usage = MyNamespaceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
            'namespace'
        ])
            ->where('namespace_id', $namespaceId)
            ->where('id', $usageId)
            ->first();

        $accepted = MyNamespaceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
            'namespace'
        ])
            ->where('namespace_id', $namespaceId)
            ->where('is_indent', false)
            ->where('group', $usage->group)
            ->where('id', '!=', $usage->id)
            ->first();

        $acceptedUsage = null;
        if ($accepted) {
            $acceptedUsage = $accepted->taxonName;
            $speciesLayer = isset($acceptedUsage->properties['species_layers']) ? $acceptedUsage->properties['species_layers'] : [];
            $acceptedUsage->species = $accepted->taxonName->properties['species_id'] ? TaxonName::find($accepted->taxonName->properties['species_id']) : null;
            $acceptedUsage->species_layers = collect($speciesLayer)->map(function ($s) {
                return [
                    'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
                    'latin_name' => $s['latin_name']
                ];
            });
        }

        $typeName = $typeName = ($usage->properties['type_name'] ?? '') ? TaxonNameCollection::collection([
            TaxonName::with([
                'authors',
                'exAuthors',
                'reference',
                'nomenclature',
                'originalTaxonName.authors',
                'originalTaxonName.exauthors'
            ])->find((int) $usage->properties['type_name'])
        ])[0] : null;

        return response([
            'id' => $usage->id,
            'namespace' => $usage->namespace,
            'taxon_name' => TaxonNameCollection::collection([$usage->taxonName])[0],
            'parent_taxon_name' => $usage->parent ? TaxonNameCollection::collection([$usage->parent])[0] : null,
            'status' => $usage->status,
            'properties' => $usage->properties,
            'type_name' => $typeName,
            'group' => $usage->group,
            'per_usages' => collect($usage->per_usages)->map(function ($r) {
                $r['target'] = isset($r['reference_id']) ? Reference::with('authors')->find($r['reference_id']) : null;
                return $r;
            }),
            'type_specimens' => collect($usage->type_specimens)->map(function ($t) {
                    $t['collectors'] = PersonCollection::collection(Person::whereIn('id', $t['collector_ids'])->get());
                    return $t;
                }) ?? [],
            'name_remark' => $usage->name_remark,
            'custom_name_remark' => $usage->custom_name_remark,
            'accepted_usage' => $acceptedUsage,
        ]);
    }

    public function update(Request $request, $namespaceId, $usageId)
    {
        $taxonNameId = $request->get('taxon_name_id');

        $typeSpecimens = $request->get('type_specimens');
        $status = $request->get('status');

        $request->validate([
            'status' => 'required',
            'properties.indications' => 'required_if:status,misapplied,undetermined',
            'parent_taxon_name_id' => function ($attribute, $parentTaxonNameId, $fail) use ($taxonNameId) {
                // validate parent taxon name id
                $taxonName = TaxonName::with(['rank'])->where('id', $taxonNameId)->first();

                $parentTaxonName = TaxonName::with(['rank'])->where('id', $parentTaxonNameId)->first();

                $speciesParentKey = ['genus', 'subgenus', 'section', 'subsection'];

                // 種下階層
                $underSpecies = ['aberration', 'morph', 'stirp', 'race', 'special-form', 'subform', 'form', 'nothovariety', 'subvariety', 'variety', 'nothosubspecies', 'subspecies'];

                if (!$taxonName) {
                    $fail('usage.wrongParent');
                } else if ($taxonName->rank->key === 'species' && !in_array($parentTaxonName->rank->key, $speciesParentKey)) {
                    $fail('usage.wrongParent');
                } else if (in_array($taxonName->rank->key, $underSpecies) && $taxonName->properties['species_id'] != $parentTaxonNameId) {
                    $fail('usage.wrongRank');
                } else if ($parentTaxonNameId === $taxonNameId) {
                    $fail('common.selfNotAllowed');
                }
            },
            'type_specimens.*.use' => 'required',
            'type_specimens.*.kind' => 'required|integer',
            'type_specimens.*.collection_year' => 'max:4',
            'type_specimens.*.collection_day' => 'max:2',
            'type_specimens.*.collector_ids' => 'array|exists:persons,id',
            'type_specimens.*.isotypes.*.herbarium' => 'required',
            'per_usages.*.reference_id' => 'required',
            'per_usages.*.show_page' => 'integer|nullable',
            'properties.is_in_taiwan' => $status === 'accepted' ? 'required' : '',
            'properties.common_names.*.name' => 'required',
            'properties.common_names.*.language' => 'required',
        ], [
            'min' => 'usage.required',
            'not_in' => 'usage.required',
            'required' => 'usage.required',
            'required_if' => 'usage.required',
            'required_without' => 'usage.required',
            'integer' => 'usage.integer',
        ]);

        $namespace = MyNamespace::find($namespaceId);

        if (!$namespace) {
            return response()->setStatusCode(404);
        }

        DB::beginTransaction();

        $namespace->touch();

        $usage = $request->all();

        $originUsage = MyNamespaceUsage::find($usageId);
        $firstUsage = $namespace->usages()->orderBy('group')->orderBy('order')->first();

        if ($firstUsage->id === $originUsage->id && $usage['status'] !== 'accepted') {
            return response()->json([
                'message' => '第一筆必須為 accepted',
                'errors' => [
                    'status' => ['usage.firstMustBeAccepted'],
                ],
            ], 422);
        }

        try {
            $originUsage->parent_taxon_name_id = $usage['parent_taxon_name_id'] ?? null;
            $originUsage->is_for_publish = false;

            if ($usage['status'] === 'not-accepted' || $usage['status'] === 'misapplied') {
                $originUsage->is_indent = true;
            }

            $originUsage->status = $usage['status'] ?? false;
            $originUsage->type_specimens = $usage['type_specimens'] ?? [];
            $originUsage->name_remark = $usage['name_remark'] ?? '';
            $originUsage->custom_name_remark = $usage['custom_name_remark'] ?? '';
            $originUsage->properties = $usage['properties'] ?? [];
            $originUsage->per_usages = $usage['per_usages'] ?? [];

            $originUsage->save();

            DB::commit();
            return response()->json([
                'data' => $usage
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[usage] - udpate::fail {$e->getMessage()}");
            return response()->json([
                'message' => 'error'
            ], 500);
        }
    }

    public function updateUsageProperties(Request $request, $namespaceId)
    {

        $namespace = MyNamespace::with('usages')->find($namespaceId);

        if (!$namespace) {
            return response()->setStatusCode(404);
        }


        DB::beginTransaction();
        try {
            foreach ($namespace->usages()->get() as $usage) {
                if ($usage->isTitle) {
                    continue;
                }

                // Only accepted names
                if ($usage->status !== 'accepted') {
                    continue;
                }

                $p = $request->all();
                $usage->properties = [
                        'is_fossil' => !isset($p['is_fossil']) ? null : (!!$p['is_fossil'] ? 1 : 0),
                        'is_marine' => !isset($p['is_marine']) ? null : (!!$p['is_marine'] ? 1 : 0),
                        'is_brackish' => !isset($p['is_brackish']) ? null : (!!$p['is_brackish'] ? 1 : 0),
                        'is_in_taiwan' => !isset($p['is_in_taiwan']) ? null : (!!$p['is_in_taiwan'] ? 1 : 0),
                        'is_freshwater' => !isset($p['is_freshwater']) ? null : (!!$p['is_freshwater'] ? 1 : 0),
                        'is_terrestrial' => !isset($p['is_terrestrial']) ? null : (!!$p['is_terrestrial'] ? 1 : 0),
                        'is_endemic' => !isset($p['is_endemic']) ? null : (!!$p['is_endemic'] ? 1 : 0),
                        'distribution_in_tw' => $p['distribution_in_tw'] ?? '',
                        'alien_type' => $p['alien_type'] ?? '',
                        'alien_status_note' => $p['alien_status_note'] ?? '',
                    ] + $usage->properties;
                $usage->save();
            }
            DB::commit();
        } catch (\Exception) {
            DB::rollBack();
        }

        return response([]);
    }


    public function store(Request $request, $namespaceId)
    {
        $usages = $request->all();

        $request->validate([
            '*.type_specimens.*.use' => 'required',
            '*.type_specimens.*.kind' => 'required|integer',
            '*.type_specimens.*.country' => 'required_if:type_specimens.*.kind,1',
            '*.type_specimens.*.specimens.*.herbarium' => 'required_if:type_specimens.*.kind,1',
            '*.type_specimens.*.collection_year' => 'max:4',
            '*.type_specimens.*.collection_day' => 'max:2',
            '*.type_specimens.*.collectors.*.id' => 'exists:persons,id',
            '*.type_specimens.*.collectors' => 'required_if:type_specimens.*.kind,1|array',
            '*.type_specimens.*.isotypes.*.herbarium' => 'required',
        ], [
            'min' => '必填',
            'not_in' => '必填',
            'required' => '必填',
            'required_if' => '必填',
            'required_without' => '必填',
        ]);

        DB::beginTransaction();

        $namespace = MyNamespace::find($namespaceId);

        if (!$namespace) {
            return response()->setStatusCode(404);
        }

        try {
            $namespace->touch();

            $group = 0;
            foreach ($usages as $index => $usage) {
                if ($index === 0 && isset($usage['is_deleted']) && $usage['is_deleted'] === false && $usage['is_title'] === false && $usage['status'] !== 'accepted' ) {
                    return response()->json([
                        'message' => '第一筆必須為 accepted'
                    ], 422);
                }

                $isTitle = (bool) ($usage['is_title'] ?? false);

                if (!$usage['is_indent']) {
                    $group += 1;
                }

                if (isset($usage['id']) && $usage['id']) {
                    $currentUsage = MyNamespaceUsage::find($usage['id']);

                    // update status
                    if ($currentUsage->status !== $usage['status']) {
                        $currentUsage->status = $usage['status'] ?? 'accepted';
                        $usage['properties']['indications'] = [];
                        $currentUsage->properties = $usage['properties'];
                        $currentUsage->save();
                    }

                    if ($currentUsage && isset($usage['is_deleted']) && (bool) $usage['is_deleted']) {
                        $currentUsage->delete();
                        continue;
                    }
                } else {
                    $currentUsage = new MyNamespaceUsage();
                    $currentUsage->namespace_id = $namespaceId;

                    $currentUsage->parent_taxon_name_id = $usage['parent_taxon_name_id'] ?? null;
                    $currentUsage->is_for_publish = false;
                    $currentUsage->status = $usage['status'] ?? 'accepted';
                    $currentUsage->type_specimens = $usage['type_specimens'] ?? [];
                    $currentUsage->name_remark = $usage['name_remark'] ?? '';
                    $currentUsage->custom_name_remark = $usage['custom_name_remark'] ?? '';
                    $currentUsage->properties = $usage['properties'] ?? [];
                    $currentUsage->per_usages = $usage['per_usages'] ?? [];
                    $currentUsage->taxon_name_id = (int) $usage['taxon_name_id'];
                }

                $currentUsage->group = $group;
                $currentUsage->order = $index;

                $currentUsage->is_title = $isTitle;
                $currentUsage->is_indent = (bool) $usage['is_indent'];
                $currentUsage->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }


        return response([]);
    }

    public function import(Request $request, $id)
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
            $service = new UsageImportService($sheets[0], $id);
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
