<?php

namespace App\Http\Resources;

use App\Country;
use App\Person;
use App\Rank;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxonNameCollection extends JsonResource
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

        $parent = $this->originalTaxonName;
        if ($parent) {
            $parent->authors = $this->originalTaxonName->authors;
            $parent->ex_authors = $this->originalTaxonName->exAuthors;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'formatted_name' => $this->formatted_name,
            'formatted_authors' => $this->formatted_authors,
            'nomenclature' => $this->nomenclature,
            'reference' => ReferenceCollection::collection([$this->reference])->first(),
            'usage' => $this->reference ? [
                'show_page' => $this->properties['usage']['show_page'],
                'figure' => $this->properties['usage']['figure'] ?? '',
                'name_in_reference' => $this->properties['usage']['name_in_reference'] ?? '',
            ] : [],
            'original_taxon_name' => $parent,
            'rank' => $this->rank,
            'authors' => PersonCollection::collection($this->authors),
            'ex_authors' => PersonCollection::collection($this->exauthors),
            'type_specimens' => collect($this->type_specimens)->map(function ($typeSpecimen) {
                $typeSpecimen['country'] = $typeSpecimen['country_id'] ?? null ? Country::find($typeSpecimen['country_id']) : null;
                $typeSpecimen['collectors'] = $typeSpecimen['collectors'] ?? [];
                return $typeSpecimen;
            }),
            'species' => TaxonName::find($this->properties['species_id'] ?? ''),
            'species_layers' => collect($speciesLayer)->map(function ($s) {
                return [
                    'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
                    'latin_name' => $s['latin_name']
                ];
            }),
            'properties' => $this->properties,
            'publish_year' => $this->publish_year,
            'hybridParents' => $this->hybridParents->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'formatted_name' => $p->formatted_name,
                ];
            }),
            'note' => $this->note,
        ];
    }
}
