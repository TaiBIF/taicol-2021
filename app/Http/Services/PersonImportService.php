<?php


namespace App\Http\Services;


use App\Country;
use App\Person;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PersonImportService
{
    private $duplicateRows = []; // 與資料庫比對 unique key 重複（已折入 errorRows）
    private $repeatRows = [];    // 檔案內部重複（已折入 errorRows）
    private $errorRows = [];     // { 列號-1 => ['message'] }
    private $warningRows = [];   // 有同姓名（不阻擋匯入，僅提示）
    private $validRows = [];

    private $countries;
    private ?int $dataRowCount = null;

    private Worksheet $sheet;
    private array $columnMap = [];   // 欄名 => 數字索引（1 起算）

    private const VALID_DEPARTMENTS = [
        'viruses', 'bacteria', 'archaea', 'protozoa',
        'chromista', 'fungi', 'plantae', 'animalia',
    ];

    /** @var callable|null  function(string $phase, int $done) */
    public $onProgress = null;

    private function reportProgress(string $phase, int $done): void
    {
        if (is_callable($this->onProgress)) {
            ($this->onProgress)($phase, $done);
        }
    }

    public function __construct($sheet)
    {
        $this->sheet = $sheet;

        // 國籍對照（save 階段亦重用）
        $this->countries = Country::select('display', 'numeric_code')
            ->get()
            ->keyBy(function ($c) {
                return $c->display['zh-tw'];
            });
    }

    // ---- 讀取工具：一律用欄名 ----

    private function buildColumnMap(): void
    {
        if (!empty($this->columnMap)) {
            return;
        }
        $highestColumnIndex = Coordinate::columnIndexFromString($this->sheet->getHighestColumn());
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = trim((string) $this->sheet->getCellByColumnAndRow($col, 1)->getValue());
            if ($header !== '') {
                $this->columnMap[$header] = $col;
            }
        }
    }

    /** 依欄名讀取；欄位不存在回 null */
    private function cell(int $row, string $columnName)
    {
        $index = $this->columnMap[$columnName] ?? null;
        if ($index === null) {
            return null;
        }
        return $this->sheet->getCellByColumnAndRow($index, $row)->getValue();
    }

    /**
     * 唯讀：逐列收集全部錯誤，回傳錯誤列數。
     * 欄名：last_name / first_name / middle_name / original_full_name / abbreviation_name /
     *       other_names / year_birth / year_death / year_publication /
     *       country_name（讀國籍中文名，存入 country_numeric_code）/ biology_departments / biological_group
     * 同姓名（last+first 已存在）僅列入 warning，不計入錯誤、不進錯誤檔。
     */
    public function validate(): int
    {
        $this->buildColumnMap();

        $highest = $this->sheet->getHighestRow();

        // 掃檔案：偵測檔案內重複、蒐集 key 供 DB 比對
        $firstSeen = [];   // uniqueKey => 首次出現列號
        $allKey1s = [];    // uniqueKey => true
        $allKey2s = [];    // nameKey   => true
        for ($row = 2; $row <= $highest; $row++) {
            $lastName = (string) $this->cell($row, 'last_name');
            $firstName = (string) $this->cell($row, 'first_name');
            $middleName = (string) $this->cell($row, 'middle_name');
            $yearBirth = (string) $this->cell($row, 'year_birth');

            if (trim($lastName) === '' && trim($firstName) === '') {
                continue; // 整列空（含結尾空列）→ 略過
            }

            $uniqueKey = "{$lastName}{$firstName}{$middleName}{$yearBirth}";
            $nameKey = "{$lastName}{$firstName}";
            if (!isset($firstSeen[$uniqueKey])) {
                $firstSeen[$uniqueKey] = $row;
            }
            $allKey1s[$uniqueKey] = true;
            $allKey2s[$nameKey] = true;
        }

        // DB 端：unique key 重複、同姓名
        $duplicatePersons = Person::select(['id', DB::raw("CONCAT(`last_name`, `first_name`, `middle_name`, `year_birth`) as `unique_key`")])
            ->whereIn(DB::raw("CONCAT(`last_name`, `first_name`, `middle_name`, `year_birth`)"), array_keys($allKey1s))
            ->get()
            ->keyBy('unique_key');

        $warningPersons = Person::select([DB::raw("CONCAT(`last_name`, `first_name`) as `name_key`"), DB::raw("COUNT(*) as count")])
            ->whereIn(DB::raw("CONCAT(`last_name`, `first_name`)"), array_keys($allKey2s))
            ->groupBy('name_key')
            ->get()
            ->keyBy('name_key');

        $done = 0;
        for ($row = 2; $row <= $highest; $row++) {
            $lastName = (string) $this->cell($row, 'last_name');
            $firstName = (string) $this->cell($row, 'first_name');
            $middleName = (string) $this->cell($row, 'middle_name');
            $yearBirth = (string) $this->cell($row, 'year_birth');
            $countryName = trim((string) $this->cell($row, 'country_name'));
            $departments = trim((string) $this->cell($row, 'biology_departments'));

            if (trim($lastName) === '' && trim($firstName) === '') {
                continue; // 空列略過
            }

            $done++;
            if ($done % 100 === 0) {
                $this->reportProgress('validating', $done);
            }

            // 姓、名必填
            if (trim($lastName) === '' || trim($firstName) === '') {
                $this->addError($row, '姓與名皆為必填');
                continue;
            }

            $uniqueKey = "{$lastName}{$firstName}{$middleName}{$yearBirth}";
            $nameKey = "{$lastName}{$firstName}";

            // 檔案內重複
            if ($firstSeen[$uniqueKey] !== $row) {
                $this->addError($row, "檔案內重複：與第 {$firstSeen[$uniqueKey]} 筆");
                continue;
            }

            // 資料庫重複
            if (isset($duplicatePersons[$uniqueKey])) {
                $this->addError($row, "與資料庫重複（#{$duplicatePersons[$uniqueKey]->id}）");
                continue;
            }

            // 國籍
            if ($countryName !== '' && !isset($this->countries[$countryName])) {
                $this->addError($row, "國籍 格式錯誤：{$countryName}");
                continue;
            }

            // 研究類群
            $deptError = $this->departmentError($departments);
            if ($deptError !== null) {
                $this->addError($row, $deptError);
                continue;
            }

            // 同姓名（不同人）→ 警告，不阻擋
            if (isset($warningPersons[$nameKey])) {
                $this->warningRows[$row - 1] = ['message' => "與現有同姓名者（{$nameKey}）"];
            } else {
                $this->validRows[$row - 1] = ['message' => ''];
            }
        }
        $this->reportProgress('validating', $done);

        return count($this->errorRows);
    }

    /** 分批 commit（每 200 筆），回傳成功筆數 */
    public function save(): int
    {
        $this->buildColumnMap();

        $highest = $this->sheet->getHighestRow();
        $batchSize = 200;
        $count = 0;

        for ($start = 2; $start <= $highest; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $highest);

            DB::beginTransaction();
            try {
                for ($row = $start; $row <= $end; $row++) {
                    $lastName = (string) $this->cell($row, 'last_name');
                    $firstName = (string) $this->cell($row, 'first_name');

                    if (trim($lastName) === '' && trim($firstName) === '') {
                        continue; // 空列略過（與 validate 一致）
                    }

                    $middleName = $this->cell($row, 'middle_name');
                    $originalFullName = $this->cell($row, 'original_full_name');
                    $abbreviationName = $this->cell($row, 'abbreviation_name');
                    $otherNames = $this->cell($row, 'other_names');
                    $yearBirth = $this->cell($row, 'year_birth');
                    $yearDeath = $this->cell($row, 'year_death');
                    $yearPublication = $this->cell($row, 'year_publication');
                    $countryName = trim((string) $this->cell($row, 'country_name'));
                    $departments = trim((string) $this->cell($row, 'biology_departments'));
                    $biologicalGroup = trim((string) $this->cell($row, 'biological_group'));

                    $numericCode = ($countryName !== '' && isset($this->countries[$countryName]))
                        ? $this->countries[$countryName]->numeric_code
                        : null;

                    $person = new Person();
                    $person->last_name = $lastName ?? '';
                    $person->middle_name = $middleName ?? '';
                    $person->first_name = $firstName ?? '';
                    $person->original_full_name = $originalFullName ?? '';
                    $person->abbreviation_name = $abbreviationName ?? '';
                    $person->other_names = $otherNames ?? '';
                    $person->year_birth = $yearBirth ?? '';
                    $person->year_death = $yearDeath ?? '';
                    $person->year_publication = $yearPublication ?? '';
                    $person->biology_departments = $departments;
                    $person->biological_group = implode('、', explode(', ', $biologicalGroup)) ?? '';
                    $person->country_numeric_code = $numericCode;
                    $person->save();

                    (new LogService())->writeImportLog(LogType::PERSON, $person->id);

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

    public function totalRows(): int
    {
        if ($this->dataRowCount !== null) {
            return $this->dataRowCount;
        }
        $this->buildColumnMap();
        $count = 0;
        for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {
            $lastName = trim((string) $this->cell($row, 'last_name'));
            $firstName = trim((string) $this->cell($row, 'first_name'));
            if ($lastName === '' && $firstName === '') {
                continue;
            }
            $count++;
        }
        return $this->dataRowCount = $count;
    }

    private function addError(int $row, string $message): void
    {
        if (!isset($this->errorRows[$row - 1])) {
            $this->errorRows[$row - 1] = ['message' => $message];
        }
    }

    private function departmentError(string $departmentsString): ?string
    {
        foreach (explode(',', $departmentsString) as $d) {
            $d = trim($d);
            if ($d === '') {
                continue; // 未填 / 尾逗號 → 視為沒填，放行
            }
            if (!in_array($d, self::VALID_DEPARTMENTS, true)) {
                return "研究類群 格式錯誤：{$d}";
            }
        }
        return null;
    }

    public function getErrorRows(): array
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