<?php


namespace App\Http\Services;


use App\MyNamespaceUsage;
use App\Nomenclature;
use App\Rank;
use App\TaxonName;
use App\Reference;
use App\Exceptions\ImportRowException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Http\Utils\CommonNameArray;

class UsageImportService
{
    private $duplicateRows = []; // 與資料庫比對 unique key 重複
    private $repeatRows = [];    // 檔案內部重複
    private $errorRows = [];
    private $validRows = [];
    private $warningRows = [];

    private Worksheet $sheet;
    private int $namespaceId;

    private array $columnMap = [];        // 欄名 => 數字索引（1 起算）
    private array $resolved = [];         // 列號 => 驗證階段解析結果，save 重用
    private int $baseGroup = 0;           // 該 namespace 現有最後一個 group
    private int $groupCursor = 0;
    private int $orderCursor = 0;

    private Collection $nomenclatures;    // key: name
    private Collection $rankMap;          // key: key

    /** @var callable|null  function(string $phase, int $done) */
    public $onProgress = null;

    private function reportProgress(string $phase, int $done): void
    {
        if (is_callable($this->onProgress)) {
            ($this->onProgress)($phase, $done);
        }
    }

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

    // ---- 讀取工具：一律用欄名 ----

    private function buildColumnMap(): void
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($this->sheet->getHighestColumn());
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = $this->sheet->getCellByColumnAndRow($col, 1)->getValue();
            $header = trim((string) $header);
            if ($header !== '') {
                $this->columnMap[$header] = $col;
            }
        }
    }

    /** 依欄名讀取；欄位不存在回 null（交由後續邏輯以空值處理） */
    private function cell(int $row, string $columnName)
    {
        $index = $this->columnMap[$columnName] ?? null;
        if ($index === null) {
            return null;
        }
        return $this->sheet->getCellByColumnAndRow($index, $row)->getValue();
    }

    // ---- 驗證階段（唯讀，逐列收集全部錯誤） ----

    public function validate(): int
    {
        $this->buildColumnMap();

        $this->nomenclatures = Nomenclature::select('id', 'name')->get()->keyBy('name');
        $this->rankMap = Rank::select('id', 'key')->get()->keyBy('key');

        $lastGroupUsage = MyNamespaceUsage::where('namespace_id', $this->namespaceId)
            ->orderBy('group', 'desc')
            ->first();
        $this->baseGroup = $lastGroupUsage->group ?? 0;
        $this->groupCursor = $this->baseGroup;
        $this->orderCursor = 0;

        $highest = $this->sheet->getHighestRow();
        $done = 0;
        for ($row = 2; $row <= $highest; $row++) {
            try {
                $this->validateRow($row);
            } catch (ImportRowException $e) {
                // 該列第一個錯誤已記錄，繼續下一列
            }
            $done++;
            if ($done % 100 === 0) {
                $this->reportProgress('validating', $done);
            }
        }
        $this->reportProgress('validating', $done);

        return count($this->errorRows);
    }

    private function validateRow(int $row): void
    {
        $nomenclature = $this->cell($row, 'nomenclature');
        $rank = $this->cell($row, 'rank');
        $name = $this->cell($row, 'name');
        $authorsString = $this->cell($row, 'authors');
        $parentTaxonNameString = $this->cell($row, 'parant_taxon');
        $status = $this->cell($row, 'usage_status');
        $commonNamesStrings = $this->cell($row, 'common_name');

        $isInTaiwan = $this->cell($row, 'is_in_taiwan');
        $distributionTw = $this->cell($row, 'distribution_in_tw');
        $isEndemic = $this->cell($row, 'is_endemic');
        $alienType = $this->cell($row, 'alien_type');
        $isFossil = $this->cell($row, 'is_fossil');
        $isTerrestrial = $this->cell($row, 'is_terrestrial');
        $isFreshwater = $this->cell($row, 'is_freshwater');
        $isBrackish = $this->cell($row, 'is_brackish');
        $isMarine = $this->cell($row, 'is_marine');
        $alienStatusNote = $this->cell($row, 'alien_status_note');
        $isNewRecord = $this->cell($row, 'is_new_record');
        $note = $this->cell($row, 'note');

        $isIndent = (bool) $this->cell($row, 'is_indent');
        $isTitle = (bool) $this->cell($row, 'is_title');

        // --- 基本欄位檢查（原 validateSheetRows）---
        if (!$nomenclature) {
            $this->throwError($row, 'nomenclature 未填寫');
        }
        if (!$rank) {
            $this->throwError($row, 'rank 未填寫');
        }
        if (!$name) {
            $this->throwError($row, 'name 未填寫');
        }
        if ($status && !in_array($status, $this->statusMapping)) {
            $this->throwError($row, 'usage_status 錯誤');
        }
        if ($alienType && !in_array($alienType, $this->alienTypeMapping)) {
            $this->throwError($row, 'alien_type 錯誤');
        }
        $commonNameFormatMatch = !!preg_match('/(.*)\((.*),(.*)\)/', (string) $commonNamesStrings, $cnMatch);
        if ($commonNamesStrings && !$commonNameFormatMatch) {
            $this->throwError($row, 'common_name 錯誤');
        }
        if ($commonNamesStrings && $commonNameFormatMatch && !isset($this->languageMapping[$cnMatch[2]])) {
            $this->throwError($row, 'common_name language 錯誤');
        }

        // --- usage_references ---
        $perUsages = [];
        $usageReferences = $this->cell($row, 'usage_references');
        if (isset($usageReferences)) {
            foreach (explode('|', $usageReferences) as $usageReference) {
                // str_getcsv 以逗號分隔並忽略雙引號內的逗號
                $usageReference = str_getcsv($usageReference);

                if (count($usageReference) == 4) {
                    if (Reference::where('id', $usageReference[0])->exists()) {
                        $now_usage = [];
                        $now_usage['reference_id'] = $usageReference[0];

                        if ($usageReference[1] !== '') {
                            $now_usage['show_page'] = $usageReference[1];
                        }
                        if ($usageReference[2] !== '') {
                            $now_usage['figure'] = $usageReference[2];
                        }
                        if ($usageReference[3] === 'true') {
                            $now_usage['pro_parte'] = true;
                            $now_usage['pro_parte_type'] = 'pro parte';
                        }

                        $perUsages[] = $now_usage;
                    } else {
                        $this->throwError($row, 'usage_references提供之文獻ID查無文獻');
                    }
                }
            }
        }

        // --- indications ---
        $indications = $this->cell($row, 'indications');
        if (isset($indications)) {
            $indications = explode('|', $indications);
            $validIndications = json_decode(file_get_contents(resource_path('json/indications.json')), true);
            $validIndications = collect($validIndications)->pluck('abbreviation')->all();
            $checkedIndications = array_values(array_intersect($indications, $validIndications));
            $absent = array_values(array_diff($indications, $validIndications));

            if (count($absent) > 0) {
                $this->throwError($row, '不合法的標註: ' . implode(',', $absent));
            }
        } else {
            $checkedIndications = [];
        }

        // --- additional_fields ---
        $additionalFields = [];
        foreach (['description', 'diagnosis', 'distribution', 'etymology', 'habitat', 'substrata', 'measurements', 'coloration', 'other_examined_material'] as $add) {
            $val = $this->cell($row, $add);
            if (isset($val)) {
                $fieldName = $add === 'other_examined_material' ? 'otherExaminedMaterial' : $add;
                $additionalFields[] = [
                    'field_value' => $val,
                    'field_name' => $fieldName,
                ];
            }
        }

        // --- custom_fields ---
        $customFields = [];
        foreach (['custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'custom_field5'] as $cus) {
            $val = $this->cell($row, $cus);
            if (isset($val)) {
                $parts = explode(':', $val, 2);
                $firstPart = $parts[0];
                $secondPart = $parts[1] ?? '';

                if (isset($firstPart) && isset($secondPart)) {
                    $customFields[] = [
                        'field_name_en' => $firstPart,
                        'field_value' => $secondPart,
                    ];
                } else {
                    $this->throwError($row, '不正確的' . $cus . '格式');
                }
            }
        }

        // --- 第一筆限制 ---
        if ($row === 2 && ($isIndent === true || $status === 'not-accepted') && $this->baseGroup === 0) {
            $this->throwError($row, '第一筆不能是無效名或縮排');
        }

        // --- 解析 taxon name ---
        $taxonNames = TaxonName::query()->where('name', $name)->get();

        if ($taxonNames->count() > 1) {
            $nomenclatureId = $this->nomenclatures[$nomenclature]->id;
            $taxonNamesQuery = TaxonName::query()
                ->where('nomenclature_id', $nomenclatureId)
                ->where('rank_id', $this->rankMap[$rank]->id)
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

        // --- group / order 遞增（與原邏輯同位置）---
        if (!$isIndent) {
            $this->groupCursor++;
            $this->orderCursor = 0;
        } else {
            $this->orderCursor++;
        }

        // --- 解析 parent taxon ---
        $parentTaxonName = null;
        if ($parentTaxonNameString) {
            $parentTaxonNames = TaxonName::query()->where('name', $parentTaxonNameString)->get();

            if ($parentTaxonNames->count() > 1) {
                $nomenclatureId = $this->nomenclatures[$nomenclature]->id;
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

        // --- common_names ---
        $commonName = [];
        if (isset($commonNamesStrings)) {
            foreach (explode('|', $commonNamesStrings) as $commonNamesString) {
                $isMatch = preg_match('/(.*)\((.*),(.*)\)/', $commonNamesString, $matches);

                $cName = $matches[1] ?? '';
                foreach (array_keys(CommonNameArray::get()) as $cc_key) {
                    $cName = str_replace($cc_key, CommonNameArray::get()[$cc_key], $cName);
                }

                if ($isMatch) {
                    $commonName[] = [
                        'area' => $matches[3] ?? '',
                        'name' => trim(str_replace("\x00", "", $cName)),
                        'language' => $this->languageMapping[$matches[2]],
                    ];
                }
            }
        }

        // --- properties ---
        $properties = [
            'is_fossil' => !isset($isFossil) || $isFossil === '' ? null : ($isFossil ? 1 : 0),
            'is_marine' => !isset($isMarine) || $isMarine === '' ? null : ($isMarine ? 1 : 0),
            'is_brackish' => !isset($isBrackish) || $isBrackish === '' ? null : ($isBrackish ? 1 : 0),
            'common_names' => !isset($commonName) ? null : ($commonName),
            'note' => !isset($note) ? null : $note,
            'is_in_taiwan' => !isset($isInTaiwan) || $isInTaiwan === '' ? null : (int) $isInTaiwan,
            'is_freshwater' => !isset($isFreshwater) || $isFreshwater === '' ? null : ($isFreshwater ? 1 : 0),
            'is_terrestrial' => !isset($isTerrestrial) || $isTerrestrial === '' ? null : ($isTerrestrial ? 1 : 0),
            'additional_fields' => $additionalFields,
            'custom_fields' => $customFields,
            'indications' => $checkedIndications,
        ];

        if ($isInTaiwan == 1) {
            $properties['is_endemic'] = !isset($isEndemic) || $isEndemic === '' ? null : ($isEndemic ? 1 : 0);
            $properties['distribution_in_tw'] = $distributionTw;
            $properties['is_new_record'] = !isset($isNewRecord) || $isNewRecord === '' ? null : ($isNewRecord ? 1 : 0);
            $properties['alien_type'] = $alienType;
            $properties['alien_status_note'] = $alienStatusNote;
        }

        // --- 快取解析結果，save 階段重用（不再打 DB）---
        $this->resolved[$row] = [
            'taxon_name_id' => $taxonName->id,
            'parent_taxon_name_id' => $parentTaxonName->id ?? null,
            'status' => $status,
            'properties' => $properties,
            'per_usages' => $perUsages,
            'group' => $this->groupCursor,
            'order' => $this->orderCursor,
            'is_title' => $isTitle,
            'is_indent' => $isIndent,
        ];
    }

    // ---- 寫入階段（分批 commit，重用 validate 解析結果）----

    public function save(): int
    {
        $highest = $this->sheet->getHighestRow();
        $batchSize = 200;
        $count = 0;

        for ($start = 2; $start <= $highest; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $highest);

            DB::beginTransaction();
            try {
                for ($row = $start; $row <= $end; $row++) {
                    if (!isset($this->resolved[$row])) {
                        continue;
                    }
                    $this->saveUsage($this->resolved[$row]);
                    $count++;
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception($e->getMessage());
            }

            // commit 後回報，輪詢連線讀得到
            $this->reportProgress('saving', $count);
        }

        return $count;
    }

    private function saveUsage(array $r): void
    {
        $usage = new MyNamespaceUsage();
        $usage->namespace_id = $this->namespaceId;
        $usage->taxon_name_id = $r['taxon_name_id'];

        if ($r['is_title']) {
            $usage->parent_taxon_name_id = null;
            $usage->status = '';
            $usage->properties = [];
        } else {
            $usage->parent_taxon_name_id = $r['parent_taxon_name_id'];
            $usage->status = $r['status'];
            $usage->properties = $r['properties'];
        }

        $usage->name_remark = '';
        $usage->custom_name_remark = '';
        $usage->is_indent = $r['is_indent'];
        $usage->is_title = $r['is_title'];
        $usage->order = $r['order'];
        $usage->group = $r['group'];
        $usage->type_specimens = [];
        $usage->per_usages = $r['per_usages'];
        $usage->save();
    }

    public function totalRows(): int
    {
        return max(0, $this->sheet->getHighestRow() - 1);
    }

    private function throwError(int $row, string $message)
    {
        if (!isset($this->errorRows[$row - 1])) {
            $this->errorRows[$row - 1] = ['message' => $message];
        }
        throw new ImportRowException($message);
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