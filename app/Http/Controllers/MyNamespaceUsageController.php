<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyNamespaceCollection;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\ParentNameResource;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Resources\UsageCollection;
use App\Http\Services\UsageImportService;
use App\Http\Services\ParentTaxonService;
use App\Http\Services\UsageAiImportService;
use App\Http\Services\TaxonNameAiImportService;
use App\MyNamespace;
use App\MyNamespaceUsage;
use App\Person;
use App\Rank;
use App\Reference;
use App\TaxonName;
use App\TmpNamespaceUsage;
use App\ImportChecklistLog;
use App\User;
use App\Country;
use App\Nomenclature;
use App\ImportLog;
use App\Jobs\ImportNamespaceUsageJob;
use App\Jobs\CreateNamesForNamespaceUsageJob;
use App\Jobs\CreateNamesAndImportUsagesFromAiJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Utils\CommonNameArray;
use App\Exports\UsagesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Services\UsagePreviewService;
use App\Http\Resources\TaxonNameSimpleSubResource;
use App\ImportAiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Jobs\ProcessAiUsageRequest;

class MyNamespaceUsageController extends Controller
{

    public function __construct(
        private ParentTaxonService $parentService,
    ) {}

    public function index(Request $request, $namespaceId)
    {
        $namespace = MyNamespace::find($namespaceId);

        // 判斷從ai匯入的話是不是有需要新增的學名
        $hasUnmatchedNames = ImportAiLog::where('import_to_id', $namespaceId)
            ->whereRaw("JSON_EXTRACT(metadata, '$.unmatched_name_count') > 0")
            ->where('status', '!=', 'added')
            ->exists();

        if ($hasUnmatchedNames) {
            return response()->json([
                'has_unmatched_names' => true,
                'redirect_to' => 'namespace-name-create-page'
            ]);
        }

        if ($namespace->user_id !== $request->user()->id) {
            return response()->json([], 401);
        }

        $offset = $request->get('offset', 0);
        $length = 100;

        $groupArray = MyNamespaceUsage::where('namespace_id', $namespaceId)
                                       ->distinct('group')->pluck('group')->toArray();
        sort($groupArray);
        $groupCount = count($groupArray);
        $groupArray = array_slice($groupArray, $offset, $length);

        $usages = MyNamespaceUsage::where('namespace_id', $namespaceId)->whereIn('group',$groupArray)->orderBy('group')->orderBy('order')->get();

        $data = MyNamespaceCollection::collection([$namespace])->first()->toArray($request);
        $data['usages'] = UsageCollection::collection($usages);
        $data['group_count'] = $groupCount;
        $data['bind_reference'] =   isset($namespace->reference_id) ? ReferenceCollection::collection([Reference::find($namespace->reference_id)])->first() : null;

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

        // $acceptedUsage = null;
        // if ($accepted) {
        //     $acceptedUsage = $accepted->taxonName;
        //     $speciesLayer = isset($acceptedUsage->properties['species_layers']) ? $acceptedUsage->properties['species_layers'] : [];
        //     $acceptedUsage->species = $accepted->taxonName->properties['species_id'] ? TaxonName::find($accepted->taxonName->properties['species_id']) : null;
        //     $acceptedUsage->species_layers = collect($speciesLayer)->map(function ($s) {
        //         return [
        //             'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
        //             'latin_name' => $s['latin_name']
        //         ];
        //     });
        // }
        

        $typeName = ($usage->properties['type_name'] ?? '') ? TaxonNameCollection::collection([
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
                    $t['collectors'] = PersonCollection::collection(Person::whereIn('id', $t['collector_ids'] ?? [])->get());
                    $t['country'] = isset($t['country_id']) ? Country::find($t['country_id']) : null;
                    $t['lecto_designated_reference'] = isset($t['lecto_designated_reference_id']) ? ReferenceCollection::collection([Reference::find($t['lecto_designated_reference_id'])])->first() : null;
                    return $t;
                }) ?? [],
            'name_remark' => $usage->name_remark,
            'custom_name_remark' => $usage->custom_name_remark,
            // 'accepted_usage' => $acceptedUsage,
            'accepted_usage' => $accepted ? new TaxonNameSimpleSubResource($accepted->taxonName) : null,
        ]);
    }

    public function update(Request $request, $namespaceId, $usageId)
    {
        $taxonNameId = $request->get('taxon_name_id');

        $typeSpecimens = $request->get('type_specimens');
        $status = $request->get('status');
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
                // 種下
                } else if (in_array($taxonName->rank->key, $underSpecies) && count($taxonName->properties['species_layers']) == 1 && $taxonName->properties['species_id'] != $parentTaxonNameId) {
                    $fail('usage.wrongParent');
                // 種下下
                } else if (in_array($taxonName->rank->key, $underSpecies) && count($taxonName->properties['species_layers']) == 2) {
                    $correctParentTaxonNameString = $taxonName->properties['latin_genus'] . ' '  . $taxonName->properties['latin_s1'];
                    $correctParentTaxonNameString .= ' ' . $taxonName->properties['species_layers'][0]['rank_abbreviation'] . ' ' . $taxonName->properties['species_layers'][0]['latin_name'];
                    if ($parentTaxonName->name != $correctParentTaxonNameString){
                        $fail('usage.wrongParent');
                    }
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
            'per_usages.*.show_page' => ['nullable', 'regex:/^[0-9a-zA-Z\-–,]+$/'],
            'per_usages.*.pro_parte_text' => 'required_if:per_usages.*.pro_parte_type,excl. ＿＿,quoad ＿＿',
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
            'per_usages.*.show_page.regex' => 'usage.show_page.regex',
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

            if (isset($usage['properties']['common_names'])){

                $new_common_names = [];
                foreach ($usage['properties']['common_names'] as $name_c){
                    
                    $name = $name_c['name'];
                    
                    foreach (array_keys(CommonNameArray::get()) as $cc_key) {
                        $name = str_replace($cc_key,CommonNameArray::get()[$cc_key],$name);
                    };

                    $new_name_c = Array(
                        "area" =>  $name_c['area'] ?? '',
                        "name" =>  trim(str_replace("\x00", "", $name)),
                        "language" =>  $name_c['language']
                    );

                    array_push($new_common_names, $new_name_c);
                    
                }

                $usage['properties']['common_names'] = $new_common_names;
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
                        'is_new_record' => !isset($p['is_new_record']) ? null : (!!$p['is_new_record'] ? 1 : 0),
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
        // 名錄編輯區頁面操作 如滑動等

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
 
                // 這裡是在介面上移動學名卡片的縮排
                // 雖然這邊應該沒有俗名的問題 但還是先寫著

                if (isset($usage['properties']['common_names'])){

                    $new_common_names = [];
                    foreach ($usage['properties']['common_names'] as $name_c){
                        
                        $name = $name_c['name'];
                        
                        foreach (array_keys(CommonNameArray::get()) as $cc_key) {
                            $name = str_replace($cc_key,CommonNameArray::get()[$cc_key],$name);
                        };
    
                        $new_name_c = Array(
                            "area" =>  $name_c['area'] ?? '',
                            "name" =>  trim(str_replace("\x00", "", $name)),
                            "language" =>  $name_c['language']
                        );
    
                        array_push($new_common_names, $new_name_c);
                        
                    }
    
                    $usage['properties']['common_names'] = $new_common_names;
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
                    $currentUsage->is_for_publish = false;
                    $currentUsage->status = $usage['status'] ?? 'accepted';
                    $currentUsage->type_specimens = $usage['type_specimens'] ?? [];
                    $currentUsage->name_remark = $usage['name_remark'] ?? '';
                    $currentUsage->custom_name_remark = $usage['custom_name_remark'] ?? '';
                    $currentUsage->properties = $usage['properties'] ?? [];
                    $currentUsage->per_usages = $usage['per_usages'] ?? [];
                    $currentUsage->taxon_name_id = (int) $usage['taxon_name_id'];

                    // 自動帶入上階層
                    $currentUsage->parent_taxon_name_id = $this->parentService->resolveOne(
                        $currentUsage->taxon_name_id,
                        $usage['parent_taxon_name_id'] ?? null
                    );
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

        $userId = $request->user()->id;

        // 擋重複：同 user + type + 同 namespace 有進行中任務才擋
        $inProgress = ImportLog::where('user_id', $userId)
            ->where('type', 'namespace_usage')
            ->where('context->namespace_id', (int) $id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($inProgress) {
            return response()->json([
                'message' => '此名錄已有一筆匯入正在處理中，請待其完成後再上傳。',
            ])->setStatusCode(409);
        }

        // 檔案落地（storage/app/imports），Job 讀完後刪除
        $path = $request->file('file')->store('imports');

        $log = ImportLog::create([
            'type' => 'namespace_usage',
            'status' => 'pending',
            'user_id' => $userId,
            'file_path' => $path,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'context' => ['namespace_id' => (int) $id],
        ]);

        ImportNamespaceUsageJob::dispatch($log->id);

        return response()->json([
            'message' => 'accepted',
            'log_id' => $log->id,
        ])->setStatusCode(202);
    }

    public function export(Request $request, $id)
    {
        return Excel::download(new UsagesExport($id), 'usages.xlsx');
    }

    public function importChecklist(Request $request){
        $tmpChecklistId = $request->get('tmp_checklist_id');

        $request->validate([
            'title' => 'required',
        ]);

        # 從 tmp_checklist_usages抓資料

        DB::beginTransaction();

        try {
            $namespace = new MyNamespace();
            $namespace->title = $request->get('title');
            $namespace->type = 0; // 預設為分類研究

            $request->user()->namespaces()->save($namespace);

            // 存 edit log
            $importLog = new ImportChecklistLog();
            $importLog->user_id = $request->user()->id;
            $importLog->namespace_id = $namespace->id;
            $importLog->only_in_taiwan = $request->get('only_in_taiwan') === 'yes' ? 1 : 0;
            $importLog->exclude_cultured = $request->get('exclude_cultured') === 'yes' ? 1 : 0;

            $filter_method = new \stdClass();

            $filter_method->view = $request->get('classification_view');
            $filter_method->usage_references = $request->get('usage_references');
            $filter_method->completeness = $request->get('completeness');

            if ($request->get('method') === 1) {

                $filter_method->type = "higher_taxa";
                $filter_method->taxon_id = $request->get('taxon_ids');

            } else if  ($request->get('method') === 2) {

                $filter_method->type = "reference";

            } else if  ($request->get('method') === 3) {

                $filter_method->type = "region";
                $filter_method->county = $request->get('county');
                $filter_method->municipality = $request->get('municipality');

            }

            $importLog->filter_method = $filter_method;
            $refs = $request->get('references');
            $importLog->included_references = implode(',', $refs);
            $importLog->save();

            $usages = TmpNamespaceUsage::where('tmp_checklist_id', $tmpChecklistId)->get();


            // === 預先解析所有 parent ===
            $taxonNameIds = $usages->pluck('taxon_name_id')->map(fn($v) => (int) $v)->unique()->values()->toArray();

            $userProvidedMap = [];
            foreach ($usages as $usage) {
                if (!empty($usage['parent_taxon_name_id'])) {
                    $userProvidedMap[(int) $usage['taxon_name_id']] = (int) $usage['parent_taxon_name_id'];
                }
            }

            $parentMap = $this->parentService->resolveMany($taxonNameIds, $userProvidedMap);

            // === 組裝 insert rows ===
            $now = now();
            $rows = [];

            foreach ($usages as $usage) {
                $taxonNameId = (int) $usage['taxon_name_id'];

                $properties = count($usage['properties']) > 0
                    ? json_encode($usage['properties'], JSON_UNESCAPED_UNICODE)
                    : '{}';

                $rows[] = [
                    'namespace_id'         => $namespace->id,
                    'taxon_name_id'        => $taxonNameId,
                    'parent_taxon_name_id' => $parentMap[$taxonNameId] ?? null,
                    'status'               => $usage['status'],
                    'type_specimens'       => json_encode($usage['type_specimens'], JSON_UNESCAPED_UNICODE),
                    'properties'           => $properties,
                    'per_usages'           => json_encode($usage['per_usages'], JSON_UNESCAPED_UNICODE),
                    'name_remark'          => '',
                    'custom_name_remark'   => '',
                    'group'                => $usage->group,
                    'order'                => $usage->order,
                    'is_title'             => 0,
                    'is_indent'            => $usage['status'] !== 'accepted' ? 1 : 0,
                    'is_for_publish'       => 0,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }

            // === 批次寫入 ===
            foreach (array_chunk($rows, 1000) as $chunk) {
                MyNamespaceUsage::insert($chunk);
            }

            // 清空tmp_checklist_usage
            TmpNamespaceUsage::where('tmp_checklist_id', $tmpChecklistId)->delete();

            DB::commit();

            return response([
                'data' =>  $namespace->id,
            ]);

        } catch (\Exception $e) {
            Log::info($e);

            DB::rollBack();

            return response([
                'data' =>  null,
            ]);

        }

    }

    public function clearChecklist(Request $request)
    {
        $tmpChecklistId = $request->get('tmp_checklist_id');
        TmpNamespaceUsage::where('tmp_checklist_id', $tmpChecklistId)->delete();
    }

    public function clear(Request $request, $namespaceId)
    {
        // 清除底下的usage
        MyNamespaceUsage::where('namespace_id', $namespaceId)
                        ->delete();

        return response([
        ]);
    }

    public function biota(Request $request)
    {
        // 給生物誌的API

        if ($request->get('token')!=env('BIOTA_TOKEN')){
            return response([
                'message' => 'Incorrect token'
            ])->setStatusCode(403);

        }


        $data = Array();
        $namespaceId = $request->get('namespace_id');

        $namespace = MyNamespace::find($namespaceId);
        $data['title'] = $namespace->title;
        $user = User::find($namespace->user_id);
        $data['author'] = $user->name;
        $data['updated_at'] = $namespace->updated_at;

        $referencesString = ImportChecklistLog::where('namespace_id', $namespaceId)
            ->value('included_references') ?? '';

        $citations = [];
        if ($referencesString) {
            $references = array_filter(array_map('trim', explode(',', $referencesString)));
            
            if (!empty($references)) {
                $citations = DB::table('api_citations')
                    ->whereIn('reference_id', $references)
                    ->select(
                        'reference_id',
                        DB::raw("CONCAT(author, ' ', content) as citation")
                    )
                    ->orderBy('citation', 'asc')
                    ->get()
                    ->toArray();
            }
        }

        $data['literatures'] = $citations;

        // 1. 取得所有相關的 namespace usages (需要更多關聯數據)
        $allUsages = MyNamespaceUsage::with([
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
            ->orderBy('group')->orderBy('order')
            ->get();

        // 2. 分離 accepted 和 synonyms
        $acceptedUsages = $allUsages->where('status', 'accepted');
        $synonymUsages = $allUsages->where('status', '!=', 'accepted');

        // 初始化 UsagePreviewService
        $service = new UsagePreviewService();

        // 3. 處理每個 accepted usage
        $groups = [];
        
        foreach ($acceptedUsages as $usage) {
            $properties =$usage->properties ?? [];
            
            // 處理 additional_fields 中的 distribution 和 description
            $additionalFields = $properties['additional_fields'] ?? [];
            $distribution = null;
            $description = null;
            
            foreach ($additionalFields as $field) {
                if (isset($field['field_name'])) {
                    if ($field['field_name'] === 'distribution') {
                        $distribution = $field['field_value'] ?? null;
                    } elseif ($field['field_name'] === 'description') {
                        $description = $field['field_value'] ?? null;
                    }
                }
            }
            
            // 處理 common_names 中的 name
            $commonNames = [];
            if (isset($properties['common_names']) && is_array($properties['common_names'])) {
                $commonNames = array_column($properties['common_names'], 'name');
                $commonNames = array_filter($commonNames); // 過濾空值
            }
            
            // 取得 note
            $note = $properties['note'] ?? null;
            
            // 處理 type_name
            $typeName = ($properties['type_name'] ?? '') ? TaxonNameSimpleSubResource::collection([
                TaxonName::with([
                    'authors',
                    'exAuthors',
                    'reference',
                    'nomenclature',
                    'originalTaxonName.authors',
                    'originalTaxonName.exauthors'
                ])->find((int) $properties['type_name'])
            ])[0] : null;

            // 使用 UsagePreviewService 處理 usage_references_text
            $usageReferencesResult = $service->process(
                TaxonNameSimpleSubResource::collection([$usage->taxonName])[0],
                $properties['indications'] ?? null, 
                collect($usage->per_usages)->map(function ($r) {
                    $r['target'] = isset($r['reference_id']) ? Reference::with('authors')->find($r['reference_id']) : null;
                    return $r;
                }), 
                collect($usage->type_specimens)->map(function ($t) {
                        $t['collectors'] = PersonCollection::collection(Person::whereIn('id', $t['collector_ids'] ?? [])->get());
                        return $t;
                    }) ?? [], 
                $usage->status, 
                false,  
                $typeName
            );

            // 合併 per_usages 和 type_specimens 欄位
            $usageReferencesText = '';
            $perUsages = $usageReferencesResult['per_usages'] ?? '';
            $typeSpecimens = $usageReferencesResult['type_specimens'] ?? '';
            
            if (!empty($perUsages) && !empty($typeSpecimens)) {
                $usageReferencesText = $perUsages . ' ' . $typeSpecimens;
            } elseif (!empty($perUsages)) {
                $usageReferencesText = $perUsages;
            } elseif (!empty($typeSpecimens)) {
                $usageReferencesText = $typeSpecimens;
            }

            // 從 api_names 表取得正確的 name 資訊
            $apiName = DB::table('api_names')
                ->where('taxon_name_id', $usage->taxon_name_id)
                ->first();

            
            // 6. 找出相同 group 的 synonyms 並處理
            $synonyms = [];
            
            $groupSynonyms = $synonymUsages->where('group', $usage->group ?? null);
            
            foreach ($groupSynonyms as $synonym) {
                // 為 synonym 也使用 UsagePreviewService
                $synonymProperties = $synonym->properties ?? [];
                
                $synonymTypeName = ($synonymProperties['type_name'] ?? '') ? TaxonNameSimpleSubResource::collection([
                    TaxonName::with([
                        'authors',
                        'exAuthors',
                        'reference',
                        'nomenclature',
                        'originalTaxonName.authors',
                        'originalTaxonName.exauthors'
                    ])->find((int) $synonymProperties['type_name'])
                ])[0] : null;

                $synonymReferencesResult = $service->process(
                    TaxonNameSimpleSubResource::collection([$synonym->taxonName])[0],
                    $synonymProperties['indications'] ?? null, 
                    collect($synonym->per_usages)->map(function ($r) {
                        $r['target'] = isset($r['reference_id']) ? Reference::with('authors')->find($r['reference_id']) : null;
                        return $r;
                    }), 
                    collect($synonym->type_specimens)->map(function ($t) {
                            $t['collectors'] = PersonCollection::collection(Person::whereIn('id', $t['collector_ids'] ?? [])->get());
                            return $t;
                        }) ?? [], 
                    $synonym->status, 
                    false,  
                    $synonymTypeName
                );

                // 合併 per_usages 和 type_specimens 欄位
                $synonymReferencesText = '';
                $synonymPerUsages = $synonymReferencesResult['per_usages'] ?? '';
                $synonymTypeSpecimens = $synonymReferencesResult['type_specimens'] ?? '';
                
                if (!empty($synonymPerUsages) && !empty($synonymTypeSpecimens)) {
                    $synonymReferencesText = $synonymPerUsages . ' ' . $synonymTypeSpecimens;
                } elseif (!empty($synonymPerUsages)) {
                    $synonymReferencesText = $synonymPerUsages;
                } elseif (!empty($synonymTypeSpecimens)) {
                    $synonymReferencesText = $synonymTypeSpecimens;
                }
                
                $synonyms[] = [
                    'name_id' => $synonym->taxon_name_id,
                    'usage_references_text' => $synonymReferencesText
                ];
            }
            
            // 7. 組合最終結果
            $groups[] = [
                'name_id' => $usage->taxon_name_id,
                'rank_id' => $usage->taxonName->rank_id ?? null,
                'name' => $apiName->formatted_name ?? null,
                'name_authors' => $apiName->name_author ?? null,
                'usage_references_text' => $usageReferencesText,
                'common_names' => $commonNames,
                'distribution' => $distribution,
                'description' => $description,
                'note' => $note,
                'synonyms' => $synonyms
            ];
        }

        $data['group'] = $groups;

        return response()->json($data, 200, [], 
            JSON_UNESCAPED_UNICODE | 
            JSON_UNESCAPED_SLASHES | 
            JSON_PRETTY_PRINT
        );

    }


    public function fetchUsageAi(Request $request)  
    {
        $data = $request->all();

        $referenceId = $request->input('reference_id');
        $userId = Auth::user()->id;

        // 背景處理

        ProcessAiUsageRequest::dispatch($referenceId, $userId);
        
        return response()->json([
            'status' => 'queued', 
            'message' => '請求已送出，處理完成後將寄信通知，完成後也可以至「我的名錄區」查看及編輯。',
        ]);


    }

    public function createName(Request $request, $namespaceId)
    {
        // 名錄編輯區 - 匯入分類群 - 從 ImportLog 讀未比對學名
        $importLogId = $request->query('import_log_id');
        if ($importLogId) {
            $log = ImportLog::where('id', $importLogId)
                ->where('user_id', $request->user()->id)
                ->where('type', 'namespace_usage')
                ->firstOrFail();

            if ($log->status !== 'awaiting_names') {
                return response(['finished' => true]);
            }

            return response([
                'data'          => $log->context['unmatched_names'] ?? [],
                'nomenclatures' => Nomenclature::with('ranks', 'kingdoms')->get(),
            ]);
        }

        $namespace = MyNamespace::find($namespaceId);

        // 1 - 重新判斷一次需要新增的學名 並回傳給前端

        $jobLog = ImportAiLog::where('import_to_id', $namespaceId)->first();
        $fileUri = $jobLog->file_uri;
        $usageJson = json_decode(file_get_contents(public_path('usage_results/' . $fileUri . '.json')), true);

        if (is_array($usageJson) && isset($usageJson['scientific_names'])) {
            foreach ($usageJson['scientific_names'] as $key => &$item) {
                if (!array_key_exists('index', $item)) {
                    $item['index'] = (int)$key + 1;
                }
            }
            unset($item);
        }

        $service = new UsageAiImportService();
        $processedData = $service->processScientificNames($usageJson);
        $unmatchedCount = $service->countUnmatchedScientificNames($processedData);

        if ($unmatchedCount > 0){

            $data = $service->getUnmatchedScientificNames($processedData);

            return response([
                'data' => $data,
                'nomenclatures' => Nomenclature::with('ranks','kingdoms')->get(),
            ]);


        } else {
            // 2 - 這步已無缺學名：改為 dispatch 同一個 Job（submit_data 空即可重用），前端輪詢
            $log = ImportLog::create([
                'type'              => 'namespace_usage_ai',
                'status'            => 'processing',
                'phase'             => 'creating_names', // 無學名可建，Job 會很快進入 importing_usages
                'user_id'           => $request->user()->id,
                'started_at'        => now(),
                'file_path'         => $jobLog->file_uri ?? 'ai', // file_path NOT NULL，佔位
                'original_filename' => 'AI 匯入',
                'context'           => [
                    'namespace_id' => (int) $namespace->id,
                    'ai_log_id'    => $jobLog->id,
                    'submit_data'  => [],
                ],
            ]);

            \App\Jobs\CreateNamesAndImportUsagesFromAiJob::dispatch($log->id);

            return response([
                'finished' => true,
                'log_id'   => $log->id,
            ]);
        }



    }

    public function addNameAndUsage(Request $request, $namespaceId)  
    {

        // 名錄編輯區 - 匯入分類群 - 從 ImportLog 讀未比對學名
        $importLogId = $request->query('import_log_id');
        if ($importLogId) {
            return $this->addNameAndUsageFromExcel($request, $namespaceId, (int) $importLogId);
        }

        try {
            $submitData = json_decode($request->getContent(), true) ?? [];

            // index() 靠這筆 AI log 的 status='added' 判斷是否還要導去補學名頁，收尾時由 Job 設回
            $aiLog = ImportAiLog::where('import_to_id', $namespaceId)->first();

            // 建名 + 匯入 usage 全部移到背景 Job，立即返回
            $log = ImportLog::create([
                'type'              => 'namespace_usage_ai',
                'status'            => 'processing',
                'phase'             => 'creating_names',
                'user_id'           => $request->user()->id,
                'started_at'        => now(),
                // ImportLog.file_path 可能為 NOT NULL；AI 無上傳檔，塞 file_uri 佔位（Job 實際仍從 file_uri 讀 json）
                'file_path'         => $aiLog->file_uri ?? 'ai',
                'original_filename' => 'AI 匯入',
                'context'           => [
                    'namespace_id' => (int) $namespaceId,
                    'ai_log_id'    => $aiLog->id ?? null,
                    'submit_data'  => $submitData,
                ],
            ]);

            CreateNamesAndImportUsagesFromAiJob::dispatch($log->id);

            return response()->json(['success' => true, 'log_id' => $log->id]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function addNameAndUsageFromExcel(Request $request, $namespaceId, int $importLogId)
    {
        try {
            $log = ImportLog::where('id', $importLogId)
                ->where('user_id', $request->user()->id)
                ->where('type', 'namespace_usage')
                ->firstOrFail();

            $submitData = json_decode($request->getContent(), true) ?? [];

            // 只建立未跳過的學名；跳過者會在重跑後出現在錯誤檔
            $toCreate = collect($submitData)
                ->filter(fn($item) => empty($item['skip']))
                ->map(function ($item) {
                    // 服務讀的是 kingdom_name，前端送的是 kingdom
                    $item['kingdom_name'] = $item['kingdom'] ?? null;
                    return $item;
                })
                ->values();

            // 建名移到背景 Job：把待建學名塞進 context，狀態進 creating_names，立即返回
            $context = $log->context ?? [];
            $context['names_to_create'] = $toCreate->toArray();

            $log->update([
                'status'          => 'processing',
                'phase'           => 'creating_names',
                'error_message'   => null,
                'error_file_path' => null,
                'processed_rows'  => 0,
                'success_count'   => null,
                'context'         => $context,
            ]);

            CreateNamesForNamespaceUsageJob::dispatch($log->id);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
