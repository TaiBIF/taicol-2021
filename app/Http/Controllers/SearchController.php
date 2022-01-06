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

        $keywords->each(function ($keyword) use ($referenceQuery) {
            $type = $keyword['type'];
            $word = trim(strtoLower($keyword['name']));

            if ($type === 'text' || $type === 'reference') {
                $referenceQuery->where(function ($referenceQuery) use ($word) {
                    $referenceQuery
                        ->whereRaw('title LIKE ? ', '%' . $word . '%')
                        ->orWhereRaw('subtitle LIKE ? ', '%' . $word . '%');
                });
            }

            if ($type === 'person') {
                $referenceQuery->whereHas('authors', function ($query) use ($word) {
                    $query->whereRaw('CONCAT(last_name, \', \', first_name, \' \', middle_name) like ?', '%' . $word . '%');
                });
            }
        });

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
            'usages' => function($query) {
                $query->select(['reference_usages.*','references.publish_year', 'references.id'])
                    ->where('status', 'accepted')
                    ->leftJoin('references', 'references.id', '=', 'reference_usages.reference_id')
                    ->where('reference_usages.properties->common_names', 'like', '%zh-tw%')
                    ->orderBy('references.publish_year');
            },
        ])
            ->leftJoin('ranks', 'taxon_names.rank_id', 'ranks.id');

        $keywords->each(function ($keyword) use ($query) {
            $type = $keyword['type'];
            $word = $keyword['name'];

            if ($type === 'text' || $type === 'taxon-name') {
                $query->whereRaw('name like ? ', '%' . $word . '%');
            }

            if ($type === 'person') {
                $query->whereHas('persons', function ($query) use ($word) {
                    $query->whereRaw('CONCAT(last_name, \', \', first_name, \' \', middle_name) like ?', '%' . $word . '%');
                });
            }

            if ($type === 'person_id') {
                $query->whereHas('authors', function ($query) use ($word) {
                    $query->where('persons.id', (int) $word);
                });
            }
        });

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

        $roots = [];
        if ($taxonNames->count()) {
            $roots = TaxonName::ancestors($taxonNames->pluck('id')->toArray());
        }

        return response()->json([
            'total' => $taxonNames->total(),
            'data' => TaxonNameListCollection::collection($taxonNames->map(function ($taxonName) use ($roots) {
                $taxonName->root = $roots[$taxonName->id] ?? null;
                return $taxonName;
            })),
            'per_page' => $taxonNames->perPage(),
            'current_page' => $taxonNames->currentPage(),
            'last_page' => $taxonNames->lastPage(),
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
}
