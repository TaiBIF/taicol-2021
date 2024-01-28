<?php


namespace App\Http\Services;


use App\FavoriteMineItem;
use App\Http\Entities\SpecimenEntity;
use App\Http\Entities\TypeSpecimenPropertiesFactory;
use App\Nomenclature;
use App\Person;
use App\Rank;
use App\Reference;
use App\TaxonName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxonNameService
{
    public function __construct(Model $taxonName)
    {
        $this->taxonName = $taxonName;

        $this->ranks = Rank::select(['id', 'order', 'abbreviation'])->get()->keyBy('abbreviation');
    }

    /**
     * 是否存在(unique)的判斷方式為: 法規 + 階層 + name + 命名者 + 文獻
     *
     * @param int $nomenclatureId
     * @param int $rankId
     * @param string $name
     * @param int|null $referenceId
     * @param array $authorIds
     * @return int|null
     */
    public function hasExist(int $nomenclatureId, int $rankId, string $name, int $referenceId = null, array $authorIds): int|null
    {
        $existQuery = TaxonName::query()
            ->with(['authors'])
            ->where('nomenclature_id', $nomenclatureId)
            ->where('rank_id', $rankId)
            ->where('name', $name)
            ->where('reference_id', $referenceId);

        // 若為 update 的話，不能為自己
        if ($this->taxonName->id) {
            $existQuery->where('id', '!=', $this->taxonName->id);
        }

        $taxonNames = $existQuery->get();

        if (!$taxonNames->count()) return false;

        foreach ($taxonNames as $taxonName) {
            $existAuthors = $taxonName->authors->pluck('id')->toArray();

            sort($existAuthors);
            sort($authorIds);

            if ($existAuthors === $authorIds) return $taxonName->id;
        }

        return null;
    }

    /**
     *
     * @param array $data
     * @param array $authors
     * @param array $exAuthors
     * @param array $usage
     * @return Model
     * @throws \Exception
     */
    public function saveAll(array $data, array $authors = [], array $exAuthors = [], array $usage = [])
    {
        DB::beginTransaction();
        try {
            $rank = Rank::findOrFail($data['rank_id']);
            $nomenclature = Nomenclature::findOrFail($data['nomenclature_id']);

            $this->taxonName = $this->create($nomenclature, $rank, $data);

            $this->saveAuthors($authors);
            $this->saveExAuthors($exAuthors);

            if (array_key_exists('reference_id', $usage)) {
                $this->saveReference($usage['reference_id']);
            } else {
                $this->saveReference();
            }

            $isHybrid = (bool) ($data['is_hybrid'] ?? false);
            if ($isHybrid || $rank->key == 'hybrid-formula') {
                $this->saveHybridParents($data['hybrid_parents_id'] ?? []);
            }

            $this->updateRelatedHyBridTaxonName();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());
            throw $exception;
        }

        return $this->taxonName;
    }

    private function create(Nomenclature $nomenclature, Rank $rank, array $data): Model
    {
        $rankGenus = $this->ranks[RANK::KEY_GENUS];
        $rankSpecies = $this->ranks[RANK::KEY_SPECIES];

        $properties = [];

        // 屬以下(模式標本)
        if ($rank->order > $rankGenus->order) {
            $typeSpecimens = $this->formatTypeSpecimens($data['type_specimens'] ?? []);
        } else { // 屬(含)以上(模式學名)
            $properties['type_name'] = $data['type_name'] ?? '';
            $typeSpecimens = [];
        }

        // 種(包含)以下
        if ($nomenclature->group === 'virus') {
            $properties['latin_name'] = $data['latin_name'];
            $properties['latin_s1'] = $data['latin_name'];
        } else if ($rank->order >= $rankSpecies->order) {
            $properties['latin_genus'] = $data['latin_genus'];
            $properties['latin_s1'] = $data['latin_s1'] ?? '';
        } else {
            $properties['latin_name'] = $data['latin_name'];
        }

        $isHybrid = (bool) ($data['is_hybrid'] ?? false);

        $properties['reference_name'] = $data['reference_name'];
        $properties['usage'] = $data['usage'];
        $properties['is_hybrid'] = $isHybrid;

        $properties['species_id'] = $data['species_id'] ?? null;
        $properties['species_layers'] = $data['species_layers'];

        if (isset($data['authors_name']) && $data['authors_name']) {
            $properties['authors_name'] = $data['authors_name'];
        }

        if ($nomenclature->group === 'bacteria') {
            $properties['is_approved_list'] = $data['is_approved_list'];
            $properties['initial_year'] = $data['initial_year'];
        }

        if ($nomenclature->group === 'virus') {
            $properties['genome_composition'] = $data['genome_composition'];
            $properties['host'] = $data['host'];
        }

        $this->taxonName->nomenclature_id = $nomenclature->id;
        $this->taxonName->rank_id = $rank->id;
        $this->taxonName->name = $data['name'];
        $this->taxonName->formatted_authors = $data['formatted_authors'] ?? '';
        $this->taxonName->original_taxon_name_id = $data['original_taxon_name_id'] ?? null;
        $this->taxonName->type_specimens = $typeSpecimens;
        $this->taxonName->properties = $properties;
        $this->taxonName->publish_year = $data['publish_year'];
        $this->taxonName->note = $data['note'] ?? '';
        $this->taxonName->save();

        return $this->taxonName;
    }

    private function formatTypeSpecimens(array $typeSpecimensArray)
    {
        return array_map(function ($typeSpecimen) {
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
    }

    public function saveAuthors(array $authorIds)
    {
        $authors = Person::whereIn('id', $authorIds)
            ->get()
            ->sortBy(function ($model) use ($authorIds) {
                return array_search($model->getKey(), $authorIds);
            })
            ->values()
            ->map(function ($authors, $order) {
                return [
                    'person_id' => $authors->id,
                    'order' => $order
                ];
            });

        $this->taxonName->authors()->detach();
        $this->taxonName->authors()->attach($authors);
    }

    public function saveExAuthors(array $exAuthorIds)
    {
        $exAuthors = Person::whereIn('id', $exAuthorIds)
            ->get()
            ->sortBy(function ($model) use ($exAuthorIds) {
                return array_search($model->getKey(), $exAuthorIds);
            })->values()->map(function ($authors, $order) {
                return [
                    'person_id' => $authors->id,
                    'order' => $order
                ];
            });

        $this->taxonName->exAuthors()->sync($exAuthors);
    }

    public function saveReference(int $referenceId = null)
    {
        if (!$referenceId) {
            $this->taxonName->reference()->dissociate();
            $this->taxonName->save();
            return;
        }

        $reference = Reference::find($referenceId);
        $this->taxonName->reference()->associate($reference);
        $this->taxonName->save();
    }

    public function saveHybridParents(array $hybridParentIds = [])
    {
        $hybridParent1 = $hybridParentIds[0] ?? null;
        $hybridParent2 = $hybridParentIds[1] ?? null;

        $hybridParents = [];

        // 須照順序
        $h1 = TaxonName::find($hybridParent1);
        if ($hybridParent1 && $h1) {
            $hybridParents[$hybridParent1] = ['order' => 0];
        }

        $h2 = TaxonName::find($hybridParent2);
        if ($hybridParent2 && $h2) {
            $hybridParents[$hybridParent2] = ['order' => 1];
        }

        // hybrid-formula 學名特殊處理
        if ($this->taxonName->rank->key === 'hybrid-formula') {
            $this->taxonName->name = "{$h1?->name} × {$h2?->name}";
            $this->taxonName->save();
        }

        $this->taxonName->hybridParents()->sync($hybridParents);
    }

    public function updateRelatedHyBridTaxonName()
    {
        $taxonName = $this->taxonName;

        // 更新有關聯的 hybrid parent name
        $relatedTaxonNames = TaxonName::with(['hybridParents'])
            ->whereHas('hybridParents', function ($query) use ($taxonName) {
                $query->where('parent_taxon_name_id', $taxonName->id);
            })->get();

        foreach ($relatedTaxonNames as $name) {
            $name->name = sprintf('%s × %s', $name->hybridParents[0]->name, $name->hybridParents[1]->name);
            $name->save();
        }
    }

    public function saveToMyFavoriteItem()
    {
        $item = new FavoriteMineItem();
        $item->collectable_type = FavoriteMineItem::TYPE_TAXON_NAME;
        $item->collectable_id = $this->taxonName->id;
        $item->user_id = Auth::user()->id;
        $item->save();
    }
}
