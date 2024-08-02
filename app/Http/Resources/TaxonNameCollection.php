<?php

namespace App\Http\Resources;

use App\Country;
use App\Person;
use App\Rank;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

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

        $species = $this->properties['species_id'] ? TaxonName::find($this->properties['species_id']) : null;

        // find root & parent group
        $currentTaxonNameId = $this->id;

        $rootId = null;

        while ($currentTaxonNameId != null) {
            $currentTaxonName = DB::table('accepted_usages')
                ->select('taxon_name_id', 'parent_taxon_name_id')
                ->where('taxon_name_id', $currentTaxonNameId)
                ->first();

            $parent = TaxonName::select('rank_id', 'id')->find($currentTaxonNameId);

            // if ($currentTaxonName && $parent->rank_id == 3 && $currentTaxonName->parent_taxon_name_id == null)
            if ($currentTaxonName && $parent->rank_id == 3)
                $rootId = $currentTaxonNameId;

            $currentTaxonNameId = $currentTaxonName ? $currentTaxonName->parent_taxon_name_id : null;
        }

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

        $replacementName = ($this->properties['replacement_name'] ?? '') ? TaxonNameCollection::collection([
            TaxonName::with([
                'authors',
                'exAuthors',
                'reference',
                'nomenclature',
                'originalTaxonName.authors',
                'originalTaxonName.exauthors'
            ])->find((int) $this->properties['replacement_name'])
        ])[0] : null;

        $orthographicVariation = ($this->properties['orthographic_variation'] ?? '') ? TaxonNameCollection::collection([
            TaxonName::with([
                'authors',
                'exAuthors',
                'reference',
                'nomenclature',
                'originalTaxonName.authors',
                'originalTaxonName.exauthors'
            ])->find((int) $this->properties['orthographic_variation'])
        ])[0] : null;

        $commonNameUsage = $this->usages->first();
        $commonNameTw = collect($commonNameUsage->properties['common_names'] ?? [])->where('language', 'zh-tw')->first();

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
            'original_taxon_name' => $this->originalTaxonName ? TaxonNameCollection::collection([$this->originalTaxonName])[0] : null,
            'rank' => $this->rank,
            'authors' => PersonCollection::collection($this->authors),
            'ex_authors' => PersonCollection::collection($this->exauthors),
            'type_specimens' => collect($this->type_specimens)->map(function ($typeSpecimen) use ($request) {
                $typeSpecimen['country'] = $typeSpecimen['country_id'] ?? null ? Country::find($typeSpecimen['country_id']) : null;
                $collectors = collect($typeSpecimen['collectors'] ?? [])->pluck('id');
                $collectorPersons = Person::whereIn('id', $collectors)->get();
                $typeSpecimen['collectors'] = $collectors->count() ? PersonCollection::collection($collectorPersons)->toArray($request) : [];

                $lectoDesignatedReference = null;
                if ($typeSpecimen['lecto_designated_reference_id']) {
                    $reference = Reference::find($typeSpecimen['lecto_designated_reference_id']);
                    $lectoDesignatedReference = $reference ?? ReferenceCollection::collection([$reference])->first();
                }

                $typeSpecimen['lecto_designated_reference'] = $lectoDesignatedReference;
                return $typeSpecimen;
            }),
            'species' => $species ? TaxonNameCollection::collection([$species])[0] : null,
            'species_layers' => collect($speciesLayer)->map(function ($s) {
                return [
                    'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
                    'latin_name' => $s['latin_name']
                ];
            }),
            'root' => $rootId ? TaxonName::find($rootId) : null,
            'properties' => $this->properties,
            'replacement_name' => $replacementName,
            'orthographic_variation' => $orthographicVariation,
            'type_name' => $typeName,
            'publish_year' => $this->publish_year,
            'hybrid_parents' => $this->hybridParents->map(function ($p) {
                return TaxonNameCollection::collection([$p])[0];
            }),
            'note' => $this->note,
            'common_name_tw' => $commonNameTw ? $commonNameTw['name'] : '',
        ];
    }
}
