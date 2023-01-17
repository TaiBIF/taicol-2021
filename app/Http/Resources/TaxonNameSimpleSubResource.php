<?php

namespace App\Http\Resources;

use App\Rank;
use App\TaxonName;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxonNameSimpleSubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $speciesLayer = isset($this->properties['species_layers']) ? $this->properties['species_layers'] : [];

        $species = $this->properties['species_id'] ? TaxonName::find($this->properties['species_id']) : null;
        return [
            'id' => $this->id,
            'nomenclature' => $this->nomenclature,
            'rank' => $this->rank,
            'name' => $this->name,
            'reference' => ReferenceCollection::collection([$this->reference])->first(),
            'authors' => PersonCollection::collection($this->authors),
            'ex_authors' => PersonCollection::collection($this->exAuthors),
            'properties' => $this->properties,
            'publish_year' => $this->publish_year,
            'original_taxon_name' => $this->originalTaxonName ? new TaxonNameResource($this->originalTaxonName) : null,

            // Taxon name page 原始組合名所需要的資訊
            'species' => $species ? new TaxonNameResource($species) : null,
            'species_layers' => collect($speciesLayer)->map(function ($s) {
                return [
                    'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
                    'latin_name' => $s['latin_name']
                ];
            }),
        ];
    }
}
