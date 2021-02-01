<?php

namespace App\Console\Commands;

use App\Country;
use App\Person;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPerson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:person';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import person from spreedsheet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $spreadsheet = IOFactory::load(storage_path('person.xlsx'));
        $sheets = $spreadsheet->getAllSheets();

        /**
         * columns
         * A: 姓
         * B: 中間名
         * C: 名
         * D: 母語完整名
         * E: 作者縮寫
         * F: 其他名
         * G: 生年
         * H: 卒年
         * I: 活躍年代
         * J: 國籍(用中文)
         * K: 研究類群
         */
        foreach ($sheets as $sheet) {
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $lastName = $sheet->getCell('A' . $row)->getValue();
                $firstName = $sheet->getCell('B' . $row)->getValue();
                $middleName = $sheet->getCell('C' . $row)->getValue();
                $originalFullName = $sheet->getCell('D' . $row)->getValue();
                $abbreviationName = $sheet->getCell('E' . $row)->getValue();
                $otherNames = $sheet->getCell('F' . $row)->getValue();
                $yearBirth = $sheet->getCell('G' . $row)->getValue();
                $yearDeath = $sheet->getCell('H' . $row)->getValue();
                $yearPublication = $sheet->getCell('I' . $row)->getValue();
                $countryName = trim($sheet->getCell('J' . $row)->getValue());
                $depart = trim($sheet->getCell('K' . $row)->getValue());

                if (trim($lastName) === '' && trim($firstName) === '') {
                    echo "空的\n";
                    continue;
                }

                $person = new Person();
                $person->last_name = $lastName ?? '';
                $person->middle_name = $middleName ?? '';
                $person->first_name = $firstName ?? '';
                $person->original_full_name = $originalFullName ?? '';
                $person->abbreviation_name = $abbreviationName ?? '';
                $person->other_names = $otherNames ?? '';
                $person->year_birth = $yearBirth;
                $person->year_death = $yearDeath;
                $person->year_publication = $yearPublication ?? '';
                $person->biology_departments = $depart;

                if ($countryName) {
                    $c = Country::query()->whereJsonContains('display', ['zh-tw' => $countryName])->first();
                    if (!$c) {
                        dd('error', $countryName);
                    }
                    $person->country_numeric_code = $c->numeric_code;
                }

                $person->save();
            }
        }

        return 0;
    }
}
