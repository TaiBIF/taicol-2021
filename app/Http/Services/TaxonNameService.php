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
    public function hasTaxonNameExist(int $nomenclatureId, int $rankId, string $name, int $referenceId = null, array $authorIds, bool $is_publish): int|null
    {
        $existQuery = TaxonName::query()
            ->with(['authors'])
            ->where('nomenclature_id', $nomenclatureId)
            ->where('rank_id', $rankId)
            ->where('name', $name)
            ->where('reference_id', $referenceId)
            ->where('is_publish', $is_publish);

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

        // 替代名 / 拼法相異學名
        if ($nomenclature->group !== 'virus') {
            if (isset($data['replacement_name'])){
                $properties['replacement_name'] = $data['replacement_name'];
            }
            if (isset($data['spelling_variation'])){
                $properties['spelling_variation'] = $data['spelling_variation'];
            }
        }

        // 屬以下(模式標本): 種和以下（order >= 40）
        if ($rank->order > $rankGenus->order) {
            $typeSpecimens = $this->formatTypeSpecimens($data['type_specimens'] ?? []);
        } else { // 屬(含)以上(模式學名): 屬以上，包含屬的亞階層（order <= 39）
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

        $replace_words = [' subsp. ',' nothosubsp.',' var. ',' subvar. ',' nothovar. ',' fo. ',' subf. ',' f.sp. ',' race ',' strip ',' m. ',' ab. ',' × '];

        if ($rank->id==34 && $isHybrid == true){

            $search_name =  $data['latin_genus'] . ' ' . $data['latin_s1'];

        } else if ($rank->id==47){
            // 這邊尚未完成

            $search_name = '';

        } else {
        
            $search_name = str_replace($replace_words, ' ', $data['name']);
        }

        $this->taxonName->nomenclature_id = $nomenclature->id;
        $this->taxonName->rank_id = $rank->id;
        $this->taxonName->name = $data['name'];
        $this->taxonName->search_name = $search_name;
        $this->taxonName->formatted_authors = $data['formatted_authors'] ?? '';
        $this->taxonName->original_taxon_name_id = $data['original_taxon_name_id'] ?? null;
        $this->taxonName->type_specimens = $typeSpecimens;
        $this->taxonName->properties = $properties;
        $this->taxonName->publish_year = $data['publish_year'];
        $this->taxonName->note = $data['note'] ?? '';
        $this->taxonName->is_publish = $data['is_publish'] ?? true;
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
            if ($h1?->properties['latin_genus'] == $h2?->properties['latin_genus'] ){
                $now_h2_name = str_replace($h1?->properties['latin_genus']. ' ' ,'',$h2?->name);
                $search_name = "{$h1?->name} {$now_h2_name}";
            } else {
                $search_name = "{$h1?->name} {$h2?->name}";
            }

            $replace_words = [' subsp. ',' nothosubsp.',' var. ',' subvar. ',' nothovar. ',' fo. ',' subf. ',' f.sp. ',' race ',' strip ',' m. ',' ab. ',' × ','× '];
            $search_name = str_replace($replace_words, ' ', $search_name);


            $this->taxonName->search_name =  $search_name ;

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

    public function getAndUpdateObjectGroups() {

        $taxonName = $this->taxonName;

        // 如果有object_group or autonym_group 先找出所有的name_ids (統一用object_group找就可以了 因為就會包含到autonym_group)
        if ($taxonName->object_group != null){
            $updatingNameIds = TaxonName::where('object_group',$taxonName->object_group)->pluck('id')->toArray();
        } else {
            $updatingNameIds = [ $taxonName->id ];
        }

        // 處理object_group

        foreach ($updatingNameIds as $updatingNameId){

            $nowUpdatingName = TaxonName::find($updatingNameId);

            // 重新query一次 取得更新後的
            $objectNameIds = Array();
            array_push($objectNameIds, $nowUpdatingName->id);

            // 自己是別人的original_taxon_name_id

            array_push($objectNameIds, ...TaxonName::where('original_taxon_name_id', '=', $nowUpdatingName->id)
               ->pluck('id')->toArray());


            if (isset($nowUpdatingName->original_taxon_name_id)){
                $originalTaxonNameId = $nowUpdatingName->original_taxon_name_id;
                array_push($objectNameIds, ...TaxonName::where('original_taxon_name_id', '=', $originalTaxonNameId)
                        ->orWhere('id', '=', $originalTaxonNameId)->pluck('id')->toArray());
            }

            if (isset($nowUpdatingName->properties['spelling_variation'])){
                $spellingVariationId = $nowUpdatingName->properties['spelling_variation'];
                array_push($objectNameIds, ...TaxonName::whereRaw('JSON_EXTRACT(properties, "$.spelling_variation")'.  "=" . $spellingVariationId  )
                ->orWhere('id', '=', $spellingVariationId)->pluck('id')->toArray());
            }


            if (isset($nowUpdatingName->properties['replacement_name'])){
                $replacementNameId = $nowUpdatingName->properties['replacement_name'];
                array_push($objectNameIds, ...TaxonName::whereRaw('JSON_EXTRACT(properties, "$.replacement_name")'.  "=" . $replacementNameId  )
                ->orWhere('id', '=', $replacementNameId)->pluck('id')->toArray());
            }


            $objectNameIds = array_unique($objectNameIds);

            // 統一給新的object_group
            if (count($objectNameIds)>1){

                $nowObjectGroups  = TaxonName::whereIn('id', $objectNameIds)->where('object_group','!=',null)->pluck('object_group')->toArray();

                if (count($nowObjectGroups)){
                    // 如果有任何object_group 沿用
                    $nowObjectGroup = $nowObjectGroups[0];
                } else {
                    $nowObjectGroup = TaxonName::max('object_group') + 1;
                }

                TaxonName::whereIn('id',$objectNameIds)->update(['object_group' => $nowObjectGroup]);;

            } else {

                // 把自己的改掉
                // TODO 要先確定有沒有autonym_group

                $nowUpdatingName->object_group = null;
                $nowUpdatingName->save();

            }

        }

        // 處理autonym_group

        foreach ($updatingNameIds as $updatingNameId){

            $autonymNameIds = Array();
            array_push($autonymNameIds, $nowUpdatingName->id);

            # 找到latin genus latin s1 相同 & 且species_layer=latin s1的
            if ($nowUpdatingName->rank_id == 34){
                array_push($autonymNameIds, ...TaxonName::where('nomenclature_id', '=' , $nowUpdatingName->nomenclature_id)
                                            ->WhereRaw('JSON_EXTRACT(properties, "$.latin_genus") = ?', $nowUpdatingName->properties['latin_genus'] )
                                            ->WhereRaw('JSON_EXTRACT(properties, "$.latin_s1") = ?', $nowUpdatingName->properties['latin_s1'] )
                                            ->whereRaw('JSON_LENGTH(JSON_EXTRACT(properties, "$.species_layers")) = 1')
                                            ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[0].latin_name") = ?', $nowUpdatingName->properties['latin_s1'])
                                            ->pluck('id')->toArray()
                );
            } else if ($nowUpdatingName->rank_id > 34 && $nowUpdatingName->rank_id < 47 && count($nowUpdatingName->properties['species_layers']) == 1 ){
                # 1. 自己是種下, 要往上找種 & 往下找種下下
                # 先找種 要先確認自己的後面兩個一樣
                if ($nowUpdatingName->properties['latin_s1']==$nowUpdatingName->properties['species_layers'][0]['latin_name']){
                    array_push($autonymNameIds, ...TaxonName::where('nomenclature_id', '=' , $nowUpdatingName->nomenclature_id)
                                    ->where('rank_id', '=' , 34)
                                    ->WhereRaw('JSON_EXTRACT(properties, "$.latin_genus") = ?', $nowUpdatingName->properties['latin_genus'] )
                                    ->WhereRaw('JSON_EXTRACT(properties, "$.latin_s1") = ?', $nowUpdatingName->properties['latin_s1'] )
                                    ->pluck('id')->toArray()
                    );
                }
                # 再找種下下
                array_push($autonymNameIds, ...TaxonName::where('nomenclature_id', '=' , $nowUpdatingName->nomenclature_id)
                ->WhereRaw('JSON_EXTRACT(properties, "$.latin_genus") = ?', $nowUpdatingName->properties['latin_genus'] )
                ->WhereRaw('JSON_EXTRACT(properties, "$.latin_s1") = ?', $nowUpdatingName->properties['latin_s1'] )
                ->whereRaw('JSON_LENGTH(JSON_EXTRACT(properties, "$.species_layers")) = 2')
                ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[0].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][0]['latin_name'])
                ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[1].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][0]['latin_name'])
                ->pluck('id')->toArray()
                );

            } else if ($nowUpdatingName->rank_id > 34 && $nowUpdatingName->rank_id < 47 && count($nowUpdatingName->properties['species_layers']) == 2 ){
                # 2. 自己是種下下, 要往上找種下 & 往下找種下下下
                # 先找種下 要先確認自己的後面兩個一樣
                if ($nowUpdatingName->properties['species_layers'][0]['latin_name']==$nowUpdatingName->properties['species_layers'][1]['latin_name']){
                    array_push($autonymNameIds, ...TaxonName::where('nomenclature_id', '=' , $nowUpdatingName->nomenclature_id)
                    ->WhereRaw('JSON_EXTRACT(properties, "$.latin_genus") = ?', $nowUpdatingName->properties['latin_genus'] )
                    ->WhereRaw('JSON_EXTRACT(properties, "$.latin_s1") = ?', $nowUpdatingName->properties['latin_s1'] )
                    ->whereRaw('JSON_LENGTH(JSON_EXTRACT(properties, "$.species_layers")) = 1')
                    ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[0].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][0]['latin_name'])
                    ->pluck('id')->toArray()
                    );
                }
                # 再找種下下下
                array_push($autonymNameIds, ...TaxonName::where('nomenclature_id', '=' , $nowUpdatingName->nomenclature_id)
                ->WhereRaw('JSON_EXTRACT(properties, "$.latin_genus") = ?', $nowUpdatingName->properties['latin_genus'] )
                ->WhereRaw('JSON_EXTRACT(properties, "$.latin_s1") = ?', $nowUpdatingName->properties['latin_s1'] )
                ->whereRaw('JSON_LENGTH(JSON_EXTRACT(properties, "$.species_layers")) = 3')
                ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[0].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][0]['latin_name'])
                ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[1].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][0]['latin_name'])
                ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[2].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][0]['latin_name'])
                ->pluck('id')->toArray()
                );
            } else if ($nowUpdatingName->rank_id > 34 && $nowUpdatingName->rank_id < 47 && count($nowUpdatingName->properties['species_layers']) == 3 ){
                # 3. 自己是種下下下, 要往上找種下下 (目前沒有再往下的例子)
                # 先找種下下 要先確認自己的後面兩個一樣
                if ($nowUpdatingName->properties['species_layers'][1]['latin_name']==$nowUpdatingName->properties['species_layers'][2]['latin_name']){
                    array_push($autonymNameIds, ...TaxonName::where('nomenclature_id', '=' , $nowUpdatingName->nomenclature_id)
                    ->WhereRaw('JSON_EXTRACT(properties, "$.latin_genus") = ?', $nowUpdatingName->properties['latin_genus'] )
                    ->WhereRaw('JSON_EXTRACT(properties, "$.latin_s1") = ?', $nowUpdatingName->properties['latin_s1'] )
                    ->whereRaw('JSON_LENGTH(JSON_EXTRACT(properties, "$.species_layers")) = 2')
                    ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[0].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][1]['latin_name'])
                    ->whereRaw('JSON_EXTRACT(properties, "$.species_layers[1].latin_name") = ?',  $nowUpdatingName->properties['species_layers'][1]['latin_name'])
                    ->pluck('id')->toArray()
                    );
                }
            }

            $autonymNameIds = array_unique($autonymNameIds);


            if (count($autonymNameIds)>1){

                # 如果在種階層有同名的問題 必須多判斷作者相同的才會是一樣的autonym group
                $checkNames = TaxonName::select('name', DB::raw('COUNT(*) as count'))->whereIn('id',$autonymNameIds)->where('rank_id',34)->groupBy('name')->get()->toArray();

                foreach ($checkNames as $csn) {
                    
                    if ($csn['count'] > 1){
                        $rows = TaxonName::whereIn('id', $autonymNameIds)->where('formatted_authors','!=','')->whereNotNull('formatted_authors')->get()->toArray();
                        if (count($rows) == count($autonymNameIds)){
                            $subAuthor = array_column(array_filter($rows, fn($row) => $row['rank_id'] > 34), 'formatted_authors');
                            $subAuthor = $subAuthor[0];
                            $autonymNameIds = array_column(array_filter($rows, fn($row) => str_contains($row['formatted_authors'],$subAuthor)),'id');
                        }
                    }

                }

                // 統一給新的autonym_group
                $nowAutonymGroup = TaxonName::max('autonym_group') + 1;

                $nowObjectGroups  = TaxonName::whereIn('id', $autonymNameIds)->where('object_group','!=',null)->pluck('object_group')->toArray();

                if (count($nowObjectGroups)){
                    // 如果有任何object_group 沿用
                    $nowObjectGroup = $nowObjectGroups[0];
                } else {
                    $nowObjectGroup = TaxonName::max('object_group') + 1;
                }

                // $nowObjectGroup = TaxonName::max('object_group') + 1;


                TaxonName::whereIn('id',$autonymNameIds)->update(['autonym_group' => $nowAutonymGroup, 'object_group' => $nowObjectGroup]);;


            } else {
                // $nowAutonymGroup = null;

                // 如果沒有object_group的話 上面那段應該就會被改掉了
                // $nowObjectGroup = null;
                $nowAutonymGroup = null;
                $nowUpdatingName->autonym_group = $nowAutonymGroup;
                // $nowUpdatingName->object_group = $nowObjectGroup;
                $nowUpdatingName->save();

            }

        }

    }
    
}
