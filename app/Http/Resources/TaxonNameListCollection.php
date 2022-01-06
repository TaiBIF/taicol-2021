<?php

namespace App\Http\Resources;

use App\Country;
use App\Person;
use App\Rank;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        $parentIds = explode('>', $this->root->path ?? '');

        $parentGroup = !empty($parentIds) ?
            TaxonName::query()
                ->select('taxon_names.*', 'ranks.id', 'ranks.order')
                ->with('rank')
                ->leftJoin('ranks','ranks.id', 'taxon_names.rank_id')
                ->whereIn('taxon_names.id', $parentIds)
                ->whereIn('rank_id', [3,12,18,22,26])
                ->where('taxon_names.id', '!=', $this->id)
                ->orderBy('ranks.order', 'desc')
                ->first() : null;

        $species = $this->properties['species_id'] ? TaxonName::find($this->properties['species_id']) : null;

        $commonNameUsage = $this->usages->first();
        $commonNameTw = collect($commonNameUsage->properties['common_names'] ?? [])->where('language', 'zh-tw')->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nomenclature' => $this->nomenclature,
            'reference' => ReferenceCollection::collection([$this->reference])->first(),
            'original_taxon_name' => $this->originalTaxonName,
            'rank' => $this->rank,
            'authors' => PersonCollection::collection($this->authors),
            'ex_authors' => PersonCollection::collection($this->exAuthors),
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
            'root' => $this->root,
            'parent_group' => $parentGroup,
            'common_name_tw' => $commonNameTw ? $commonNameTw['name'] : '',
        ];
    }
}
