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
use Illuminate\Support\Facades\Log;

class TaxonNameSelectCollection extends JsonResource
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

        // // find root & parent group
        // $currentTaxonNameId = $this->id;


        // $rootId = null;

        // // TODO 這邊有比較有效率的寫法嗎
        // while ($currentTaxonNameId != null) {
        //     $currentTaxonName = DB::table('reference_usages')
        //         ->select('taxon_name_id', 'parent_taxon_name_id')
        //         ->where('accepted_taxon_name_id', $currentTaxonNameId)
        //         ->where('deleted_at', null)
        //         ->where('status', 'accepted')
        //         ->first();

        //     $parent = TaxonName::select('rank_id', 'id')->find($currentTaxonNameId);

        //     if ($currentTaxonName && $parent->rank_id == 3)
        //         $rootId = $currentTaxonNameId;

        //     $currentTaxonNameId = $currentTaxonName ? $currentTaxonName->parent_taxon_name_id : null;
        // }
        $genusTaxonName = isset($this->properties['genus_taxon_name_id']) ? TaxonName::find($this->properties['genus_taxon_name_id']) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nomenclature' => $this->nomenclature,
            'reference' => ReferenceCollection::collection([$this->reference])->first(),
            'original_taxon_name' => $this->originalTaxonName ? TaxonNameCollection::collection([$this->originalTaxonName])[0] : null,
            'genus_taxon_name' => $genusTaxonName ? new TaxonNameSimpleSubResource($genusTaxonName) : null ,
            'rank' => $this->rank,
            'authors' => PersonCollection::collection($this->authors),
            'ex_authors' => PersonCollection::collection($this->exauthors),
            'species' => $species ? TaxonNameCollection::collection([$species])[0] : null,
            'species_layers' => collect($speciesLayer)->map(function ($s) {
                return [
                    'rank' => Rank::where('abbreviation', ($s['rank_abbreviation']))->first(),
                    'latin_name' => $s['latin_name']
                ];
            }),
            'root' => $this->kingdom_taxon_name_id ? TaxonName::find($this->kingdom_taxon_name_id) : null,
            'properties' => $this->properties,
            'publish_year' => $this->publish_year,
            'hybrid_parents' => $this->hybridParents->map(function ($p) {
                return TaxonNameSelectCollection::collection([$p])[0];
            }),
            'usage' => $this->reference ? [
                'show_page' => $this->properties['usage']['show_page'],
                'figure' => $this->properties['usage']['figure'] ?? '',
                'name_in_reference' => $this->properties['usage']['name_in_reference'] ?? '',
            ] : [],

        ];
    }
}
