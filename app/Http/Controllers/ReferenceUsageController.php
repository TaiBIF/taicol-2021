<?php

namespace App\Http\Controllers;

use App\ImportUsageLog;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Resources\UsageCollection;
use App\Person;
use App\Rank;
use App\Reference;
use App\ReferenceUsage;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferenceUsageController extends Controller
{

    private $common_names_var = ['葉' => '葉',
                                '蘭' => '蘭',
                                '裂' => '裂',
                                '輻' => '輻',
                                '良' => '良',
                                '螺' => '螺',
                                '笠' => '笠',
                                '琉' => '琉',
                                '離' => '離',
                                '刺' => '刺',
                                '律' => '律',
                                '裡' => '裡',
                                '里' => '里',
                                '梨' => '梨',
                                '輪' => '輪',
                                '呂' => '呂',
                                '利' => '利',
                                '柳' => '柳',
                                '金' => '金',
                                '羽' => '羽',
                                '狀' => '狀',
                                '來' => '來',
                                '綠' => '綠'];


    public function index(Request $request, $id)
    {
        $reference = Reference::find($id);

        if (!$reference) {
            return response()->json([], 401);
        }


        $offset = $request->get('offset', 0);
        $length = 100;

        $groupArray = ReferenceUsage::where('reference_id', $id)
                                    ->distinct('group')->pluck('group')->toArray();
        sort($groupArray);
        $groupCount = count($groupArray);
        $groupArray = array_slice($groupArray, $offset, $length);

        $usages = ReferenceUsage::where('reference_id', $id)->whereIn('group', $groupArray)->orderBy('group')->orderBy('order')->get();


        return response([
            'type' => $reference->type,
            'usages' => UsageCollection::collection($usages),
            'group_count'=> $groupCount
        ]);
    }

    public function show($referenceId, $usageId)
    {
        $usage = ReferenceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
            'reference'
        ])
            ->where('reference_id', $referenceId)
            ->where('id', $usageId)
            ->first();

        $accepted = ReferenceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
            'reference'
        ])
            ->where('reference_id', $referenceId)
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
            'reference' => $usage->reference,
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
                    return $t;
                }) ?? [],
            'name_remark' => $usage->name_remark,
            'custom_name_remark' => $usage->custom_name_remark,
            'accepted_usage' => $acceptedUsage,
        ]);
    }

    public function update(Request $request, $id, $usageId)
    {
        $taxonNameId = $request->get('taxon_name_id');

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
            'per_usages.*.show_page' => 'integer|nullable',
            'properties.is_in_taiwan' => $status === 'accepted' ? 'required' : '',
            'properties.common_names.*.name' => 'required',
            'properties.common_names.*.language' => 'required',
            
            'properties.additional_fields.*.field_name' => 'required',
            'properties.additional_fields.*.field_value' => 'required',

            'properties.custom_fields.*.field_name_en' => 'required',
            'properties.custom_fields.*.field_value' => 'required',

        ], [
            'min' => 'usage.required',
            'not_in' => 'usage.required',
            'required' => 'usage.required',
            'required_if' => 'usage.required',
            'required_without' => 'usage.required',
            'integer' => 'usage.integer',
        ]);

        $reference = Reference::find($id);

        if (!$reference) {
            return response()->setStatusCode(404);
        }

        DB::beginTransaction();

        $reference->touch();

        $usage = $request->all();

        $properties_cols = ['type_name','is_in_taiwan','common_names','is_fossil','is_marine','is_brackish',
        'is_freshwater','is_terristrial','is_endemic','distribution_in_tw','is_new_record',
        'alien_type','alien_status_note','indications','note'];

        $record_cols = ['accepted_taxon_name_id','parent_taxon_name_id','status',
        'custom_name_remark','type_specimens','per_usages','is_indent','is_title'];

        $originUsage = ReferenceUsage::find($usageId);

        if (isset($usage['properties']['common_names'])){

            $new_common_names = [];
            foreach ($usage['properties']['common_names'] as $name_c){
                
                $name = $name_c['name'];
                
                foreach (array_keys($this->common_names_var) as $cc_key) {
                    $name = str_replace($cc_key,$this->common_names_var[$cc_key],$name);
                };

                $new_name_c = Array(
                    "area" =>  $name_c['area'],
                    "name" =>  trim(str_replace("\x00", "", $name)),
                    "language" =>  $name_c['language']
                );

                array_push($new_common_names, $new_name_c);
                
            }

            $usage['properties']['common_names'] = $new_common_names;
        }

        $old_value = array();
        foreach ($properties_cols as $properties_col){
            if (array_key_exists($properties_col, $originUsage->properties)){
                $old_value[$properties_col] = $originUsage->properties[$properties_col];
            } else {
                $old_value[$properties_col] = null;
            }
        }
        foreach ($record_cols as $record_col){
            $old_value[$record_col] = $originUsage[$record_col];
        }

        $firstUsage = $reference->usages()->orderBy('group')->orderBy('order')->first();

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

            // $usage: request傳送過來的資料
            // $originUsage: 原本的資料

            $new_value = array();
            foreach ($properties_cols as $properties_col){
                if (array_key_exists($properties_col, $originUsage->properties)){
                    $new_value[$properties_col] = $originUsage->properties[$properties_col];
                } else {
                    $new_value[$properties_col] = null;
                }
            }
            foreach ($record_cols as $record_col){
                $new_value[$record_col] = $originUsage[$record_col];
            }

            foreach(array_merge($properties_cols,$record_cols) as $now_c){
                if ($new_value[$now_c] == $old_value[$now_c]){
                    unset($old_value[$now_c]);
                    unset($new_value[$now_c]);
                }
            }

            if (count($old_value)>0){
                $edit_log = new ImportUsageLog();
                $edit_log->reference_usage_id = $originUsage->id;
                $edit_log->action = ImportUsageLog::ACTION_USAGE_UPDATE;
                $edit_log->reference_id = $id;
                $edit_log->taxon_name_id = $originUsage->taxon_name_id;
                $edit_log->user_id = $request->user()->id;
                $edit_log->old_value = json_encode($old_value);
                $edit_log->new_value = json_encode($new_value);
                $edit_log->columns = implode(',', array_map(fn($column) => $this->snakeToCamel($column), array_keys($new_value)));
                $edit_log->save();
            }

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

    public function updateUsageProperties(Request $request, $id)
    {

        $reference = Reference::with('usages')->find($id);

        if (!$reference) {
            return response()->setStatusCode(404);
        }


        DB::beginTransaction();
        try {

            $record_cols = ['is_fossil','is_marine','is_brackish','is_in_taiwan','is_freshwater','is_terrestrial',
                            'is_endemic','distribution_in_tw','is_new_record','alien_type','alien_status_note'];

            foreach ($reference->usages()->get() as $usage) {
                if ($usage->isTitle) {
                    continue;
                }

                // Only accepted names
                if ($usage->status !== 'accepted') {
                    continue;
                }

                // 先紀錄原本的value
                $old_value = array();
                foreach ($record_cols as $record_col){
                    $old_value[$record_col] = $usage->properties[$record_col];
                }

                $p = $request->all();

                $new_value = [
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
                ];

                $usage->properties = $new_value + $usage->properties;

                foreach ($record_cols as $record_col){
                    if ($new_value[$record_col] == $old_value[$record_col]){
                        unset($new_value[$record_col]);
                        unset($old_value[$record_col]);
                    }
                }

                if (count($old_value)>0){
                    $edit_log = new ImportUsageLog();
                    $edit_log->reference_usage_id = $usage->id;
                    $edit_log->reference_id = $id;
                    $edit_log->taxon_name_id = $usage->taxon_name_id;
                    $edit_log->action = ImportUsageLog::ACTION_USAGE_UPDATE;
                    $edit_log->user_id = $request->user()->id;
                    $edit_log->old_value = json_encode($old_value);
                    $edit_log->new_value = json_encode($new_value);
                    $edit_log->columns = implode(',', array_map(fn($column) => $this->snakeToCamel($column), array_keys($new_value)));
                    $edit_log->save();
                }
                
                $usage->save();
            }
            DB::commit();
        } catch (\Exception) {
            DB::rollBack();
        }

        return response([]);
    }


    public function store(Request $request, $id)
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

        $reference = Reference::find($id);

        if (!$reference) {
            return response()->setStatusCode(404);
        }

        try {
            $reference->touch();

            $group = 0;
            foreach ($usages as $index => $usage) {


                if ($index === 0 && $usage['status'] !== 'accepted') {
                    return response()->json([
                        'message' => '第一筆必須為 accepted'
                    ], 422);
                }

                $isTitle = (bool) ($usage['is_title'] ?? false);

                if (!$usage['is_indent']) {
                    $group += 1;
                }


                if (isset($usage['id']) && $usage['id']) {

                    $currentUsage = ReferenceUsage::find($usage['id']);

                    $old_value = array(
                        "status" => $currentUsage->status,
                        "is_indent" => $currentUsage->is_indent,
                        "is_title" => $currentUsage->is_title,
                        "accepted_taxon_name_id" => $currentUsage->accepted_taxon_name_id,
                        "indications" => isset($currentUsage->properties['indications']) ? $currentUsage->properties['indications'] : null,
                    );


                    $nowAction = ImportUsageLog::ACTION_USAGE_UPDATE;
                     
                    // update status
                    if ($currentUsage->status !== $usage['status']) {
                        $currentUsage->status = $usage['status'] ?? 'accepted';
                        $usage['properties']['indications'] = [];
                        $currentUsage->properties = $usage['properties'];
                        $currentUsage->save();
                    }

                    if ($currentUsage && isset($usage['is_deleted']) && (bool) $usage['is_deleted']) {
                        $currentUsage->delete();
                        $edit_log = new ImportUsageLog();
                        $edit_log->reference_usage_id = $usage['id'];
                        $edit_log->taxon_name_id = $currentUsage->taxon_name_id;
                        $edit_log->reference_id = $id;
                        $edit_log->action = ImportUsageLog::ACTION_USAGE_DELETE;
                        $edit_log->user_id = $request->user()->id;
                        $edit_log->save();
                        continue; // 這邊就會跳出loop
                    }
                } else {


                    $nowAction = ImportUsageLog::ACTION_USAGE_ADD;
                    $currentUsage = new ReferenceUsage();
                    $currentUsage->reference_id = $id;

                    // $currentUsage->parent_taxon_name_id = $usage['parent_taxon_name_id'] ?? null;
                    $currentUsage->is_for_publish = false;
                    $currentUsage->status = $usage['status'] ?? 'accepted';
                    $currentUsage->type_specimens = $usage['type_specimens'] ?? [];
                    $currentUsage->name_remark = $usage['name_remark'] ?? '';
                    $currentUsage->custom_name_remark = $usage['custom_name_remark'] ?? '';
                    $currentUsage->properties = $usage['properties'] ?? [];
                    $currentUsage->per_usages = $usage['per_usages'] ?? [];
                    $currentUsage->taxon_name_id = (int) $usage['taxon_name_id'];

                    // 自動帶入上階層 優先採用usage
                    $parent = $usage['parent_taxon_name_id'] ?? DB::table('accepted_usages')
                    ->select('parent_taxon_name_id')
                    ->where('taxon_name_id', $currentUsage->taxon_name_id)
                    ->first();

                    $parent = $parent->parent_taxon_name_id ?? null;
                    
                    $nowName = TaxonName::find($currentUsage->taxon_name_id);
                    $nomenclatureId = $nowName->nomenclature_id;

                    if (empty($parent) && $nomenclatureId != 4){

                        $speciesLayer = $nowName->properties['species_layers'];

                        if (count($speciesLayer) == 1) {
                            $parent = $nowName->properties['species_id'];
                        } else if (count($speciesLayer) == 2){
                            $parentTaxonNameString = $nowName->properties['latin_genus'] . ' '  . $nowName->properties['latin_s1'];
                            $parentTaxonNameString .= ' ' . $speciesLayer[0]['rank_abbreviation'] . ' ' . $speciesLayer[0]['latin_name'];
                    
                            $parent = TaxonName::where('name', $parentTaxonNameString)
                                                ->where('nomenclature_id', $nomenclatureId)
                                                ->first()->id;
                        } else if ($nowName->rank_id == 34) {
                            // 種
                            $parentTaxonNameString = $nowName->properties['latin_genus'];

                            $parent_query = TaxonName::where('name', $parentTaxonNameString)
                                                ->where('nomenclature_id', $nomenclatureId);
                            if ($parent_query->count() > 0){
                                $parent = $parent_query->first()->id;
                            }

                        }
                    }

                    $currentUsage->parent_taxon_name_id = $parent;
                    
                }

                $currentUsage->accepted_taxon_name_id = $currentUsage->status === 'accepted' ? $currentUsage->taxon_name_id : $previousUsageId ?? null;

                $currentUsage->group = $group;
                $currentUsage->order = $index;

                $currentUsage->is_title = $isTitle;
                $currentUsage->is_indent = (bool) $usage['is_indent'];
                $currentUsage->save();


                if ($currentUsage->status === 'accepted') {
                    $previousUsageId = $currentUsage->taxon_name_id;
                }


                $edit_log = new ImportUsageLog();
                $edit_log->reference_usage_id = $currentUsage->id;
                $edit_log->taxon_name_id = $currentUsage->taxon_name_id;
                $edit_log->reference_id = $id;
                $edit_log->action = $nowAction;
                $edit_log->user_id = $request->user()->id;

                // 因為會for loop全部的 所以這邊還是要判斷有沒有更新
                
                // $columnChanges = [];
                if ($nowAction == ImportUsageLog::ACTION_USAGE_UPDATE){
                    
                    $new_value = array();
                    
                    foreach (['status','is_indent','is_title','accepted_taxon_name_id'] as $now_c){
                        
                        if ($currentUsage[$now_c] != $old_value[$now_c]){
                            $new_value[$now_c] = $currentUsage[$now_c];
                        } else {
                            unset($old_value[$now_c]);
                        }
                    }

                    if (isset($currentUsage['properties']['indications'])){
                        if ($currentUsage['properties']['indications'] !== $old_value['indications']){
                            $new_value['indications'] = $currentUsage['indications'];
                        } else {
                            unset($old_value['indications']);
                        }
                    } else if (!isset($old_value['indications'])){
                        unset($old_value['indications']);
                    } 

                    if (count($old_value)>0){
                        $edit_log->old_value = json_encode($old_value);
                        $edit_log->new_value = json_encode($new_value);    
                        $edit_log->columns = implode(',', array_map(fn($column) => $this->snakeToCamel($column), array_keys($new_value)));
                    } else {
                        // 沒有修改的不存編輯紀錄
                        continue;
                    }
                }

                $edit_log->save();


            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }

        return response([]);
    }


    public function updateCommonName(Request $request)
    {

        $request->validate([
            'taxon_name_id' => 'required',
            'properties.common_names.*.name' => 'required',
            'properties.common_names.*.language' => 'required',
        ], [
            'required' => 'usage.required',
        ]);


        DB::beginTransaction();

        $usage = $request->all();

        // 整理common_names
        if (isset($usage['properties']['common_names'])){

            $new_common_names = [];
            foreach ($usage['properties']['common_names'] as $name_c){
                
                $name = $name_c['name'];
                
                foreach (array_keys($this->common_names_var) as $cc_key) {
                    $name = str_replace($cc_key,$this->common_names_var[$cc_key],$name);
                };

                $new_name_c = Array(
                    "area" =>  $name_c['area'],
                    "name" =>  trim(str_replace("\x00", "", $name)),
                    "language" =>  $name_c['language']
                );

                array_push($new_common_names, $new_name_c);
                
            }

            $usage['properties']['common_names'] = $new_common_names;
        }

        // 加上需要的欄位


        $usage['properties']['is_in_taiwan'] = null;

        $currentUsage = new ReferenceUsage();
        $currentUsage->reference_id = 95; # common name backbone
        $currentUsage->is_for_publish = false;
        $currentUsage->is_title = false;
        $currentUsage->is_indent = false;
        $currentUsage->status = 'accepted';
        $currentUsage->taxon_name_id = (int) $usage['taxon_name_id'];
        $currentUsage->accepted_taxon_name_id = (int) $usage['taxon_name_id'];
        $currentUsage->group = 1;
        $currentUsage->order = 1;
        $currentUsage->name_remark = '';
        $currentUsage->custom_name_remark = '';
        $currentUsage->per_usages = [];
        $currentUsage->type_specimens = [];

        // 自動帶入上階層 
        $parent = DB::table('accepted_usages')
        ->select('parent_taxon_name_id')
        ->where('taxon_name_id', $currentUsage->taxon_name_id)
        ->first();

        $parent = $parent->parent_taxon_name_id ?? null;

        $nowName = TaxonName::find($currentUsage->taxon_name_id);
        $nomenclatureId = $nowName->nomenclature_id;

        if (empty($parent) && $nomenclatureId != 4){

            $speciesLayer = $nowName->properties['species_layers'];

            if (count($speciesLayer) == 1) {
                $parent = $nowName->properties['species_id'];
            } else if (count($speciesLayer) == 2){
                $parentTaxonNameString = $nowName->properties['latin_genus'] . ' '  . $nowName->properties['latin_s1'];
                $parentTaxonNameString .= ' ' . $speciesLayer[0]['rank_abbreviation'] . ' ' . $speciesLayer[0]['latin_name'];
        
                $parent = TaxonName::where('name', $parentTaxonNameString)
                                    ->where('nomenclature_id', $nomenclatureId)
                                    ->first()->id;
            } else if ($nowName->rank_id == 34) {
                // 種
                $parentTaxonNameString = $nowName->properties['latin_genus'];

                $parent_query = TaxonName::where('name', $parentTaxonNameString)
                                    ->where('nomenclature_id', $nomenclatureId);
                if ($parent_query->count() > 0){
                    $parent = $parent_query->first()->id;
                }

            }
        }

        $currentUsage->parent_taxon_name_id = $parent;

        $currentUsage->properties = $usage['properties'];
        $currentUsage->save();

        // 這邊要加上編輯log


        $edit_log = new ImportUsageLog();
        $edit_log->reference_usage_id = $currentUsage->id;
        $edit_log->action = ImportUsageLog::ACTION_COMMON_NAME_UPDATE;
        $edit_log->reference_id = $currentUsage->reference_id;
        $edit_log->taxon_name_id = $currentUsage->taxon_name_id;
        $edit_log->user_id = $request->user()->id;

        $edit_log->save();

        try {

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


    public function getUncheckedUsage(Request $request) {

        $rows = DB::table('api_usage_check')->where('is_checked',0)->where('error_type','!=',11)->get();
        $lastUpdated = DB::table('api_usage_check')->max('updated_at');

        return response()->json([
            'usages' => $rows,
            'last_updated' => $lastUpdated
        ]);
        
    }

    public function updateUsageCheck(Request $request) {

        $url = "https://api.taicol.tw/v2/update_check_usage";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($curl);

        $jsonResult = json_decode($result, true);

        if ($jsonResult['status']['code']==200){
            return response()->json([
                'message' => 'done'
            ]);
        } else {
            return response()->json([
                'message' => 'fail'
            ]);

        }

    }

    private function snakeToCamel($input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

}
