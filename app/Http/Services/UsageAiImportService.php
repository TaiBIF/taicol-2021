<?php

namespace App\Http\Services;

use App\MyNamespaceUsage;
use App\TaxonName;
use App\Reference;
use App\Rank;
use App\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Utils\CommonNameArray;

class UsageAiImportService
{
    private $errorItems = [];
    private int $namespaceId;
    private $rankMapping;

    private $statusMapping = [
        'accepted',
        'not-accepted', 
        'misapplied',
        'undetermined',
    ];

    private $alienTypeMapping = [
        'native',
        'naturalized', 
        'invasive',
        'cultured',
    ];

    private $languageMapping = [
        'en-us' => 'en-us',
        'zh-tw' => 'zh-tw',
        'ja-jp' => 'ja-jp', 
        'zh-cn' => 'zh-cn',
        'de-de' => 'de-de',
        'fr-fr' => 'fr-fr',
        'lat' => 'lat',
        'others' => 'others',
    ];

    private $nomenclatureMapping = [
        'ICZN' => 1,
        'ICN' => 2,
        'ICNP' => 3,
        'ICVCN' => 4,
    ];

    public function __construct()
    {
        // $this->namespaceId = $namespaceId;
        $this->rankMapping = Rank::all()->keyBy('key');
    }



    public function processScientificNames(array $scientificNamesData): array
    {

        foreach ($scientificNamesData['scientific_names'] as &$scientificName) {
            // 先取得需要的ID值
            $nomenclatureId = $this->nomenclatureMapping[$scientificName['nomenclature']];
            $rankId = $this->rankMapping[strtolower($scientificName['rank'])]->id;
            
            // 比對學名 (只用 nomenclature + rank + name)
            $taxonNameId = $this->findMatchingTaxonName(
                $nomenclatureId,
                $rankId,
                $scientificName['latin_name']
            );
            
            // 新增taxon_name_id欄位
            $scientificName['taxon_name_id'] = $taxonNameId;
        }
        
        return $scientificNamesData;
    }

    private function findMatchingTaxonName(int $nomenclatureId, int $rankId, string $name): int|null
    {
        $taxonName = TaxonName::query()
            ->where('nomenclature_id', $nomenclatureId)
            ->where('rank_id', $rankId)
            ->where('name', $name)
            ->first();

        return $taxonName ? $taxonName->id : null;
    }

    public function countUnmatchedScientificNames(array $scientificNamesData): int
    {
        $unmatchedCount = 0;
        
        foreach ($scientificNamesData['scientific_names'] as $scientificName) {
            if (empty($scientificName['taxon_name_id'])) {
                $unmatchedCount++;
            }
        }
        
        return $unmatchedCount;
    }

    public function getUnmatchedScientificNames(array $scientificNamesData): array
    {
        $unmatchedNames = [];
        $seen = []; 
        
        foreach ($scientificNamesData['scientific_names'] as $index => $scientificName) {
            if (empty($scientificName['taxon_name_id'])) {
                // 建立唯一值 (nomenclature + rank + latin_name)
                $uniqueKey = ($this->nomenclatureMapping[$scientificName['nomenclature']] ?? '') . '|' . 
                            ($scientificName['rank'] ?? '') . '|' . 
                            ($scientificName['latin_name'] ?? '');
                
                // 檢查是否已存在相同組合
                if (!isset($seen[$uniqueKey])) {
                    $unmatchedNames[] = [
                        'nomenclature' => $this->nomenclatureMapping[$scientificName['nomenclature']] ?? null,
                        'kingdom' => $scientificName['kingdom'] ?? null,
                        'rank' => $scientificName['rank'] ?? null,
                        'latin_name' => $scientificName['latin_name'] ?? null,
                        'latin_genus' => $scientificName['latin_genus'] ?? null,
                        'latin_s1' => $scientificName['latin_s1'] ?? null,
                        's2_rank' => $scientificName['s2_rank'] ?? null,
                        'latin_s2' => $scientificName['latin_s2'] ?? null,
                        'formatted_authors' => $scientificName['formatted_authors'] ?? null,
                        // 'index' => $scientificName['index'],
                        'original_name' => $scientificName['latin_name'] ?? null,
                    ];
                    
                    $seen[$uniqueKey] = true;
                }
            }
        }
        
        return $unmatchedNames;
    }

