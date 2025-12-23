<?php

namespace App\Http\Services;

use App\Person;
use App\Rank;
use App\TaxonName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxonNameAiImportService
{
    private $duplicateRows = [];
    private $repeatRows = [];
    private $errorRows = [];
    private $validRows = [];
    private $warningRows = [];

    private Collection $ranks;
    private array $scientificNamesData;

    private $nomenclatureMapping = [
        'ICZN' => 1,
        'ICN' => 2,
        'ICNP' => 3,
        'ICVCN' => 4,
    ];

    public function __construct(array $scientificNamesData)
    {
        $this->scientificNamesData = $scientificNamesData;
    }

    public function handle()
    {
        $this->ranks = Rank::all()->keyBy('key');
        $this->validateData();

        DB::beginTransaction();
        $count = 0;
        $importedTaxonNames = [];

        try {
            $min_taxon_name_id = 0;

            foreach ($this->scientificNamesData['scientific_names'] as $arrayIndex => $scientificName) {
                // $originalIndex = $scientificName['index'] ?? $arrayIndex;
                $taxonName = $this->saveTaxonName($arrayIndex, $scientificName);

                // 使用原始的 index
                $importedTaxonNames[] = [
                    'original_name' => $scientificName['original_name'],
                    // 'original_index' => $originalIndex,
                    'taxon_name_id' => $taxonName->id,
                    'taxon_name' => $taxonName
                ];

                $logService = new LogService();
                $logService->writeImportLog(LogType::TAXON_NAME, $taxonName->id);
                $count++;
                
                if ($count === 1) {
                    $min_taxon_name_id = $taxonName->id;
                }
            }

            DB::commit();

            // 要commit之後才呼叫API
            $nameUpdateAPI = env('TAICOL_API_ROOT') . '/update/name?min_taxon_name_id=' . $min_taxon_name_id;
            $resp = file_get_contents($nameUpdateAPI);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'count' => $count,
            'imported_taxon_names' => $importedTaxonNames
        ];
    }

    private function validateData()
    {
        $service = new TaxonNameService(new TaxonName());

        foreach ($this->scientificNamesData['scientific_names'] as $arrayIndex => $scientificName) {
            // $originalIndex = $scientificName['index'] ?? $arrayIndex;
            
            $nomenclature = $scientificName['nomenclature'] ?? null;
            $rankString = $scientificName['rank'] ?? null;
            $name = isset($scientificName['latin_name']) ? trim(str_replace("\x00", "", $scientificName['latin_name'])) : null;
            $referenceId = isset($scientificName['reference_id']) ? (int) $scientificName['reference_id'] : null;
            $authorsString = $scientificName['authors'] ?? null;

            if (!$nomenclature) {
                $this->throwError($arrayIndex, 'nomenclature 錯誤');
            }

            if (!$rankString) {
                $this->throwError($arrayIndex, 'rank 未填寫');
            }

            if (!$name) {
                $this->throwError($arrayIndex, 'name 未填寫');
            }

            if (!isset($this->ranks[$rankString])) {
                $this->throwError($arrayIndex, 'rank 錯誤');
            }

            $authors = $this->findPersonsByString($arrayIndex, $authorsString);
            if ($service->hasTaxonNameExist($nomenclature, $this->ranks[$rankString]->id, $name, $referenceId, $authors->pluck('id')->toArray(), true)) {
                $this->throwError($arrayIndex, '學名重複');
            } else if ($service->hasTaxonNameExist($nomenclature, $this->ranks[$rankString]->id, $name, $referenceId, $authors->pluck('id')->toArray(), false)) {
                $this->throwError($arrayIndex, '學名已存在於草稿');
            }
        }
    }

    private function throwError(int $index, string $message)
    {
        $this->errorRows[$index] = ['message' => $message];
        throw new \Exception("第 {$index} 筆資料錯誤: {$message}");
    }

    private function findPersonsByString(int $index, ?string $authorsString = '')
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
            $this->throwError($index, "找不到作者");
        }

        return $authors;
    }

    private function saveTaxonName(int $arrayIndex, array $scientificName)
    {
        // $originalIndex = $scientificName['index'] ?? $arrayIndex;
        
        $taxonName = new TaxonName();
        $service = new TaxonNameService($taxonName);

        $nomenclature = $scientificName['nomenclature'];
        $rankString = $scientificName['rank'];
        $name = trim(str_replace("\x00", "", $scientificName['latin_name']));
        $latinGenus = isset($scientificName['latin_genus']) ? trim(str_replace("\x00", "", $scientificName['latin_genus'])) : '';
        $latinS1 = isset($scientificName['latin_s1']) ? trim(str_replace("\x00", "", $scientificName['latin_s1'])) : '';
        
        $s2Rank = $scientificName['s2_rank'] ?? null;
        $latinS2 = isset($scientificName['latin_s2']) ? trim(str_replace("\x00", "", $scientificName['latin_s2'])) : '';
        
        $originNameString = $scientificName['origin_name'] ?? null;
        $originNameAuthorString = $scientificName['origin_name_author'] ?? null;
        $originNameExAuthorString = $scientificName['origin_name_ex_author'] ?? null;
        
        $formattedAuthorsString = $scientificName['formatted_authors'] ?? '';
        $authorsString = $scientificName['authors'] ?? '';
        $exAuthorsString = $scientificName['ex_authors'] ?? '';
        
        $referenceName = $scientificName['reference_name'] ?? '';
        $referenceId = isset($scientificName['reference_id']) ? (int) $scientificName['reference_id'] : null;
        $page = $scientificName['page'] ?? '';
        $citeFigure = $scientificName['cite_figure'] ?? '';
        $publishYear = $scientificName['publish_year'] ?? '';
        $note = $scientificName['note'] ?? '';
        $kingdomNameString = $scientificName['kingdom_name'] ?? null;

        $originalTaxonName = null;
        if ($originNameString) {
            $originalTaxonName = $this->findOriginalTaxonName($originNameString, $originNameAuthorString, $originNameExAuthorString, $arrayIndex);
        }

        if (!$originalTaxonName && $originNameString) {
            $this->throwError($arrayIndex, "找不到 $originNameString");
        }

        $kingdomTaxonName = null;
        if ($kingdomNameString) {
            $kingdomTaxonName = $this->findKingdomTaxonName($kingdomNameString, $arrayIndex);
        }

        if (!$kingdomTaxonName && $kingdomNameString) {
            $this->throwError($arrayIndex, "找不到 $kingdomNameString");
        }

        $species = TaxonName::where('name', "$latinGenus $latinS1")->first();

        $authors = $this->findPersonsByString($arrayIndex, $authorsString);
        $exAuthors = $this->findPersonsByString($arrayIndex, $exAuthorsString);

        $taxonName = $service->saveAll([
            'nomenclature_id' => $nomenclature,
            'rank_id' => $this->ranks[strtolower($rankString)]->id,
            'name' => $name,
            'formatted_authors' => $formattedAuthorsString,
            'original_taxon_name_id' => $originalTaxonName ? $originalTaxonName->id : null,
            'kingdom_taxon_name_id' => $kingdomTaxonName ? $kingdomTaxonName->id : null,
            'type_specimens' => $scientificName['type_specimens'] ?? [],
            'publish_year' => $publishYear,
            'note' => $note,
            'is_hybrid' => $scientificName['is_hybrid'] ?? false,
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
            'type_name' => $scientificName['type_name'] ?? '',
            'usage' => $referenceId ? [
                'reference_id' => $referenceId,
                'figure' => $citeFigure,
                'name_in_reference' => '',
                'show_page' => $page,
            ] : [],

            // ICNP
            'is_approved_list' => $scientificName['is_approved_list'] ?? false,
            'initial_year' => $scientificName['initial_year'] ?? '',

            // ICNP
            'genome_composition' => $scientificName['genome_composition'] ?? '',
            'host' => $scientificName['host'] ?? '',
            'from_import' => true,
        ],
        $authors->pluck('id')->toArray(),
        $exAuthors->pluck('id')->toArray(),
        $referenceId ? [
            'reference_id' => $referenceId,
            'figure' => $citeFigure,
            'name_in_reference' => '',
            'show_page' => $page,
        ] : [],
        true
        );

        if ($nomenclature != 4) {
            $service->getAndUpdateObjectGroups();
        }
        
        return $taxonName;
    }

    private function findOriginalTaxonName(string $originNameString, ?string $originAuthorNameString, ?string $originExAuthorNameString, int $index)
    {
        $originNameQuery = TaxonName::where('name', $originNameString);

        if ($originAuthorNameString) {
            $authors = $this->findPersonsByString($index, $originAuthorNameString);
            $originNameQuery->whereHas('authors', function ($query) use ($authors) {
                $query->whereIn('persons.id', $authors->pluck('id')->toArray());
            }, '=', $authors->count());
        }

        if ($originExAuthorNameString) {
            $exAuthors = $this->findPersonsByString($index, $originExAuthorNameString);
            $originNameQuery->whereHas('exauthors', function ($query) use ($exAuthors) {
                $query->whereIn('persons.id', $exAuthors->pluck('id')->toArray());
            }, '=', $exAuthors->count());
        }

        return $originNameQuery->first();
    }

    private function findKingdomTaxonName(string $kingdomNameString, int $index)
    {
        $kingdomNameQuery = TaxonName::where('name', $kingdomNameString)->where('rank_id', 3);
        return $kingdomNameQuery->first();
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