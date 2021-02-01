<?php

namespace App\Console\Commands;

use App\Person;
use App\Rank;
use App\TaxonName;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportTaxonName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:taxonname';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $spreadsheet = IOFactory::load(storage_path('taxonname.xlsx'));
        $sheets = $spreadsheet->getAllSheets();
        $nomenclatureMapping = [
            'ICZN' => 1,
            'ICN' => 2,
            'ICNP' => 3,
            'ICVCN' => 4,
        ];

        /**
         * columns
         * A. 命名規約
         * B. Rank
         * C. latin_name
         * D. latin_genus
         * E. latin_s1
         * F. s2_rank
         * G. latin_s2
         * H. original_name
         * I. name_authors
         * J. name_ex_authors
         * K. 命名者例外或字串暫填欄(需求如命名邏輯：學名組合邏輯：C41)
         * L. is_hybrid
         * M. hybrid_parent1
         * N. hybrid_parent2
         * O. protologue 字串
         * P. protologue_id
         * Q. page_name
         * R. cite_figure
         * S. publish year
         * T. name_remark
         */

        $ranks = Rank::all();
        $ranksF = $ranks->keyBy('key');

        foreach ($sheets as $sheet) {
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $nomenclature = $nomenclatureMapping[$sheet->getCell('A'. $row)->getValue()];
                $rankString = $sheet->getCell('B'. $row)->getValue();
                $name = $sheet->getCell('C' . $row)->getValue();
                $latinGenus = $sheet->getCell('D' . $row)->getValue();
                $latinS1 = $sheet->getCell('E' . $row)->getValue();

                $s2Rank = $sheet->getCell('F' . $row)->getValue();
                $latinS2 = $sheet->getCell('G' . $row)->getValue();

                $authorsString = $sheet->getCell('I'. $row)->getValue();
                $exAuthorsString = $sheet->getCell('J'. $row)->getValue();

                $originNameString = $sheet->getCell('H'. $row)->getValue();

                $isHybrid = $sheet->getCell('L' . $row)->getValue();
                $referenceName = $sheet->getCell('O' . $row)->getValue();
                $publishYear = $sheet->getCell('S' . $row)->getValue();

                $authorsAbbr = preg_split('/( & |, )/', $authorsString);
                $exAuthorsAbbr = $exAuthorsString ? preg_split('/( & |, )/', $exAuthorsString) : [];
                $authors = Person::whereIn('abbreviation_name', $authorsAbbr)->get();
                $exAuthors = $exAuthorsAbbr ? Person::whereIn('abbreviation_name', $exAuthorsAbbr)->get() : [];

                $originName = null;
                if ($originNameString) {
                    $originName = DB::table(DB::raw('(select
                        CONCAT(
                            JSON_UNQUOTE(JSON_EXTRACT(properties, \'$.latin_genus\')), \' \',
                            JSON_UNQUOTE(JSON_EXTRACT(properties, \'$.latin_s1\')), \' \',
                            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(properties, \'$.species_layers[0].rank_abbreviation\')) + \' \', \'\'),
                            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(properties, \'$.species_layers[0].latin_name\')) + \' \', \'\'),
                            formatted_authors
                        ) as tempname, id from taxon_names where deleted_at is null) as t'))
                        ->where('tempname', $originNameString)
                        ->first();
                }

                if (!$originName && $originNameString) {
                    echo "找不到 $originNameString ($row)\n";
                }

                $species = TaxonName::where('name', "$latinGenus $latinS1")->first();

                $taxonName = new TaxonName();
                $taxonName->nomenclature_id = $nomenclature;
                $taxonName->rank_id = isset($ranksF[strtolower($rankString)]) ? $ranksF[strtolower($rankString)]->id : null;
                $taxonName->original_taxon_name_id = $originName ? $originName->id : null;
                $taxonName->name = $name;
                $taxonName->formatted_name = implode(' ', [$latinGenus, $latinS1]);
                $taxonName->formatted_authors = $authorsString ?? '';
                $taxonName->publish_year = $publishYear;
                $taxonName->note = '';

                $taxonName->properties = [
                    'latin_s1' => $latinS1,
                    'latin_genus' => $latinGenus,
                    'has_reference' => false,
                    'reference_name' => $referenceName,
                    'species_id' => $species ? $species->id : null,
                    'species_layers' => $s2Rank ? [
                        [
                            'rank_abbreviation' => $s2Rank,
                            'latin_name' => $latinS2,
                        ]
                    ] : []
                ];
                $taxonName->type_specimens = [];

                $taxonName->save();

                // 儲存作者
                $taxonName->authors()->sync($authors);
                $taxonName->exAuthors()->sync($exAuthors);
            }
        }
    }

}
