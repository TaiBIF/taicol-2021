<?php


namespace App\Http\Services;


use App\MyNamespaceUsage;
use App\Nomenclature;
use App\Rank;
use App\TaxonName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        try {
            for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {
                $nomenclature = $this->sheet->getCell('A' . $row)->getCalculatedValue();
                $rank = $this->sheet->getCell('B' . $row)->getCalculatedValue();
                $name = $this->sheet->getCell('C' . $row)->getCalculatedValue();
                $authorsString = $this->sheet->getCell('D' . $row)->getCalculatedValue();
                $parentTaxonNameString = $this->sheet->getCell('E' . $row)->getCalculatedValue();
                $status = $this->sheet->getCell('F' . $row)->getCalculatedValue();
                $commonNamesString = $this->sheet->getCell('I' . $row)->getCalculatedValue();

                $isInTaiwan = $this->sheet->getCell('J' . $row)->getCalculatedValue();
                $distributionTw = $this->sheet->getCell('K' . $row)->getCalculatedValue();
                $isEndemic = $this->sheet->getCell('L' . $row)->getCalculatedValue();
                $alienType = $this->sheet->getCell('M' . $row)->getCalculatedValue();
                $isFossil = $this->sheet->getCell('N' . $row)->getCalculatedValue();
                $isTerrestrial = $this->sheet->getCell('O' . $row)->getCalculatedValue();
                $isFreshwater = $this->sheet->getCell('P' . $row)->getCalculatedValue();
                $isBrackish = $this->sheet->getCell('Q' . $row)->getCalculatedValue();
                $isMarine = $this->sheet->getCell('R' . $row)->getCalculatedValue();

                $isIndent = (bool) $this->sheet->getCell('H' . $row)->getCalculatedValue();

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

                $isMatch = preg_match('/(.*)\((.*),(.*)\)/', $commonNamesString, $matches);

                $commonName = $isMatch ? [[
                    'area' => $matches[3],
                    'name' => $matches[1],
                    'language' => $this->languageMapping[$matches[2]],
                ]] : [];

                $properties = [
                    'is_fossil' => !isset($isFossil) || $isFossil === '' ? null : ($isFossil ? 1 : 0),
                    'is_marine' => !isset($isMarine) || $isMarine === '' ? null : ($isMarine ? 1 : 0),
                    'is_brackish' => !isset($isBrackish) || $isBrackish === '' ? null : ($isBrackish ? 1 : 0),
                    'common_names' => !isset($commonName) ? null : ($commonName),
                    'is_in_taiwan' => !isset($isInTaiwan) || $isInTaiwan === '' ? null : ($isInTaiwan ? 1 : 0),
                    'is_freshwater' => !isset($isFreshwater) || $isFreshwater === '' ? null : ($isFreshwater ? 1 : 0),
                    'is_terrestrial' => !isset($isTerrestrial) || $isTerrestrial === '' ? null : ($isTerrestrial ? 1 : 0),
                ];

                if ($isInTaiwan) {
                    $properties['is_endemic'] = !isset($isEndemic) || $isEndemic === '' ? null : ($isEndemic ? 1 : 0);
                    $properties['distribution_in_tw'] = $distributionTw;
                    $properties['alien_type'] = $alienType;
                }

                $this->saveUsages($row, $taxonName, $parentTaxonName ?? null, $properties, $group, $order);
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

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $kingdom = $sheet->getCell('A' . $row)->getCalculatedValue();
            $rank = $sheet->getCell('B' . $row)->getCalculatedValue();
            $name = $sheet->getCell('C' . $row)->getCalculatedValue();
            $authorNamesString = $sheet->getCell('D' . $row)->getCalculatedValue();
            $usageStatus = $sheet->getCell('F' . $row)->getCalculatedValue();
            $isIndent = (bool) $this->sheet->getCell('H' . $row)->getCalculatedValue();
            $commonNames = $sheet->getCell('I' . $row)->getCalculatedValue();
            $alienType = $sheet->getCell('M' . $row)->getCalculatedValue();

            if (!$kingdom) {
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

    private function saveUsages(int $row, $taxonName, ?object $parentTaxonName, $properties, $group, $order)
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
