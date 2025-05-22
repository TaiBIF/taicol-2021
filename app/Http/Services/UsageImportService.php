<?php


namespace App\Http\Services;


use App\MyNamespaceUsage;
use App\Nomenclature;
use App\Rank;
use App\TaxonName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Log;
use App\Http\Utils\CommonNameArray;
use App\Reference;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class UsageImportService
{
    private $duplicateRows = []; // 與資料庫比對 unique key 重複
    private $repeatRows = []; // 檔案內部重複
    private $errorRows = [];
    private $validRows = [];
    private $warningRows = []; //

    private Collection $ranks;
    private int $namespaceId;

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

    private $kingdomMapping = [
        'Plantae',
        'Animalia',
        'Chromista',
    ];

    private $languageMapping = [
        '英文' => 'en-us',
        '繁體中文' => 'zh-tw',
        '日文' => 'jp-jp',
        '簡體中文' => 'zh-cn',
        '德文' => 'de-de',
        '法文' => 'fr-fr',
        '拉丁文' => 'lat',
        '其他' => 'others',
    ];

    public function __construct(Worksheet $sheet, int $namespaceId)
    {
        $this->sheet = $sheet;
        $this->namespaceId = $namespaceId;
    }

    public function handle()
    {
        $this->validateSheetRows();

        $this->ranks = Rank::all();

        DB::beginTransaction();

        $count = 0;
        $lastGroupUsage = MyNamespaceUsage::where('namespace_id', $this->namespaceId)
            ->orderBy('group', 'desc')
            ->first();

        $group = $lastGroupUsage->group ?? 0;
        $order = 0;
        $nomenclatures = Nomenclature::select('id', 'name')->get()->keyBy('name');
        $ranks = Rank::select('id', 'key')->get()->keyBy('key');

        // 讀取欄位名稱（第一列）

        $highestColumn = $this->sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        
        
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = $this->sheet->getCellByColumnAndRow($col, 1)->getValue();
            $headers[$header] = $col;
        }

        try {
            for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {

                $nomenclature = $this->sheet->getCellByColumnAndRow($headers['nomenclature'], $row)->getCalculatedValue();
                $rank = $this->sheet->getCellByColumnAndRow($headers['rank'], $row)->getCalculatedValue();
                $name = $this->sheet->getCellByColumnAndRow($headers['name'], $row)->getCalculatedValue();
                $authorsString = $this->sheet->getCellByColumnAndRow($headers['authors'], $row)->getCalculatedValue();
                $parentTaxonNameString = $this->sheet->getCellByColumnAndRow($headers['parant_taxon'], $row)->getCalculatedValue();
                $status = $this->sheet->getCellByColumnAndRow($headers['usage_status'], $row)->getCalculatedValue();
                $commonNamesStrings = $this->sheet->getCellByColumnAndRow($headers['common_name'], $row)->getCalculatedValue();

                $isInTaiwan = $this->sheet->getCellByColumnAndRow($headers['is_in_taiwan'], $row)->getCalculatedValue();
                $distributionTw = $this->sheet->getCellByColumnAndRow($headers['distribution_in_tw'], $row)->getCalculatedValue();
                $isEndemic = $this->sheet->getCellByColumnAndRow($headers['is_endemic'], $row)->getCalculatedValue();
                $alienType = $this->sheet->getCellByColumnAndRow($headers['alien_type'], $row)->getCalculatedValue();
                $isFossil = $this->sheet->getCellByColumnAndRow($headers['is_fossil'], $row)->getCalculatedValue();
                $isTerrestrial = $this->sheet->getCellByColumnAndRow($headers['is_terrestrial'], $row)->getCalculatedValue();
                $isFreshwater = $this->sheet->getCellByColumnAndRow($headers['is_freshwater'], $row)->getCalculatedValue();
                $isBrackish = $this->sheet->getCellByColumnAndRow($headers['is_brackish'], $row)->getCalculatedValue();
                $isMarine = $this->sheet->getCellByColumnAndRow($headers['is_marine'], $row)->getCalculatedValue();
                $alienStatusNote = $this->sheet->getCellByColumnAndRow($headers['alien_status_note'], $row)->getCalculatedValue();
                $isNewRecord = $this->sheet->getCellByColumnAndRow($headers['is_new_record'], $row)->getCalculatedValue();

                $isIndent = (bool) $this->sheet->getCellByColumnAndRow($headers['is_indent'], $row)->getCalculatedValue();

                // 2025 05 新增
                $note = $this->sheet->getCellByColumnAndRow($headers['note'], $row)->getCalculatedValue();

                $usageReferences = $this->sheet->getCellByColumnAndRow($headers['usage_references'], $row)->getCalculatedValue();

                $perUsages = [];
                if (isset($usageReferences)){
                    $usageReferences = explode("|", $usageReferences);

                    foreach ($usageReferences as $usageReference){
                        // 用str_getcsv 可以用逗號分隔 並忽略雙引號內的逗號
                        $usageReference = str_getcsv($usageReference);

                        if (count($usageReference)==4){
                            // 要確定reference_id有沒有存在在資料庫中
                            if (Reference::find($usageReference[0])->get()){

                                $now_usage = array();
                                $now_usage['reference_id'] = $usageReference[0];

                                if ($usageReference[1] !== ''){
                                    $now_usage['show_page'] = $usageReference[1];
                                }

                                if ($usageReference[2] !== ''){
                                    $now_usage['figure'] = $usageReference[2];
                                }

                                if ($usageReference[3] === 'true'){
                                    $now_usage['pro_parte'] = true;
                                    $now_usage['pro_parte_type'] = 'pro parte';
                                };

                                array_push($perUsages, $now_usage);

                            } else {
                                $this->throwError($row, 'usage_references提供之文獻ID查無文獻');
                            };

                        }

                    }

                } 


                // 確認indications有沒有在清單中
                $indications = $this->sheet->getCellByColumnAndRow($headers['indications'], $row)->getCalculatedValue();
                
                if (isset($indications)){
                    $indications = explode("|", $indications);
                    $validIndications = json_decode(file_get_contents(resource_path('json/indications.json')), true);

                    $validIndications = collect($validIndications)->pluck('abbreviation')->all();
                    $checkedIndications = array_values(array_intersect($indications, $validIndications));
                    $absent = array_values(array_diff($indications, $validIndications));

                    if (count($absent) > 0){
                        $this->throwError($row, '不合法的標註: ' . implode(",",$absent));
                    }

                } else {
                    $checkedIndications = [];
                }


                $additionalFields = [];

                foreach (['description','diagnosis','distribution','etymology','habitat','substrata','measurements','coloration','other_examined_material'] as $add){
                    $val = $this->sheet->getCellByColumnAndRow($headers[$add], $row)->getCalculatedValue();

                    if (isset($val)){
                        if ($add == 'other_examined_material'){
                            $add = 'otherExaminedMaterial';
                        }

                        $now_dict = array();
                        $now_dict['field_value'] = $val;
                        $now_dict['field_name'] = $add;
                        
                        array_push($additionalFields, $now_dict);
                    }
                }

                $customFields = [];

                foreach (['custom_field1','custom_field2','custom_field3','custom_field4','custom_field5'] as $cus){
                    $val = $this->sheet->getCellByColumnAndRow($headers[$cus], $row)->getCalculatedValue();

                    if (isset($val)){

                        $parts = explode(':', $val, 2);
                        $firstPart = $parts[0];
                        $secondPart = $parts[1] ?? '';


                        if (isset($firstPart) && isset($secondPart)){

                            $now_dict = array();
                            $now_dict['field_name_en'] = $firstPart ;
                            $now_dict['field_value'] = $secondPart;
                            
                            array_push($customFields, $now_dict);
                        } else {
                            $this->throwError($row, '不正確的' . $cus .'格式');
                        }
                    }
                }


                if ($row === 2 && ($isIndent === true || $status === 'not-accepted') && $group === 0) {
                    $this->throwError($row, '第一筆不能是無效名或縮排');
                }

                $taxonNames = TaxonName::query()->where('name', $name)->get();

                if ($taxonNames->count() > 1) {
                    $nomenclatureId = $nomenclatures[$nomenclature]->id;
                    $taxonNamesQuery = TaxonName::query()
                        ->where('nomenclature_id', $nomenclatureId)
                        ->where('rank_id', $ranks[$rank]->id)
                        ->where('name', $name);

                    if ($authorsString) {
                        $taxonNamesQuery->where('formatted_authors', $authorsString);
                    }

                    $taxonNames = $taxonNamesQuery->get();
                }

                if ($taxonNames->count() > 1) {
                    $this->throwError($row, '此 Taxon 有同名，請提供作者名輔助');
                } else if ($taxonNames->count() === 1) {
                    $taxonName = $taxonNames->first();
                } else {
                    $taxonName = null;
                }

                if (!$taxonName) {
                    $this->throwError($row, '查無此 Taxon');
                }

                if (!$isIndent) {
                    $group++;
                    $order = 0;
                } else {
                    $order++;
                }

                $parentTaxonName = null;
                if ($parentTaxonNameString) {
                    $parentTaxonNames = TaxonName::query()->where('name', $parentTaxonNameString)->get();

                    if ($parentTaxonNames->count() > 1) {
                        $nomenclatureId = $nomenclatures[$nomenclature]->id;
                        $parentTaxonName = TaxonName::query()
                            ->where('nomenclature_id', $nomenclatureId)
                            ->where('name', $parentTaxonNameString)
                            ->first();
                    } else if ($parentTaxonNames->count() === 1) {
                        $parentTaxonName = $parentTaxonNames->first();
                    } else {
                        $this->throwError($row, '查無此 Parent Taxon');
                    }
                }

                $commonName  = [];

                if (isset($commonNamesStrings)){

                    $commonNamesStrings = explode('|', $commonNamesStrings);

                    foreach ($commonNamesStrings as $commonNamesString){

                        $isMatch = preg_match('/(.*)\((.*),(.*)\)/', $commonNamesString, $matches);

                        $name = $matches[1];
                        foreach (array_keys(CommonNameArray::get()) as $cc_key) {
                            $name = str_replace($cc_key,CommonNameArray::get()[$cc_key],$name);
                        };

                        if ($isMatch){
                            array_push($commonName, [
                                'area' => $matches[3],
                                'name' => trim(str_replace("\x00", "", $name)),
                                'language' => $this->languageMapping[$matches[2]],
                            ]);
                        }
                    }
                }

                $isInTaiwan = !isset($isInTaiwan) || $isInTaiwan === '' ? null : (int)$isInTaiwan;

                $properties = [
                    'is_fossil' => !isset($isFossil) || $isFossil === '' ? null : ($isFossil ? 1 : 0),
                    'is_marine' => !isset($isMarine) || $isMarine === '' ? null : ($isMarine ? 1 : 0),
                    'is_brackish' => !isset($isBrackish) || $isBrackish === '' ? null : ($isBrackish ? 1 : 0),
                    'common_names' => !isset($commonName) ? null : ($commonName),
                    'note' => !isset($note) ? null : $note,
                    'is_in_taiwan' => $isInTaiwan,
                    'is_freshwater' => !isset($isFreshwater) || $isFreshwater === '' ? null : ($isFreshwater ? 1 : 0),
                    'is_terrestrial' => !isset($isTerrestrial) || $isTerrestrial === '' ? null : ($isTerrestrial ? 1 : 0),
                    'additional_fields' => $additionalFields,
                    'custom_fields' => $customFields,
                    'indications' => $checkedIndications,
                ];


                if ($isInTaiwan == 1) {
                    $properties['is_endemic'] = !isset($isEndemic) || $isEndemic === '' ? null : ($isEndemic ? 1 : 0);
                    $properties['distribution_in_tw'] = $distributionTw;
                    $properties['is_new_record'] = !isset($isNewRecord) || $isNewRecord === '' ? null : ($isNewRecord ? true : false);
                    $properties['alien_type'] = $alienType;
                    $properties['alien_status_note'] = $alienStatusNote;
                }

                $this->saveUsages($row, $taxonName, $parentTaxonName ?? null, $properties, $group, $order, $perUsages);
                $count++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->throwError($row, $e->getMessage());
        }


        return $count;
    }

    private function validateSheetRows()
    {
        $sheet = $this->sheet;


        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        
        
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = $sheet->getCellByColumnAndRow($col, 1)->getValue();
            $headers[$header] = $col;
        }


        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nomenclature = $sheet->getCellByColumnAndRow($headers['nomenclature'], $row)->getCalculatedValue();
            $rank = $sheet->getCellByColumnAndRow($headers['rank'], $row)->getCalculatedValue();
            $name = $sheet->getCellByColumnAndRow($headers['name'], $row)->getCalculatedValue();
            // $authorNamesString = $sheet->getCell('D' . $row)->getCalculatedValue();
            $usageStatus = $sheet->getCellByColumnAndRow($headers['usage_status'], $row)->getCalculatedValue();
            // $isIndent = (bool) $this->sheet->getCell('H' . $row)->getCalculatedValue();
            $commonNames = $sheet->getCellByColumnAndRow($headers['common_name'], $row)->getCalculatedValue();
            $alienType = $sheet->getCellByColumnAndRow($headers['alien_type'], $row)->getCalculatedValue();

            if (!$nomenclature) {
                $this->throwError($row, 'nomenclature 未填寫');
            }

            if (!$rank) {
                $this->throwError($row, 'rank 未填寫');
            }

            if (!$name) {
                $this->throwError($row, 'name 未填寫');
            }

            if ($usageStatus && !in_array($usageStatus, $this->statusMapping)) {
                $this->throwError($row, 'usage_status 錯誤');
            }

            if ($alienType && !in_array($alienType, $this->alienTypeMapping)) {
                $this->throwError($row, 'alien_type 錯誤');
            }

            $isMatch = !!preg_match('/(.*)\((.*),(.*)\)/', $commonNames, $matches);
            if ($commonNames && !$isMatch) {
                $this->throwError($row, 'common_name 錯誤');
            }

            if ($commonNames && $isMatch && !isset($this->languageMapping[$matches[2]])) {
                $this->throwError($row, 'common_name language 錯誤');
            }

            $this->maxHighRows = $row;
        }
    }

    private function throwError(int $row, string $message)
    {
        $this->errorRows[$row - 1] = ['message' => $message];
        throw new \Exception($message);
    }

    private function saveUsages(int $row, $taxonName, ?object $parentTaxonName, $properties, $group, $order, $perUsages)
    {

        $usage = new MyNamespaceUsage();
        $usage->namespace_id = $this->namespaceId;

        $isTitle = (bool) $this->sheet->getCell('G' . $row)->getCalculatedValue();
        $isIndent = (bool) $this->sheet->getCell('H' . $row)->getCalculatedValue();

        $usage->taxon_name_id = $taxonName->id;

        if ($isTitle) {
            $usage->parent_taxon_name_id = null;
            $usage->status = '';
            $usage->name_remark = '';
            $usage->custom_name_remark = '';
            $usage->properties = [];
            $usage->per_usages = [];
        } else {
            $usage->parent_taxon_name_id = $parentTaxonName->id ?? null;
            $usage->status = $this->sheet->getCell('F' . $row)->getCalculatedValue();
            $usage->name_remark = '';
            $usage->custom_name_remark = '';
            $usage->properties = $properties;
            $usage->per_usages = [];
        }

        $usage->is_indent = $isIndent;
        $usage->is_title = $isTitle;
        $usage->order = $order;
        $usage->group = $group;
        $usage->type_specimens = [];
        $usage->per_usages = $perUsages;
        $usage->save();
    }

    public function getErrorRows()
    {
        return [
            'repeat_rows' => $this->repeatRows,
            'duplicate_rows' => $this->duplicateRows,
            'error_rows' => $this->errorRows,
            'warning_rows' => $this->warningRows,
            'valid_rows' => $this->validRows,
        ];
    }
}
