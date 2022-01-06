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
        $this->name = trim(preg_replace('!\s+!', ' ', str_replace("\n", '', $data['name'])));
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

        $isHybridFormula = $data['is_hybrid_formula'] ?? false;

        if ($isHybridFormula || $this->rank->key == 'hybrid-formula') {
            $hybridParents = TaxonName::whereIn('id', array_map(function ($parent) {
                return $parent['id'] ?? null;
            }, $data['hybrid_parents']))
                ->get()
                ->keyBy('id');

            if ($hybridParents[$data['hybrid_parents'][0]['id']] ?? null) {
                $this->hybridParents[$data['hybrid_parents'][0]['id']] = ['order' => 0];
            }

            if ($hybridParents[$data['hybrid_parents'][1]['id']] ?? null) {
                $this->hybridParents[$data['hybrid_parents'][1]['id']] = ['order' => 1];
            }
        }

        $this->reference = isset($data['usage']['reference_id']) ? Reference::find($data['usage']['reference_id']) : null;

        $this->properties = [];

        $ranks = Rank::select(['id', 'order', 'abbreviation'])->get()->keyBy('abbreviation');

        $rankGenus = $ranks[RANK::KEY_GENUS];
        $rankSpecies = $ranks[RANK::KEY_SPECIES];

        // 屬以下(模式標本)
        if ($this->rank->order > $rankGenus->order) {
            $this->setTypeSpecimens($data['type_specimens']);
        } else { // 屬(含)以上(模式學名)
            $this->properties['type_name'] = $data['type_name'] ?? '';
            $this->setTypeSpecimens([]);
        }

        // 種(包含)以下
        if ($this->rank->order >= $rankSpecies->order) {
            $this->properties['latin_genus'] = $data['latin_genus'];
            $this->properties['latin_s1'] = $data['latin_s1'] ?? '';
        } else {
            $this->properties['latin_name'] = $data['latin_name'];
        }

        $this->properties['reference_name'] = $data['reference_name'];
        $this->properties['usage'] = $data['usage'];
        $this->properties['is_hybrid_formula'] = (bool) $isHybridFormula;

        $this->properties['species_id'] = $data['species_id'] ?? null;
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
            $name->save();
        }

        return $taxonName;
    }
}
