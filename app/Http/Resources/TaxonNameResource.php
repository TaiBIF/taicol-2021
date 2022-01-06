<?php

namespace App\Http\Resources;

use App\Country;
use App\Person;
use App\Rank;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxonNameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $speciesLayer = isset($this->properties['species_layers']) ? $this->properties['species_layers'] : [];

        $species = $this->properties['species_id'] ? TaxonName::find($this->properties['species_id']) : null;

        $typeName = ($this->properties['type_name'] ?? '') ? TaxonNameCollection::collection([
            TaxonName::with([
                'authors',
                'exAuthors',
                'reference',
                'nomenclature',
                'originalTaxonName.authors',
                'originalTaxonName.exauthors'
            ])->find((int) $this->properties['type_name'])
        ])[0] : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'formatted_authors' => $this->formatted_authors,
            'nomenclature' => $this->nomenclature,
            'reference' => ReferenceCollection::collection([$this->reference])->first(),
            'usage' => $this->reference ? [
                'show_page' => $this->properties['usage']['show_page'],
                'figure' => $this->properties['usage']['figure'] ?? '',
                'name_in_reference' => $this->properties['usage']['name_in_reference'] ?? '',
            ] : [],
            'original_taxon_name' => $this->originalTaxonName ? new TaxonNameSimpleSubResource($this->originalTaxonName) : null,
            'rank' => $this->rank,
            'authors' => PersonCollection::collection($this->authors),
            'ex_authors' => PersonCollection::collection($this->exauthors),
            'type_specimens' => collect($this->type_specimens)->map(function ($typeSpecimen) use ($request) {
                $typeSpecimen['country'] = $typeSpecimen['country_id'] ?? null ? Country::find($typeSpecimen['country_id']) : null;
                $collectors = collect($typeSpecimen['collectors'])->pluck('id');
                $collectorPersons = Person::whereIn('id', $collectors)->get();
                $typeSpecimen['collectors'] = $collectors->count() ? PersonCollection::collection($collectorPersons)->toArray($request) : [];

                $lectoDesignatedReference = null;
                if ($typeSpecimen['lecto_designated_reference_id']) {
                    $reference = Reference::find($typeSpecimen['lecto_designated_reference_id']);
                    $lectoDesignatedReference = $reference ? ReferenceCollection::collection([$reference])->first() : null;
                }
                $typeSpecimen['lecto_designated_reference'] = $lectoDesignatedReference;

                return $typeSpecimen;
            }),
            'species' => $species ? new TaxonNameResource($species) : null,
            'species_layers' => collect($speciesLayer)->map(function ($s) {
                return [
                    'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
                    'latin_name' => $s['latin_name']
                ];
            }),
            'properties' => $this->properties,
            'type_name' => $typeName,
            'publish_year' => $this->publish_year,
            'hybrid_parents' => TaxonNameSimpleSubResource::collection($this->hybridParents),
            'note' => $this->note,
        ];
    }
}
