<?php

namespace App\Http\Services;

use App\Http\Entities\SpecimenEntity;
use App\Http\Entities\TypeSpecimenPropertiesFactory;
use App\Person;
use App\Rank;
use App\Reference;
use App\ReferenceUsage;
use App\TaxonName;
use App\TaxonNameHybridParent;

class TaxonNameService
{
    protected $nomenclatureId;
    protected $rankId;
    protected $rank;
    protected $formattedName = '';
    protected $authors = [];
    protected $exAuthors = [];
    protected $formattedAuthors = '';
    protected $formattedExAuthors = '';
    protected $usage = [];
    protected $properties = [];
    protected $typeSpecimens = [];
    protected $originalTaxonName;
    protected $hybridParents = [];
    protected $reference;

    public function setAllProperties($data): self
    {
        $this->nomenclatureId = $data['nomenclature_id'];
        $this->rankId = $data['rank_id'];
        $this->rank = Rank::find($this->rankId);
        $this->name = $data['name'];
        $this->formattedName = $data['formatted_name'];
        $this->usage = $data['usage'];
        $this->publishYear = $data['publish_year'];
        $this->note = $data['note'];

        $authors = $data['authors'];
        $this->authors = Person::whereIn('id', $data['authors'])->get()->sortBy(function($model) use ($authors){
            return array_search($model->getKey(), $authors);
        });
        $this->exAuthors = Person::whereIn('id', $data['ex_authors'])->get();
        $this->originalTaxonName = TaxonName::find($data['original_taxon_name_id']);
        $this->formattedAuthors = $data['formatted_authors'];
        $this->setTypeSpecimens($data['type_specimens']);

        if ($data['is_hybrid_formula'] || $this->rank->key == 'hybrid-formula') {
            $this->hybridParents = TaxonName::whereIn('id', array_map(function ($parent) {
                return $parent['id'];
            }, $data['hybrid_parents']))->pluck('id');
        }

        $this->reference = isset($data['usage']['reference_id']) ? Reference::find($data['usage']['reference_id']) : null;

        $this->properties = [];

        if ($data['latin_genus']) {
            $this->properties['latin_genus'] = $data['latin_genus'];
        }

        $this->properties['reference_name'] = $data['reference_name'];
        $this->properties['usage'] = $data['usage'];

        $ranks = Rank::whereIn('abbreviation', array_map(function ($layer) {
            return $layer['rank_abbreviation'];
        }, $data['species_layers']))->get()->keyBy('abbreviation');

        $this->speciesLayers = array_map(function ($layer) use ($ranks) {
            return [
                'rank_abbreviation' => $ranks[$layer['rank_abbreviation']],
                'latin_name' => $layer['latin_name'],
            ];
        }, $data['species_layers']);

        $this->properties['species_id'] = $data['species_id'] ?? null;
        $this->properties['latin_s1'] = $data['latin_s1'] ?? '';
        $this->properties['species_layers'] = $data['species_layers'];

        return $this;
    }

    public function setTypeSpecimens(array $typeSpecimensArray): self
    {
        $this->typeSpecimens = array_map(function ($typeSpecimen) {
            $newTypeSpecimen = [];
            $newTypeSpecimen['use'] = $typeSpecimen['use'];
            $newTypeSpecimen['kind'] = $typeSpecimen['kind'];

            $newTypeSpecimen = array_merge($newTypeSpecimen, TypeSpecimenPropertiesFactory::prepareProperties($typeSpecimen['kind'])
                ->setProperties($typeSpecimen)
                ->toArray());

            $newTypeSpecimen['specimens'] = array_map(function ($specimen) {
                $newSpecimen = new SpecimenEntity();
                $newSpecimen->setProperties($specimen);
                return $newSpecimen->toArray();
            }, $typeSpecimen['specimens']);

            return $newTypeSpecimen;
        }, $typeSpecimensArray);
        return $this;
    }

    public function saveAll(TaxonName $taxonName = null)
    {
        $taxonName = $taxonName ? $taxonName : new TaxonName();
        $taxonName->nomenclature_id = $this->nomenclatureId;
        $taxonName->rank_id = $this->rankId;
        $taxonName->name = $this->name;
        $taxonName->formatted_name = $this->formattedName;
        $taxonName->formatted_authors = $this->formattedAuthors ?? '';
        $taxonName->original_taxon_name_id = $this->originalTaxonName->id ?? null;
        $taxonName->type_specimens = $this->typeSpecimens ?? [];
        $taxonName->properties = $this->properties;
        $taxonName->publish_year = $this->publishYear;
        $taxonName->note = $this->note ?? '';
        $taxonName->save();

        // authors
        $taxonName->authors()->detach();
        $taxonName->authors()->attach($this->authors);

        // exAuthors
        $taxonName->exAuthors()->sync($this->exAuthors);

        // reference
        if ($this->reference) {
            $taxonName->reference()->associate($this->reference);
        } else {
            $taxonName->reference()->dissociate();
        }
        $taxonName->save();

        // save hybrid parent
        $taxonName->hybridParents()->sync($this->hybridParents);

        // 更新有關聯的 hybrid parent name
        $relatedTaxonNames = TaxonName::with(['hybridParents'])
            ->whereHas('hybridParents', function ($query) use ($taxonName) {
                $query->where('parent_taxon_name_id', $taxonName->id);
            })->get();

        foreach ($relatedTaxonNames as $name) {
            $name->name = sprintf('%s x %s', $name->hybridParents[0]->name, $name->hybridParents[1]->name);
            $name->formatted_name = sprintf('%s x %s', $name->hybridParents[0]->formatted_name, $name->hybridParents[1]->formatted_name);
            $name->save();
        }

        return $taxonName;
    }
}