    public function handle(array $scientificNamesData, int $namespaceId): int
    {
        DB::beginTransaction();
        $this->namespaceId = $namespaceId;

        $count = 0;
        $lastGroupUsage = MyNamespaceUsage::where('namespace_id', $this->namespaceId)
            ->orderBy('group', 'desc')
            ->first();

        $group = $lastGroupUsage->group ?? 0;
        $order = 0;

        try {
            foreach ($scientificNamesData['scientific_names'] as $index => $scientificName) {
                
                // 檢查是否有配對到 taxon_name_id
                if (empty($scientificName['taxon_name_id'])) {
                    // $this->throwError($index, '此學名未找到對應的 TaxonName');
                    continue;
                }

                $taxonName = TaxonName::find($scientificName['taxon_name_id']);
                if (!$taxonName) {
                    // $this->throwError($index, 'TaxonName ID 不存在');
                    continue;
                }

                // 處理基本欄位
                $status = $scientificName['status'] ?? 'accepted';
                $isIndent = $scientificName['is_indent'] ?? 0;
                $isInTaiwan = $scientificName['is_in_taiwan'] ?? null;
                $distributionTw = $scientificName['distribution_in_tw'] ?? null;
                $isEndemic = $scientificName['is_endemic'] ?? null;
                $alienType = $scientificName['alien_type'] ?? null;
                $isFossil = $scientificName['is_fossil'] ?? null;
                $isTerrestrial = $scientificName['is_terrestrial'] ?? null;
                $isFreshwater = $scientificName['is_freshwater'] ?? null;
                $isBrackish = $scientificName['is_brackish'] ?? null;
                $isMarine = $scientificName['is_marine'] ?? null;
                $alienStatusNote = $scientificName['alien_status_note'] ?? null;
                $isNewRecord = $scientificName['is_new_record'] ?? null;
                $note = $scientificName['note'] ?? null;

                // 驗證欄位
                if ($status && !in_array($status, $this->statusMapping)) {
                    $this->throwError($index, 'usage_status 錯誤');
                }

                if ($alienType && !in_array($alienType, $this->alienTypeMapping)) {
                    $this->throwError($index, 'alien_type 錯誤');
                }

                if ($index === 0 && ($isIndent === 1 || $status === 'not-accepted') && $group === 0) {
                    $this->throwError($index, '第一筆不能是無效名或縮排');
                }

                if (!$isIndent) {
                    $group++;
                    $order = 0;
                } else {
                    $order++;
                }

                // 處理 parent taxon // 目前應該是沒有這個parent_taxon的欄位
                $parentTaxonNameId = $this->processParentTaxon($taxonName->id);

                // 處理 common names
                $commonNames = $this->processCommonNames($scientificName['common_name'] ?? '');

                // 處理 usage references
                $perUsages = $this->processUsageReferences($scientificName['usage_references'] ?? null, $index);

                // 處理 indications
                $checkedIndications = $this->processIndications($scientificName['indications'] ?? [], $index);

                // 處理 additional fields
                $additionalFields = $this->processAdditionalFields($scientificName);

                // 處理 custom fields  
                $customFields = $this->processCustomFields($scientificName);

                // 建立 properties
                $properties = [
                    'is_fossil' => $isFossil !== null ? ($isFossil ? 1 : 0) : null,
                    'is_marine' => $isMarine !== null ? ($isMarine ? 1 : 0) : null,
                    'is_brackish' => $isBrackish !== null ? ($isBrackish ? 1 : 0) : null,
                    'common_names' => $commonNames,
                    'note' => $note,
                    'is_in_taiwan' => $isInTaiwan !== null ? (int)$isInTaiwan : null,
                    'is_freshwater' => $isFreshwater !== null ? ($isFreshwater ? 1 : 0) : null,
                    'is_terrestrial' => $isTerrestrial !== null ? ($isTerrestrial ? 1 : 0) : null,
                    'additional_fields' => $additionalFields,
                    'custom_fields' => $customFields,
                    'indications' => $checkedIndications,
                ];

                if ($isInTaiwan == 1) {
                    $properties['is_endemic'] = $isEndemic !== null ? ($isEndemic ? 1 : 0) : null;
                    $properties['distribution_in_tw'] = $distributionTw;
                    $properties['is_new_record'] = $isNewRecord !== null ? ($isNewRecord ? 1 : 0) : null;
                    $properties['alien_type'] = $alienType;
                    $properties['alien_status_note'] = $alienStatusNote;
                }

                $this->saveUsage($taxonName, $parentTaxonNameId, $properties, $group, $order, $perUsages, $scientificName);
                $count++;
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $count;
    }

    private function processParentTaxon(int $taxon_name_id)
    {
        // if (!$parentTaxonNameString) {
        //     return null;
        // }

        // 優先採用usage

        $parent = null;

        $parent = DB::table('accepted_usages')
            ->select('parent_taxon_name_id')
            ->where('taxon_name_id', $taxon_name_id)
            ->first();


        $parent = $parent->parent_taxon_name_id ?? null;

        $nowName = TaxonName::find($taxon_name_id);
        $nomenclatureId = $nowName->nomenclature_id;

        if (empty($parent) && $nomenclatureId != 4){

            $speciesLayer = $nowName->properties['species_layers'];

            if (count($speciesLayer) == 1) {
                $parent = $nowName->properties['species_id'];
            } else if (count($speciesLayer) == 2){
                $parentTaxonNameString = $nowName->properties['latin_genus'] . ' '  . $nowName->properties['latin_s1'];
                $parentTaxonNameString .= ' ' . $speciesLayer[0]['rank_abbreviation'] . ' ' . $speciesLayer[0]['latin_name'];
        
                $parent_query = TaxonName::where('name', $parentTaxonNameString)
                                    ->where('nomenclature_id', $nomenclatureId);
                if ($parent_query->count() > 0){
                    $parent = $parent_query->first()->id;
                }
            
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


        // $parentTaxonNames = TaxonName::query()->where('name', $parentTaxonNameString)->get();
        
        // if ($parentTaxonNames->count() === 1) {
        //     return $parentTaxonNames->first();
        // } else if ($parentTaxonNames->count() > 1) {
        //     // 需要更多資訊來區分，這裡簡化處理
        //     return $parentTaxonNames->first();
        // }
        
        return $parent;
    }

    private function processCommonNames(string $commonNameString): array
    {
        if (empty($commonNameString)) {
            return [];
        }

        $commonNames = [];
        $commonNamesStrings = explode('|', $commonNameString);

        foreach ($commonNamesStrings as $commonNamesString) {
            $isMatch = preg_match('/(.*)\(([^,\)]+)(?:,([^)]*))?\)/', $commonNamesString, $matches);
            
            if ($isMatch) {
                $name = $matches[1];
                foreach (array_keys(CommonNameArray::get()) as $cc_key) {
                    $name = str_replace($cc_key, CommonNameArray::get()[$cc_key], $name);
                }

                $commonNames[] = [
                    'area' => isset($matches[3]) ? $matches[3] : null,
                    'name' => trim(str_replace("\x00", "", $name)),
                    'language' => $this->languageMapping[$matches[2]] ?? 'others',
                ];
            }
        }

        return $commonNames;
    }
    
    private function processUsageReferences(?string $usageReferences, int $index): array
    {
        if (!$usageReferences) {
            return [];
        }

        $perUsages = [];
        $usageReferencesArray = explode("|", $usageReferences);

        foreach ($usageReferencesArray as $usageReference) {
            $usageReference = str_getcsv($usageReference);

            if (count($usageReference) == 4) {
                if (!Reference::find($usageReference[0])) {
                    $this->throwError($index, 'usage_references提供之文獻ID查無文獻');
                }

                $nowUsage = ['reference_id' => $usageReference[0]];

                if ($usageReference[1] !== '') {
                    $nowUsage['show_page'] = $usageReference[1];
                }

                if ($usageReference[2] !== '') {
                    $nowUsage['figure'] = $usageReference[2];
                }

                if ($usageReference[3] === 'true') {
                    $nowUsage['pro_parte'] = true;
                    $nowUsage['pro_parte_type'] = 'pro parte';
                }

                $perUsages[] = $nowUsage;
            }
        }

        return $perUsages;
    }

    private function processIndications(array $indications, int $index): array
    {
        if (empty($indications)) {
            return [];
        }

        $validIndications = json_decode(file_get_contents(resource_path('json/indications.json')), true);
        $validIndications = collect($validIndications)->pluck('abbreviation')->all();
        
        return array_values(array_filter($indications, function($indication) use ($validIndications) {
            return in_array($indication, $validIndications);
        }));
    }

    private function processAdditionalFields(array $scientificName): array
    {
        $additionalFields = [];
        $fields = ['description', 'diagnosis', 'distribution', 'etymology', 'habitat', 'substrata', 'measurements', 'coloration', 'other_examined_material'];

        foreach ($fields as $field) {
            if (isset($scientificName[$field]) && $scientificName[$field]) {
                $fieldName = $field === 'other_examined_material' ? 'otherExaminedMaterial' : $field;
                
                $additionalFields[] = [
                    'field_value' => $scientificName[$field],
                    'field_name' => $fieldName,
                ];
            }
        }

        return $additionalFields;
    }

    private function processCustomFields(array $scientificName): array
    {
        $customFields = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $fieldKey = "custom_field{$i}";
            if (isset($scientificName[$fieldKey]) && $scientificName[$fieldKey]) {
                $parts = explode(':', $scientificName[$fieldKey], 2);
                $firstPart = $parts[0] ?? '';
                $secondPart = $parts[1] ?? '';

                if ($firstPart && $secondPart) {
                    $customFields[] = [
                        'field_name_en' => $firstPart,
                        'field_value' => $secondPart,
                    ];
                }
            }
        }

        return $customFields;
    }

    private function processTypeSpecimens($typeSpecimens): array
    {
        if (empty($typeSpecimens) || !is_array($typeSpecimens)) {
            return [];
        }

        $processedSpecimens = [];

        foreach ($typeSpecimens as $specimen) {

            // 處理 country_id 轉換
            $countryId = null;
            if (isset($specimen['country']) && $specimen['country']) {
                $country = Country::where('display->en-us', $specimen['country'])->first();
                $countryId = $country ? $country->numeric_code : null;
            }

            $processedSpecimen = [
                "id" => null,
                "sex" => null,
                "url" => null,
                "use" => $specimen['use'] ?? null,
                "kind" => $specimen['kind'] ?? 1,
                "country_id" => $countryId,
                "locality" => $specimen['locality'] ?? null,
                "specimens" => [
                    [
                        "url" => null,
                        "herbarium" => $specimen['herbarium'] ?? null,
                        "accession_number" => $specimen['accession_number'] ?? null
                    ]
                ],
                "collectors" => [], // 先不取
                "collector_ids" => [], // 先不取
                "is_designated" => false,
                "collection_day" => $specimen['collection_day'] ?? null,
                "collection_year" => $specimen['collection_year'] ?? null,
                "lecto_cite_page" => null,
                "collection_month" => $specimen['collection_month'] ?? null,
                "collector_number" => null,
                "locality_verbatim" => null,
                "citation_note_number" => null,
                "lecto_designated_reference" => null
            ];

            $processedSpecimens[] = $processedSpecimen;
        }

        return $processedSpecimens;
    }

    private function saveUsage(TaxonName $taxonName, ?int $parentTaxonNameId, array $properties, int $group, int $order, array $perUsages, array $scientificName)
    {
        $usage = new MyNamespaceUsage();
        $usage->namespace_id = $this->namespaceId;
        $usage->taxon_name_id = $taxonName->id;
        $usage->parent_taxon_name_id = $parentTaxonNameId;
        $usage->status = $scientificName['status'] ?? 'accepted';
        $usage->name_remark = '';
        $usage->custom_name_remark = '';
        $usage->properties = $properties;
        $usage->per_usages = $perUsages;
        $usage->is_indent = $scientificName['is_indent'] ?? 0;
        $usage->is_title = false;
        $usage->order = $order;
        $usage->group = $group;
        $usage->type_specimens = $this->processTypeSpecimens($scientificName['type_specimens'] ?? []);
        
        $usage->save();
    }

    private function throwError(int $index, string $message)
    {
        $this->errorItems[$index] = ['message' => $message];
        throw new \Exception("第 {$index} 筆資料錯誤: {$message}");
    }

    public function getErrorItems()
    {
        return $this->errorItems;
    }
}