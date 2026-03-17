<?php

namespace App\Http\Controllers;

use App\AllModel;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\TaxonNameListCollection;
use App\Person;
use App\Reference;
use App\TaxonName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use IntlChar;

function unicode_to_plain($text) {
    $plain_text = '';
    
    for ($i = 0; $i < mb_strlen($text, 'UTF-8'); $i++) {
        $char = mb_substr($text, $i, 1, 'UTF-8');
        
        // 獲取 Unicode 名稱
        $name = IntlChar::charName($char);
        
        if (strpos($name, 'MATHEMATICAL') !== false) {
            // 取最後一個單詞（對應的普通字母）
            $parts = explode(' ', $name);
            $letter = strtolower(end($parts)); // 預設轉小寫
            
            // 如果名稱包含 "CAPITAL"，則轉大寫
            if (strpos($name, 'CAPITAL') !== false) {
                $letter = strtoupper($letter);
            }
            
            $plain_text .= $letter;
        } else {
            $plain_text .= $char; // 保持原字元
        }
    }
    
    return $plain_text;
}



/**
 * Class SearchController
 * @package App\Http\Controllers
 */
class SearchController extends Controller
{
    /**
     * 針對單一 keyword，自所有 model 中取得結果
     * for 下拉選單自動填入
     * @param Request $request
     * @return JsonResponse
    */
    public function index(Request $request)
    {
        $type = $request->get('type', '');
        $keyword = trim(strtolower($request->get('keyword', '')));
        $keyword = preg_replace('/[+\-><\(\)~*\"@]/', ' ', $keyword);

        $personClean = preg_replace('/[\p{P}\p{S}]/u', ' ', $keyword);
        $personWords = array_filter(explode(' ', $personClean));

        $abbrNormalized = preg_replace('/\.\s+/', '.', trim(strtolower($request->get('keyword', ''))));
        $abbrNormalized = preg_replace('/[+\-><\(\)~*\"@]/', '', $abbrNormalized);
        $safeAbbrNormalized = addslashes($abbrNormalized);
        $abbrAlpha = preg_replace('/[^a-z]/u', '', $abbrNormalized);

        $personWhere = function ($query) use ($personWords, $abbrNormalized, $abbrAlpha) {
            $query->where(function ($q) use ($personWords) {
                foreach ($personWords as $word) {
                    $q->where('search_raw', 'like', '%' . $word . '%');
                }
            });
            if (strlen($abbrAlpha) >= 1) {
                $query->orWhereRaw("LOWER(REPLACE(abbreviation_name, ' ', '')) LIKE ?", ['%' . $abbrNormalized . '%'])
                      ->orWhereRaw("LOWER(REGEXP_REPLACE(abbreviation_name, '[^a-zA-Z]', '')) LIKE ?", ['%' . $abbrAlpha . '%']);
            }
        };

        $safeKeyword = addslashes($keyword);

        $orderForUnion = "CASE 
            WHEN LOWER(`title`) = '{$safeKeyword}' THEN 0 
            WHEN LOWER(`title`) LIKE '{$safeKeyword}%' THEN 1 
            WHEN LOWER(`title`) LIKE '% {$safeKeyword}' THEN 2 
            WHEN LOWER(REPLACE(abbreviation_name, ' ', '')) = '{$safeAbbrNormalized}' THEN 3
            WHEN LOWER(REPLACE(abbreviation_name, ' ', '')) LIKE '%{$safeAbbrNormalized}%' THEN 4
            WHEN LOWER(abbreviation_name) LIKE '{$safeAbbrNormalized}%' THEN 5
            WHEN LOWER(last_name) = '{$safeKeyword}' OR LOWER(first_name) = '{$safeKeyword}' THEN 6
            WHEN LOWER(last_name) LIKE '{$safeKeyword}%' OR LOWER(first_name) LIKE '{$safeKeyword}%' THEN 7
            ELSE 8 END";

        $orderForPerson = "CASE 
            WHEN LOWER(REPLACE(abbreviation_name, ' ', '')) = '{$safeAbbrNormalized}' THEN 0
            WHEN LOWER(REPLACE(abbreviation_name, ' ', '')) LIKE '%{$safeAbbrNormalized}%' THEN 1
            WHEN LOWER(abbreviation_name) LIKE '{$safeAbbrNormalized}%' THEN 2
            WHEN LOWER(last_name) = '{$safeKeyword}' OR LOWER(first_name) = '{$safeKeyword}' THEN 3
            WHEN LOWER(last_name) LIKE '{$safeKeyword}%' OR LOWER(first_name) LIKE '{$safeKeyword}%' THEN 4
            WHEN LOWER(`title`) = '{$safeKeyword}' THEN 5 
            WHEN LOWER(`title`) LIKE '{$safeKeyword}%' THEN 6 
            WHEN LOWER(`title`) LIKE '% {$safeKeyword}' THEN 7 
            ELSE 8 END";

        if ($type == 'taxon-names') {

            $replace_words = [' subgen. ', ' sect. ', ' subsect. ', ' subsp. ',' nothosubsp.',' var. ',' subvar. ',' nothovar. ',' fo. ',' subf. ',' f.sp. ',' race ',' strip ',' m. ',' ab. ',' × '];
            $keyword_raw = preg_replace('/[+\-><\(\)~*\"\'@]/', '', $keyword);
            $keyword_wo_rank = str_replace($replace_words, ' ', $keyword_raw);

            $queryA = TaxonName::selectRaw("'taxon_name' as n, id, name as title, search_name as search_title, '' as abbreviation_name, '' as last_name, '' as first_name")
                ->where('deleted_at', null)
                ->where('is_publish', 1)
                ->where(function($query) use ($keyword, $keyword_wo_rank){
                    $query
                    ->whereRaw("MATCH(search_name) AGAINST (? IN BOOLEAN MODE)", ["*$keyword_wo_rank*"])
                    ->orWhereRaw("MATCH(`name`) AGAINST (? IN BOOLEAN MODE)", ["*$keyword*"]);
                })
                ->limit(6);

            $queryB = Person::selectRaw("'person' as n, id, concat(last_name,', ',first_name,' ',middle_name) as title, concat(last_name,', ',first_name,' ',middle_name) as search_title, abbreviation_name, last_name, first_name")
                ->where($personWhere)
                ->limit(6);

            $query = $queryA->union($queryB)
                ->orderByRaw($orderForUnion);

        } else if ($type === 'references') {

            $queryA = Reference::selectRaw("'reference' as n, id, title, '' as abbreviation_name, '' as last_name, '' as first_name")
                ->where('deleted_at', null)
                ->where('is_publish', 1)
                ->whereRaw("MATCH(title) AGAINST (? IN BOOLEAN MODE)", ["*$keyword*"])
                ->limit(6);

            $queryB = Person::selectRaw("'person' as n, id, concat(last_name,', ',first_name,' ',middle_name) as title, abbreviation_name, last_name, first_name")
                ->where('deleted_at', null)
                ->where($personWhere)
                ->limit(6);

            $query = $queryA->union($queryB)
                ->orderByRaw($orderForUnion);

        } else if ($type === 'persons') {

            $query = Person::selectRaw("'person' as n, id, concat(last_name,', ',first_name,' ',middle_name) as title, abbreviation_name, last_name, first_name")
                ->where('deleted_at', null)
                ->where($personWhere)
                ->orderByRaw($orderForPerson)
                ->orderBy('last_name', 'asc');

        } else {
            return response()->json(['data' => []]);
        }

        $modelGroup = $query->limit(6)->get()->groupBy('n');

        $all = [];
        foreach ($modelGroup as $n => $models) {

            if ($n === 'person') {
                $persons = PersonCollection::collection(Person::whereIn('id', $models->pluck('id'))->get()->load('country'))->keyBy('id');
                foreach ($models as $model) {
                    if (isset($persons[$model->id])) {
                        $all[] = [
                            'type' => 'person',
                            'data' => $persons[$model->id]->jsonSerialize(),
                            'title' => $persons[$model->id]->jsonSerialize()['full_name'],
                        ];
                    }
                }
            }

            if ($n === 'taxon_name') {
                $taxonNames = TaxonNameListCollection::collection(
                    TaxonName::select('taxon_names.*')
                    ->with([
                        'authors.country', 'exAuthors.country', 'reference',
                        'nomenclature',
                        'rank',
                        'originalTaxonName.authors',
                        'originalTaxonName.exAuthors',
                        'hybridParents',
                    ])
                    ->leftJoin('ranks', 'taxon_names.rank_id', 'ranks.id')
                    ->whereIn('taxon_names.id', $models->pluck('id'))
                    ->get()
                )->keyBy('id');

                foreach ($models as $model) {
                    if (isset($taxonNames[$model->id])) {
                        $all[] = [
                            'type' => 'taxon-name',
                            'data' => $taxonNames[$model->id],
                            'title' => $taxonNames[$model->id]->name,
                        ];
                    }
                }
            }

            if ($n === 'reference') {
                $references = ReferenceCollection::collection(
                    Reference::with(['authors'])
                        ->whereIn('id', $models->pluck('id'))
                        ->get()
                )->keyBy('id');

                foreach ($models as $model) {
                    if (isset($references[$model->id])) {
                        $all[] = [
                            'type' => 'reference',
                            'data' => $references[$model->id],
                            'title' => $references[$model->id]->title,
                        ];
                    }
                }
            }
        }

        return response()->json(['data' => $all]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reference(Request $request)
    {
        $perPage = $request->get('perPage', 30);
        $perPage = $perPage > 30 ? 30 : $perPage;

        $keywords = $this->getKeywords($request->get('keywords', ''));

        $strict = (bool) $request->get('strict', true);

        if ($strict && count($keywords) === 0) {
            return response()->json([
                'total' => 0,
                'data' => [],
                'per_page' => $perPage,
                'current_page' => 0,
                'last_page' => 0,
            ]);
        }

        $referenceQuery = Reference::with(['authors']);

        try {
            $keywords->each(function ($keyword) use ($referenceQuery, $perPage) {
                $type = $keyword['type'];
                $word = trim(strtoLower($keyword['name']));

                switch ($type) {
                    case $type === 'text' || $type === 'reference':
                        $referenceQuery
                            ->where('is_publish', '=', 1)
                            ->where('type', '!=', Reference::TYPE_BACKBONE)
                            ->where('type', '!=', Reference::TYPE_SUPER_BACKBONE)
                            ->where(function ($referenceQuery) use ($word) {
                                $referenceQuery
                                    ->whereRaw('title LIKE ? ', '%' . $word . '%')
                                    ->orWhereRaw('subtitle LIKE ? ', '%' . $word . '%');
                            })->orWhereHas('book', function ($query) use ($word) {
                                $query
                                ->where('is_publish', '=', 1)
                                ->where('type', '!=', Reference::TYPE_SUPER_BACKBONE)
                                ->whereRaw('title LIKE ? ', '%' . $word . '%');
                            });
                        break;
                    case $type === 'person':
                        $referenceQuery->whereHas('authors', function ($query) use ($word) {
                            $query->whereRaw('CONCAT(last_name, \', \', first_name, \' \', middle_name) like ?', '%' . $word . '%');
                        });
                        break;
                    default:
                        throw new \Exception('not exist type');
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'total' => 0,
                'data' => [],
                'per_page' => $perPage,
                'current_page' => 0,
                'last_page' => 0,
            ]);
        }


        if ($request->get('sortby') === 'type') {
            $referenceQuery->orderBy('type', $request->get('direction'));
        }

        if ($request->get('sortby') === 'publish_year') {
            $referenceQuery->orderBy('publish_year', $request->get('direction'));
        }

        if ($request->get('sortby') === 'article_title') {
            $referenceQuery->orderBy('properties->article_title', $request->get('direction'));
        }

        $references = $referenceQuery
            ->where('is_publish', '=', 1)
            ->where('type', '!=', Reference::TYPE_BACKBONE)
            ->where('type', '!=', Reference::TYPE_SUPER_BACKBONE)
            ->paginate($perPage);

        return response()->json([
            'total' => $references->total(),
            'data' => ReferenceCollection::collection($references->items()),
            'per_page' => $references->perPage(),
            'current_page' => $references->currentPage(),
            'last_page' => $references->lastPage(),
        ]);
    }

    /**
     * @param $keywordsString String
     * @return Collection
     */
    private function getKeywords($keywordsString): Collection
    {
        $keywordString = trim(mb_strtolower($keywordsString, 'utf8'));
        $keywords = $keywordString ? collect(explode('@', $keywordString))->map(function ($keywordOS) {
            [$type, $name] = explode(': ', $keywordOS);

            return [
                'type' => trim($type),
                'name' => unicode_to_plain(trim($name)),
            ];
        }) : collect([]);
        return $keywords;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function taxonName(Request $request)
    {
        $perPage = $request->get('perPage', 30);
        $perPage = $perPage > 30 ? 30 : $perPage;

        $keywords = $this->getKeywords($request->get('keywords', ''));
        $strict = (bool) $request->get('strict', true);

        if ($strict && count($keywords) === 0) {
            return response()->json([
                'total' => 0,
                'data' => [],
                'per_page' => $perPage,
                'current_page' => 0,
                'last_page' => 0,
            ]);
        }

        $query = TaxonName::select('taxon_names.*')->where('is_publish',1)->with([
            'authors.country', 'exAuthors.country', 'reference',
            'nomenclature',
            'rank',
            'originalTaxonName.authors',
            'originalTaxonName.exAuthors',
            'hybridParents',
            'usages' => function ($query) {
                $query->select(['reference_usages.*', 'references.publish_year', 'references.id'])
                    ->where('status', 'accepted')
                    ->leftJoin('references', 'references.id', '=', 'reference_usages.reference_id')
                    ->where('reference_usages.properties->common_names', 'like', '%zh-tw%')
                    ->orderBy('references.publish_year');
            },
        ])
            ->leftJoin('ranks', 'taxon_names.rank_id', 'ranks.id');

        try {
            $keywords->each(function ($keyword) use ($query, $perPage) {
                $type = $keyword['type'];
                $word = $keyword['name'];

                switch ($type) {
                    case $type === 'text' || $type === 'taxon-name':
                        $query->where(function ($query) use ($word) {

                            $replace_words = [' subgen. ', ' sect. ', ' subsect. ', ' subsp. ',' nothosubsp.',' var. ',' subvar. ',' nothovar. ',' fo. ',' subf. ',' f.sp. ',' race ',' strip ',' m. ',' ab. ',' × '];
                            $word = preg_replace('/[+\-><\(\)~*\"\'@]/', '', $word);
                            $word_wo_rank = str_replace($replace_words, ' ', $word);

                            $query->whereRaw('search_name like ? ', '%' . $word_wo_rank . '%');
                            $query->orWhereRaw( 'name like ? ' , '%' . $word . '%');

                            // Check if the word contains Chinese
                            if (preg_match('/\p{Han}+/u', $word)) {
                                $query->orWhereHas('usages', function ($query) use ($word) {
                                    $query->whereRaw('JSON_EXTRACT(properties, "$.common_names[*].name") like ?', '%' . $word . '%');
                                });
                            }
                        });
                        break;
                    case $type === 'person':
                        $query->whereHas('persons', function ($query) use ($word) {
                            $query->whereRaw('CONCAT(last_name, \', \', first_name, \' \', middle_name) like ?', '%' . $word . '%');
                        });
                        break;
                    case $type === 'person_id':
                        $query->whereHas('authors', function ($query) use ($word) {
                            $query->where('persons.id', (int) $word);
                        });
                        break;
                    case $type === 'reference':
                        $query->whereHas('reference', function ($query) use ($word) {
                            $query->where('title', $word)->orWhere('subtitle', $word);
                        });

                    default:
                        throw new \Exception('not exist type');
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'total' => 0,
                'data' => [],
                'per_page' => $perPage,
                'current_page' => 0,
                'last_page' => 0,
            ]);
        }

        if ($request->get('sortby') === 'rank') {
            $query->orderBy('ranks.order', $request->get('direction'));
        }

        if ($request->get('sortby') === 'name') {
            $query->orderBy('taxon_names.name', $request->get('direction'));
        }

        if ($request->get('sortby') === 'publish_year') {
            $query->orderBy('taxon_names.publish_year', $request->get('direction'));
        }

        $taxonNames = $query->paginate($perPage);

        return response()->json([
            'total' => $taxonNames->total(),
            'data' => TaxonNameListCollection::collection($taxonNames),
            'per_page' => $taxonNames->perPage(),
            'current_page' => $taxonNames->currentPage(),
            'last_page' => $taxonNames->lastPage(),
        ]);
    }


    public function person(Request $request)
    {
        // 人名搜尋頁的搜尋        
        $perPage = $request->get('perPage', 30);
        $perPage = $perPage > 30 ? 30 : $perPage;

        $keywords = $this->getKeywords($request->get('keywords', ''));
        $strict = (bool) $request->get('strict', true);

        if ($strict && count($keywords) === 0) {
            return response()->json([
                'total' => 0,
                'data' => [],
                'per_page' => $perPage,
                'current_page' => 0,
                'last_page' => 0,
            ]);
        }

        $query = Person::query();

        $query->select('*', DB::raw('CONCAT(last_name, \', \', first_name, \' \', middle_name) full_name'));

        try {
            $keywords->each(function ($keyword) use ($query, $perPage) {
                $type = $keyword['type'];
                $word = $keyword['name'];

                switch ($type) {
                    case $type === 'text' || $type === 'person':
                        $query->whereRaw('CONCAT(last_name, \', \', first_name, \' \', middle_name) like ?', '%' . $word . '%')
                            ->orWhere('abbreviation_name', 'like', "%{$word}%")
                            ->orWhere('original_full_name', 'like', "%{$word}%")
                            ->orWhere('other_names', 'like', "%{$word}%");
                        break;
                    default:
                        throw new \Exception('not exist type');
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'total' => 0,
                'data' => [],
                'per_page' => $perPage,
                'current_page' => 0,
                'last_page' => 0,
            ]);
        }


        if ($request->get('sortby') === 'name') {
            $query->orderBy('full_name', $request->get('direction'));
        }

        if ($request->get('sortby') === 'abbreviation_name') {
            $query->orderBy('persons.abbreviation_name', $request->get('direction'));
        }

        if ($request->get('sortby') === 'original_full_name') {
            $query->orderBy('persons.original_full_name', $request->get('direction'));
        }

        $persons = $query->paginate($perPage);

        return response()->json([
            'total' => $persons->total(),
            'data' => PersonCollection::collection($persons),
            'per_page' => $persons->perPage(),
            'current_page' => $persons->currentPage(),
            'last_page' => $persons->lastPage(),
        ]);
    }
}
