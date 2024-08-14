<?php

namespace App\Http\Resources;

// use App\Country;
// use App\Person;
// use App\Rank;
// use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
// use Illuminate\Support\Facades\DB;

class ParentNameResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {

        $currentName = TaxonName::where('id', $this['id'])
        ->first();

        $parentTaxonNameString = null;

        $speciesLayer = isset($currentName->properties['species_layers']) ? $currentName->properties['species_layers'] : [];

        if (count($speciesLayer) == 2){
            // 將學名組合起來
            $parentTaxonNameString = $currentName->properties['latin_genus'] + ' ' + $currentName->properties['latin_s1'];
            $parentTaxonNameString .= $speciesLayer[0]['rank_abbreviation'] + ' ' + $speciesLayer[0]['latin_name'];
        }

        // $currentTaxonNameId = $this->id;
        $nomenclatrueId = $currentName->nomenclatrue_id;

        $parentTaxonNameId = TaxonName::with(['rank'])
                            ->where('name', $parentTaxonNameString)
                            ->where('nomenclatrue_id', $nomenclatrueId)
                            ->first()->id;

        return [
            'parent_taxon_name_string' => $parentTaxonNameString,
            'parent_taxon_name_id' => $parentTaxonNameId,
        ];
    }
}
