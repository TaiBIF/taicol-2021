<?php

namespace App\Http\Resources;

use App\Country;
use App\Person;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageCollection extends JsonResource
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

        return [
            'id' => $this->id,
            'is_title' => $this->is_title,
            'is_indent' => $this->is_indent,
            'parent_taxon_name' => $this->parentTaxonName,
            'taxon_name' => TaxonNameCollection::collection([$this->taxonName])[0],
            'status' => $this->status,
            'type_specimens' => collect($this->type_specimens)->map(function($typeSpecimen) {
                $typeSpecimen['country'] = isset($typeSpecimen['country_id']) ? Country::find($typeSpecimen['country_id']) : null;
                $typeSpecimen['collectors'] = PersonCollection::collection(Person::whereIn('id', $typeSpecimen['collector_ids'] ?? [])->get());
                return $typeSpecimen;
            }),
            'type_name' => $typeName,
            'properties' => $this->properties,
            'per_usages' => $perUsages->map(function($u) {
                $u['target'] = isset($u['reference_id']) ? Reference::with('authors')->find($u['reference_id']) : null;
                return $u;
            }),
            'name_remark' => $this->name_remark,
            'custom_name_remark' => $this->custom_name_remark,
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
