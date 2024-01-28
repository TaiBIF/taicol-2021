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

/**
 * Class SearchController
 * @package App\Http\Controllers
 */
class SearchController extends Controller
{
    /**
     * 針對單一 keyword，自所有 model 中取得結果
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $type = $request->get('type', '');
        $keyword = trim(strtolower($request->get('keyword', '')));

        $query = AllModel::where('title', 'like', "%$keyword%");

        if ($type == 'taxon-names') {
            $query->whereIn('n', ['person', 'taxon_name'])
                ->orderByRaw(
                    "CASE WHEN LOWER(`title`) = '{$keyword}' THEN 0 WHEN LOWER(`title`) LIKE '{$keyword}%' THEN 1 WHEN LOWER(`title`) LIKE '% {$keyword}' THEN 2 ELSE 3 END");
        } else if ($type === 'references') {
            $query->whereIn('n', ['person', 'reference']);
        } else if ($type === 'persons') {
            $query->whereIn('n', ['person']);
        } else {
            return response()->json([
                'data' => [],
            ]);
        }

        $modelGroup = $query->limit(6)->get()->groupBy('n');

        $all = [];
        foreach ($modelGroup as $n => $models) {
            if ($n === 'person') {
                $persons = PersonCollection::collection(Person::whereIn('id', $models->pluck('id'))->get()->load('country'))->keyBy('id');
                foreach ($models as $model) {
                    $data['type'] = $model->n;
                    $data['data'] = $persons[$model->id]->jsonSerialize();
                    $data['title'] = $data['data']['full_name'];
                    $all[] = $data;
                }
            }

            if ($n === 'taxon_name') {
                $taxonNames = TaxonNameListCollection::collection(
                    TaxonName::select('taxon_names.*')->with([
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
                    $data['type'] = $model->n;
                    $data['data'] = $taxonNames[$model->id];
                    $data['title'] = $taxonNames[$model->id]->name;
                    $all[] = $data;
                }
            }

            if ($n === 'reference') {
                $references = ReferenceCollection::collection(
                    Reference::with(['authors'])
                        ->whereIn('id', $models->pluck('id'))
                        ->get()
                )->keyBy('id');

                foreach ($models as $model) {
                    $data['type'] = $model->n;
                    $data['data'] = $references[$model->id];
                    $data['title'] = $references[$model->id]->title;
                    $all[] = $data;
                }
            }
        }


        return response()->json([
            'data' => $all,
        ]);
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
                            ->where(function ($referenceQuery) use ($word) {
                                $referenceQuery
                                    ->whereRaw('title LIKE ? ', '%' . $word . '%')
                                    ->orWhereRaw('subtitle LIKE ? ', '%' . $word . '%');
                            })->orWhereHas('book', function ($query) use ($word) {
                                $query->whereRaw('title LIKE ? ', '%' . $word . '%');
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
            ->where('type', '!=', Reference::TYPE_BACKBONE)
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
                'name' => trim($name),
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

        $query = TaxonName::select('taxon_names.*')->with([
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
                            $query->whereRaw('name like ? ', '%' . $word . '%');

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
