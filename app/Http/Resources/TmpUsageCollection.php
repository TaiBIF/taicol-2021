<?php

namespace App\Http\Resources;

use App\Country;
use App\Person;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class TmpUsageCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $perUsages = collect($this->per_usages);

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

        $typeSpecimens = collect($this->type_specimens) ?? [];

        $typeSpecimens = (count($typeSpecimens) > 0) ? $typeSpecimens->map(function($typeSpecimen) {
            $typeSpecimen['country'] = isset($typeSpecimen['country_id']) ? Country::find($typeSpecimen['country_id']) : null;
            $typeSpecimen['collectors'] = PersonCollection::collection(Person::whereIn('id', $typeSpecimen['collector_ids'] ?? [])->get());
            return $typeSpecimen;
        }) : [];

        return [
            'id' => $this->id,
            'group' => $this->group,
            'parent_taxon_name' => $this->parentTaxonName,
            'taxon_name' => TaxonNameSimpleSubResource::collection([$this->taxonName])[0],
            'status' => $this->status,
            'type_specimens' => $typeSpecimens,
            'type_name' => $typeName,
            'properties' => $this->properties,
            'per_usages' => $perUsages->map(function($u) {
                $u['target'] = isset($u['reference_id']) ? Reference::with('authors')->find($u['reference_id']) : null;
                return $u;
            }),
        ];
    }
}
