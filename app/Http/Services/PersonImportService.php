<?php


namespace App\Http\Services;


use App\Country;
use App\Exceptions\PersonDuplicateException;
use App\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PersonImportService
{
    private $duplicateRows = []; // 與資料庫比對 unique key 重複
    private $repeatRows = []; // 檔案內部重複
    private $errorRows = [];
    private $warningRows = []; // 有同姓名重複
    private $validRows = [];

    private $allKey1s = [];
    private $allKey2s = [];

    private int $maxHighRows = 0;

    private Worksheet $sheet;

    public function __construct($sheet)
    {
        $this->sheet = $sheet;

        // calculate max high row
        for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {
            $lastName = $sheet->getCell('A' . $row)->getValue();
            $firstName = $sheet->getCell('B' . $row)->getValue();
            $middleName = $this->sheet->getCell('C' . $row)->getValue();
            $yearBirth = $this->sheet->getCell('G' . $row)->getValue();

            if ($lastName == '' || $firstName == '') continue;

            $data = [
                'number' => $row,
                'last_name' => $this->sheet->getCell('A' . $row)->getValue(),
                'first_name' => $this->sheet->getCell('B' . $row)->getValue(),
                'middle_name' => $this->sheet->getCell('C' . $row)->getValue(),
                'original_full_name' => $this->sheet->getCell('D' . $row)->getValue(),
                'abbreviation_name' => $this->sheet->getCell('E' . $row)->getValue(),
                'other_names' => $this->sheet->getCell('F' . $row)->getValue(),
                'year_birth' => $this->sheet->getCell('G' . $row)->getValue(),
                'year_death' => $this->sheet->getCell('H' . $row)->getValue(),
                'year_publication' => $this->sheet->getCell('I' . $row)->getValue(),
                'country_name' => trim($this->sheet->getCell('J' . $row)->getValue()),
                'depart' => trim($this->sheet->getCell('K' . $row)->getValue()),
                'biological_group' => trim($this->sheet->getCell('L' . $row)->getValue()),
            ];

            $uniqueKey = "{$lastName}{$firstName}{$middleName}{$yearBirth}";
            $nameKey = "{$lastName}{$firstName}";

            if (isset($this->allKey1s[$uniqueKey])) {
                $this->repeatRows[] = $data;
            } else {
                $this->allKey1s[$uniqueKey] = $row;
            }

            $this->allKey2s[$nameKey] = $row;


            $this->maxHighRows = $row;
        }

        $this->countries = Country::select('display', 'numeric_code')
            ->get()
            ->keyBy(function ($c) {
                return $c->display['zh-tw'];
            });
    }

    public function handle(): int
    {
        /**
         * columns
         * A: 姓, B: 中間名, C: 名, D: 母語完整名, E: 作者縮寫, F: 其他名, G: 生年, H: 卒年, I: 活躍年代
         * J: 國籍(用中文), K: 研究類群, L: 研究類群
         */
        $this->getErrorRows();

        if (count($this->duplicateRows) || count($this->repeatRows)) {
            throw new PersonDuplicateException('重複');
        }


        DB::beginTransaction();
        $row = 2;
        $count = 0;
        try {
            while ($row <= $this->maxHighRows) {
                $lastName = $this->sheet->getCell('A' . $row)->getValue();
                $firstName = $this->sheet->getCell('B' . $row)->getValue();
                $middleName = $this->sheet->getCell('C' . $row)->getValue();
                $originalFullName = $this->sheet->getCell('D' . $row)->getValue();
                $abbreviationName = $this->sheet->getCell('E' . $row)->getValue();
                $otherNames = $this->sheet->getCell('F' . $row)->getValue();
                $yearBirth = $this->sheet->getCell('G' . $row)->getValue();
                $yearDeath = $this->sheet->getCell('H' . $row)->getValue();
                $yearPublication = $this->sheet->getCell('I' . $row)->getValue();
                $countryName = trim($this->sheet->getCell('J' . $row)->getValue());
                $departments = trim($this->sheet->getCell('K' . $row)->getValue());
                $biologicalGroup = trim($this->sheet->getCell('L' . $row)->getValue());

                if (trim($lastName) === '' && trim($firstName) === '') continue;

                $this->validBiologyDepartmentsColumn($departments);

                $numericCode = $this->validCountryColumn($countryName);

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

                $row++;
                $count++;
            }
            DB::commit();
        } catch (PersonDuplicateException $e) {
            DB::rollBack();
            Log::error("[fail] import person fail {$row} message: {$e->getMessage()}");
            throw new PersonDuplicateException("匯入於第 {$row} 筆失敗。");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[fail] import person fail {$row} message: {$e->getMessage()}");
            throw new \Exception("匯入於第 {$row} 筆失敗。");
        }

        return $count;
    }

    public function getErrorRows(): array
    {
        $this->validate();
        return [
            'repeat_rows' => $this->repeatRows,
            'duplicate_rows' => $this->duplicateRows,
            'error_rows' => $this->errorRows,
            'warning_rows' => $this->warningRows,
            'valid_rows' => $this->validRows,
        ];
    }

    private function validate()
    {
        // person unique key = last_name, middle_name, first_name, year_birth
        $duplicatePersons = Person::select(['id', DB::raw("CONCAT(`last_name`, `first_name`, `middle_name`, `year_birth`) as `unique_key`")])
            ->whereIn(DB::raw("CONCAT(`last_name`, `first_name`, `middle_name`, `year_birth`)"), array_keys($this->allKey1s))
            ->get()
            ->keyBy('unique_key');

        $warningPersons = Person::select([DB::raw("CONCAT(`last_name`, `first_name`) as `name_key`"), DB::raw("COUNT(*) as count")])
            ->whereIn(DB::raw("CONCAT(`last_name`, `first_name`)"), array_keys($this->allKey2s))
            ->groupBy('name_key')
            ->get()
            ->keyBy('name_key');

        for ($row = 2; $row <= $this->maxHighRows; $row++) {
            $lastName = $this->sheet->getCell('A' . $row)->getValue();
            $firstName = $this->sheet->getCell('B' . $row)->getValue();
            $middleName = $this->sheet->getCell('C' . $row)->getValue();
            $yearBirth = $this->sheet->getCell('G' . $row)->getValue();

            $countryName = $this->sheet->getCell('J' . $row)->getValue();

            $data = [
                'number' => $row,
                'last_name' => $this->sheet->getCell('A' . $row)->getValue(),
                'first_name' => $this->sheet->getCell('B' . $row)->getValue(),
                'middle_name' => $this->sheet->getCell('C' . $row)->getValue(),
                'original_full_name' => $this->sheet->getCell('D' . $row)->getValue(),
                'abbreviation_name' => $this->sheet->getCell('E' . $row)->getValue(),
                'other_names' => $this->sheet->getCell('F' . $row)->getValue(),
                'year_birth' => $this->sheet->getCell('G' . $row)->getValue(),
                'year_death' => $this->sheet->getCell('H' . $row)->getValue(),
                'year_publication' => $this->sheet->getCell('I' . $row)->getValue(),
                'country_name' => trim($countryName),
                'biology_departments' => trim($this->sheet->getCell('K' . $row)->getValue()),
                'biological_group' => trim($this->sheet->getCell('L' . $row)->getValue()),
            ];

            if ($lastName == '' || $firstName == '') {
                $this->errorRows[$row] = $data;
                continue;
            };

            if (isset($duplicatePersons["{$lastName}{$firstName}{$middleName}{$yearBirth}"])) {
                $this->duplicateRows[$row] = $data;
            } else if ($countryName && !isset($this->countries[$countryName])) {
                $this->errorRows[$row] = $data;
            } else if (isset($warningPersons["{$lastName}{$firstName}"])) {
                $this->warningRows[$row] = $data;
            } else {
                $this->validRows[$row] = $data;
            }
        }
    }

    private function validBiologyDepartmentsColumn(string $departmentsString)
    {
        $validDepartments = ['viruses', 'bacteria', 'archaea', 'protozoa', 'chromista', 'fungi', 'plantae', 'animalia'];
        $departmentsElements = explode(',', $departmentsString);

        foreach ($departmentsElements as $d) {
            if (!in_array($d, $validDepartments)) {
                throw new \Exception("Department Error: {$d}\n");
            }
        }
    }

    private function validCountryColumn($countryName): ?int
    {
        if ($countryName && !isset($this->countries[$countryName])) {
            throw new \Exception("Country not found $countryName");
        }
        return $countryName ? $this->countries[$countryName]->numeric_code : NULL;
    }
}