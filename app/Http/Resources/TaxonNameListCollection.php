<?php

namespace App\Http\Resources;

use App\Rank;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TaxonNameListCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $speciesLayer = isset($this->properties['species_layers']) ? $this->properties['species_layers'] : [];

        // find root & parent group
        $currentTaxonNameId = $this->id;

        $parentGroupId = null;
        $rootId = null;

        while ($currentTaxonNameId != null) {
            $currentTaxonName = DB::table('accepted_usages')
                ->select('taxon_name_id', 'parent_taxon_name_id')
                ->where('taxon_name_id', $currentTaxonNameId)
                ->first();

            $parent = TaxonName::select('rank_id', 'id')->find($currentTaxonNameId);

            if ($parent && in_array($parent->rank_id, [3, 12, 18, 22, 26]) && $parentGroupId == null && $currentTaxonNameId != $this->id)
                $parentGroupId = $parent->id;
            // if ($currentTaxonName && $parent->rank_id == 3 && $currentTaxonName->parent_taxon_name_id == null)
            if ($currentTaxonName && $parent->rank_id == 3)
                $rootId = $currentTaxonNameId;

            $currentTaxonNameId = $currentTaxonName ? $currentTaxonName->parent_taxon_name_id : null;
        }

        $species = $this->properties['species_id'] ? TaxonName::find($this->properties['species_id']) : null;

        $commonNameUsage = $this->usages->first();
        $commonNameTw = collect($commonNameUsage->properties['common_names'] ?? [])->where('language', 'zh-tw')->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nomenclature' => $this->nomenclature,
            'reference' => ReferenceCollection::collection([$this->reference])->first(),
            'original_taxon_name' => $this->originalTaxonName ? TaxonNameCollection::collection([$this->originalTaxonName])[0] : null,
            'rank' => $this->rank,
            'authors' => $this->authors->map(function ($author) {
                return [
                    'id' => $author->id,
                    'first_name' => $author->first_name,
                    'last_name' => $author->last_name,
                    'middle_name' => $author->middle_name,
                    'abbreviation_name' => $author->abbreviation_name,
                ];
            }),
            'ex_authors' => $this->exauthors->map(function ($author) {
                return [
                    'id' => $author->id,
                    'first_name' => $author->first_name,
                    'last_name' => $author->last_name,
                    'middle_name' => $author->middle_name,
                    'abbreviation_name' => $author->abbreviation_name,
                ];
            }),
            'properties' => $this->properties,
            'publish_year' => $this->publish_year,
            'hybrid_parents' => $this->hybridParents->map(function ($p) {
                return TaxonNameCollection::collection([$p])[0];
            }),
            'species' => $species ? [
                'properties' => $species->properties
            ] : null,
            'species_layers' => collect($speciesLayer)->map(function ($s) {
                return [
                    'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
                    'latin_name' => $s['latin_name']
                ];
            }),
            'root' => $rootId ? TaxonName::find($rootId) : null,
            'parent_group' => $parentGroupId ? TaxonName::find($parentGroupId) : null,
            'common_name_tw' => $commonNameTw ? $commonNameTw['name'] : '',
        ];
    }
}
