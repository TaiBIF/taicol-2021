<?php

namespace App\Http\Services;

use Illuminate\Database\Eloquent\Model;

class TaxonNameLogService extends LogService
{
    private Model $originTaxonName;
    private array $originAuthors;
    private array $originExAuthors;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Model $originTaxonName
     */
    public function initOriginData(Model $originTaxonName): void
    {
        $this->originTaxonName = clone $originTaxonName;
        $this->originAuthors = $originTaxonName->authors()->get()
            ->map(function ($authors) {
                return $authors->id;
            })->toArray();
        $this->originExAuthors = $originTaxonName->exAuthors()->get()
            ->map(function ($authors) {
                return $authors->id;
            })->toArray();
    }

    /**
     * @param Model $taxonName
     */
    public function saveUpdateLog(Model $taxonName, array $authorIds, array $exAuthorIds): void
    {
        $columnChanges = [];
        //handle authors
        if (count($authorIds) != count($this->originAuthors) || count(array_diff($this->originAuthors, $authorIds)) > 0) {
            if ($this->originTaxonName->nomenclature_id == 1) {
                array_push($columnChanges, 'author.thisCombinationAuthors');
            } else {
                array_push($columnChanges, 'author.thisNameAuthors');
            }
        }
        if (count($exAuthorIds) != count($this->originExAuthors) || count(array_diff($this->originExAuthors, $exAuthorIds)) > 0) {
            array_push($columnChanges, 'exAuthor');
        }
        //handle original_taxon_name_id
        if ($this->originTaxonName->original_taxon_name_id != $taxonName->original_taxon_name_id) {
            if ($this->originTaxonName->nomenclature_id == 1) {
                array_push($columnChanges, 'originalName.original-combination');
            } else if ($this->originTaxonName->nomenclature_id == 2) {
                array_push($columnChanges, 'originalName.basionym');
            } else {
                array_push($columnChanges, 'originalName.basonym');
            }
        }
        $logService = new LogService();
        $logService->writeUpdateLogWithComparison(LogType::TAXON_NAME, $taxonName, $this->originTaxonName, $columnChanges, [
            'formatted_authors',
            'properties.reference_name',
            'properties.usage.reference_id',
            'original_taxon_name_id',
        ]);
    }


}
