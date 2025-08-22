<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 專門為 UsagePreview 服務設計的 Resource
 * 將複雜的 TaxonNameResource 轉換為 UsagePreview 服務所需的簡化格式
 */
class UsagePreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // 如果已經是數組，直接返回
        if (is_array($this->resource)) {
            return $this->resource;
        }

        // 轉換原始的 TaxonNameResource 為簡化格式
        $data = $this->resource instanceof JsonResource ? 
            $this->resource->toArray($request) : 
            (method_exists($this->resource, 'toArray') ? $this->resource->toArray() : (array) $this->resource);

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? '',
            'nomenclature' => [
                'group' => $data['nomenclature']['group'] ?? ''
            ],
            'rank' => [
                'id' => $data['rank']['id'] ?? null,
                'key' => $data['rank']['key'] ?? '',
                'order' => $data['rank']['order'] ?? 0,
                'abbreviation' => $data['rank']['abbreviation'] ?? ''
            ],
            'authors' => $this->convertPersonCollection($data['authors'] ?? []),
            'ex_authors' => $this->convertPersonCollection($data['ex_authors'] ?? []),
            'publish_year' => $data['publish_year'] ?? '',
            'original_taxon_name' => $this->convertTaxonName($data['original_taxon_name'] ?? null),
            'genus_taxon_name' => $this->convertTaxonName($data['genus_taxon_name'] ?? null),
            'species' => $this->convertTaxonName($data['species'] ?? null),
            'species_layers' => $this->convertSpeciesLayers($data['species_layers'] ?? []),
            'hybrid_parents' => $this->convertHybridParents($data['hybrid_parents'] ?? []),
            'properties' => [
                'latin_name' => $data['properties']['latin_name'] ?? '',
                'latin_genus' => $data['properties']['latin_genus'] ?? '',
                'latin_s1' => $data['properties']['latin_s1'] ?? '',
                'is_hybrid' => $data['properties']['is_hybrid'] ?? false,
                'is_approved_list' => $data['properties']['is_approved_list'] ?? false,
                'initial_year' => $data['properties']['initial_year'] ?? '',
                'authors_name' => $data['properties']['authors_name'] ?? ''
            ]
        ];
    }

    /**
     * 轉換 PersonCollection 為數組
     */
    protected function convertPersonCollection($persons)
    {
        if (empty($persons)) {
            return [];
        }

        return collect($persons)->map(function ($person) {
            if (is_array($person)) {
                return [
                    'id' => $person['id'] ?? null,
                    'first_name' => $person['first_name'] ?? '',
                    'middle_name' => $person['middle_name'] ?? '',
                    'last_name' => $person['last_name'] ?? '',
                    'abbreviation_name' => $person['abbreviation_name'] ?? ''
                ];
            }
            
            return [
                'id' => $person->id ?? null,
                'first_name' => $person->first_name ?? '',
                'middle_name' => $person->middle_name ?? '',
                'last_name' => $person->last_name ?? '',
                'abbreviation_name' => $person->abbreviation_name ?? ''
            ];
        })->toArray();
    }

    /**
     * 轉換 TaxonName 為簡化格式
     */
    protected function convertTaxonName($taxonName)
    {
        if (empty($taxonName)) {
            return null;
        }

        if (is_array($taxonName)) {
            return [
                'id' => $taxonName['id'] ?? null,
                'name' => $taxonName['name'] ?? '',
                'nomenclature' => [
                    'group' => $taxonName['nomenclature']['group'] ?? ''
                ],
                'rank' => $taxonName['rank'] ?? [],
                'authors' => $this->convertPersonCollection($taxonName['authors'] ?? []),
                'ex_authors' => $this->convertPersonCollection($taxonName['ex_authors'] ?? []),
                'publish_year' => $taxonName['publish_year'] ?? '',
                'properties' => [
                    'latin_name' => $taxonName['properties']['latin_name'] ?? '',
                    'latin_genus' => $taxonName['properties']['latin_genus'] ?? '',
                    'latin_s1' => $taxonName['properties']['latin_s1'] ?? ''
                ]
            ];
        }

        return [
            'id' => $taxonName->id ?? null,
            'name' => $taxonName->name ?? '',
            'nomenclature' => [
                'group' => $taxonName->nomenclature->group ?? ''
            ],
            'rank' => $taxonName->rank ?? [],
            'authors' => $this->convertPersonCollection($taxonName->authors ?? []),
            'ex_authors' => $this->convertPersonCollection($taxonName->ex_authors ?? []),
            'publish_year' => $taxonName->publish_year ?? '',
            'properties' => [
                'latin_name' => $taxonName->properties['latin_name'] ?? '',
                'latin_genus' => $taxonName->properties['latin_genus'] ?? '',
                'latin_s1' => $taxonName->properties['latin_s1'] ?? ''
            ]
        ];
    }

    /**
     * 轉換 species layers
     */
    protected function convertSpeciesLayers($layers)
    {
        if (empty($layers)) {
            return [];
        }

        return collect($layers)->map(function ($layer) {
            return [
                'rank' => [
                    'abbreviation' => $layer['rank']['abbreviation'] ?? ''
                ],
                'latin_name' => $layer['latin_name'] ?? ''
            ];
        })->toArray();
    }

    /**
     * 轉換 hybrid parents
     */
    protected function convertHybridParents($parents)
    {
        if (empty($parents)) {
            return [];
        }

        return collect($parents)->map(function ($parent) {
            return $this->convertTaxonName($parent);
        })->toArray();
    }
}