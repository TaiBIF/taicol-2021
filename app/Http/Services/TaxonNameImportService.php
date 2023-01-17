<?php


namespace App\Http\Services;


use App\Person;
use App\Rank;
use App\TaxonName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxonNameImportService
{
    private $duplicateRows = []; // 與資料庫比對 unique key 重複
    private $repeatRows = []; // 檔案內部重複
    private $errorRows = [];
    private $validRows = [];
    private $warningRows = []; //

    private Collection $ranks;
    private Worksheet $sheet;

    private $nomenclatureMapping = [
        'ICZN' => 1,
        'ICN' => 2,
        'ICNP' => 3,
        'ICVCN' => 4,
    ];

    public function __construct($sheet)
    {
        $this->sheet = $sheet;
    }

    public function handle()
    {
        $this->ranks = Rank::all()->keyBy('key');
        $this->validateSheetRows();


        DB::beginTransaction();
        $count = 0;

        try {
            for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {

                $taxonName = $this->saveTaxonName($row);

                $logService = new LogService();
                $logService->writeCreateLog(LogType::TAXON_NAME(), $taxonName->id);
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

        $service = new TaxonNameService(new TaxonName());

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $nomenclature = $this->nomenclatureMapping[$this->sheet->getCell('A' . $row)->getCalculatedValue()];
            $rankString = $this->sheet->getCell('B' . $row)->getCalculatedValue();
            $name = $this->sheet->getCell('C' . $row)->getCalculatedValue();
            $referenceIdString = $this->sheet->getCell('O' . $row)->getCalculatedValue();
            $referenceId = (int) $referenceIdString ?: null;
            $authorsString = $this->sheet->getCell('L' . $row)->getCalculatedValue();

            if (!$nomenclature) {
                $this->throwError($row, 'nomenclature 錯誤');
            }

            if (!$rankString) {
                $this->throwError($row, 'rank 未填寫');
            }

            if (!$name) {
                $this->throwError($row, 'name 未填寫');
            }

            if (!isset($this->ranks[$rankString])) {
                $this->throwError($row, 'rank 錯誤');
            }

            $authors = $this->findPersonsByString($row, $authorsString);
            if ($service->hasExist($nomenclature, $this->ranks[$rankString]->id, $name, $referenceId, $authors->pluck('id')->toArray())) {
                $this->throwError($row, '學名重複');
            }

            $this->maxHighRows = $row;
        }
    }

    private function throwError(int $row, string $message)
    {
        $this->errorRows[$row - 1] = ['message' => $message];
        throw new \Exception($message);
    }

    private function findPersonsByString(int $row, ?string $authorsString = '')
    {
        if (!$authorsString) {
            return collect([]);
        }

        $authorOriginalFullNames = $authorsString ? explode('|', $authorsString) : [];
        $authors = count($authorOriginalFullNames) ? Person::whereIn('original_full_name', $authorOriginalFullNames)
            ->get()
            ->sortBy(function ($model) use ($authorOriginalFullNames) {
                return array_search($model->original_full_name, $authorOriginalFullNames);
            }) : collect([]);
        if ($authors->count() !== count($authorOriginalFullNames)) {
            $this->throwError($row, "找不到作者");
        }

        return $authors;
    }

    private function saveTaxonName(int $row)
    {
        $taxonName = new TaxonName();

        $service = new TaxonNameService($taxonName);

        $nomenclature = $this->nomenclatureMapping[$this->sheet->getCell('A' . $row)->getCalculatedValue()];
        $rankString = $this->sheet->getCell('B' . $row)->getCalculatedValue();
        $name = $this->sheet->getCell('C' . $row)->getCalculatedValue();
        $latinGenus = $this->sheet->getCell('D' . $row)->getCalculatedValue();
        $latinS1 = $this->sheet->getCell('E' . $row)->getCalculatedValue();

        $s2Rank = $this->sheet->getCell('F' . $row)->getCalculatedValue();
        $latinS2 = $this->sheet->getCell('G' . $row)->getCalculatedValue();

        $originNameString = $this->sheet->getCell('H' . $row)->getCalculatedValue();
        $originNameAuthorString = $this->sheet->getCell('I' . $row)->getCalculatedValue();
        $originNameExAuthorString = $this->sheet->getCell('J' . $row)->getCalculatedValue();

        $formattedAuthorsString = $this->sheet->getCell('K' . $row)->getCalculatedValue();
        $authorsString = $this->sheet->getCell('L' . $row)->getCalculatedValue();
        $exAuthorsString = $this->sheet->getCell('M' . $row)->getCalculatedValue();

        $referenceName = $this->sheet->getCell('N' . $row)->getCalculatedValue();
        $referenceIdString = $this->sheet->getCell('O' . $row)->getCalculatedValue();
        $referenceId = (int) $referenceIdString ?: null;
        $page = $this->sheet->getCell('P' . $row)->getCalculatedValue();
        $citeFigure = $this->sheet->getCell('Q' . $row)->getCalculatedValue();
        $publishYear = $this->sheet->getCell('R' . $row)->getCalculatedValue();
        $note = $this->sheet->getCell('S' . $row)->getCalculatedValue();

        $originalTaxonName = null;
        if ($originNameString) {
            $originalTaxonName = $this->findOriginalTaxonName($originNameString, $originNameAuthorString, $originNameExAuthorString, $row);
        }

        if (!$originalTaxonName && $originNameString) {
            $this->throwError($row, "找不到 $originNameString");
        }

        $species = TaxonName::where('name', "$latinGenus $latinS1")->first();

        $authors = $this->findPersonsByString($row, $authorsString);
        $exAuthors = $this->findPersonsByString($row, $exAuthorsString);

        return $service->saveAll([
            'nomenclature_id' => $nomenclature,
            'rank_id' => $this->ranks[strtolower($rankString)]->id,
            'name' => $name,
            'formatted_authors' => $formattedAuthorsString,
            'original_taxon_name_id' => $originalTaxonName ? $originalTaxonName->id : null,
            'type_specimens' => [],
            'publish_year' => $publishYear,
            'note' => $note,

            'is_hybrid' => false,
            'latin_genus' => $latinGenus,
            'latin_name' => $name,
            'latin_s1' => $latinS1,
            'reference_name' => $referenceName,
            'species_id' => $species ? $species->id : null,
            'species_layers' => $s2Rank ? [
                [
                    'rank_abbreviation' => $s2Rank,
                    'latin_name' => $latinS2,
                ]
            ] : [],
            'type_name' => '',
            'usage' => $referenceId ? [
                'reference_id' => $referenceId,
                'figure' => $citeFigure,
                'name_in_reference' => '',
                'show_page' => $page,
            ] : [],

            // ICNP
            'is_approved_list' => false,
            'initial_year' => '',

            // ICNP
            'genome_composition' => '',
            'host' => '',
        ],
            $authors->pluck('id')->toArray(),
            $exAuthors->pluck('id')->toArray(),
            $referenceId ? [
                'reference_id' => $referenceId,
                'figure' => $citeFigure,
                'name_in_reference' => '',
                'show_page' => $page,
            ] : [],
        );
    }

    private function findOriginalTaxonName(string $originNameString, ?string $originAuthorNameString, ?string $originExAuthorNameString, int $row)
    {
        $originNameQuery = TaxonName::where('name', $originNameString);

        if ($originAuthorNameString) {
            $authors = $this->findPersonsByString($row, $originAuthorNameString);
            $originNameQuery->whereHas('authors', function ($query) use ($authors) {
                $query->whereIn('persons.id', $authors->pluck('id')->toArray());
            }, '=', $authors->count());
        }

        if ($originExAuthorNameString) {
            $exAuthors = $this->findPersonsByString($row, $originExAuthorNameString);
            $originNameQuery->whereHas('exauthors', function ($query) use ($exAuthors) {
                $query->whereIn('persons.id', $exAuthors->pluck('id')->toArray());
            }, '=', $exAuthors->count());
        }

        return $originNameQuery->first();
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