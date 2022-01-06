<?php

namespace App\Http\Controllers;

use App\FavoriteMineItem;
use App\Http\Requests\TaxonNameRequest;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Resources\TaxonNameResource;
use App\Http\Services\TaxonNameService;
use App\Reference;
use App\ReferenceUsage;
use App\TaxonName;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaxonNameController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 15;

        $strict = (bool) $request->get('strict', false);

        $keyword = trim(strtolower($request->get('keyword', '')));
        $query = TaxonName::select('taxon_names.*')->with([
            'authors', 'exAuthors', 'reference', 'reference.authors',
            'nomenclature', 'rank', 'originalTaxonName.authors'
        ])
            ->leftJoin('ranks', 'taxon_names.rank_id', 'ranks.id')
            ->whereRaw('LOWER(`name`) like ? ', ['%' . $keyword . '%'])
            ->orWhereHas('authors', function (Builder $query) use ($keyword) {
                $query->whereRaw('LOWER(`last_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`first_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`middle_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`abbreviation_name`) like ? ', ['%' . $keyword . '%']);
            });

        if ($keyword === '' && $strict) {
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

        $taxonNames = $query->orderByRaw(
            "CASE WHEN LOWER(`name`) = '{$keyword}' THEN 0 WHEN LOWER(`name`) LIKE '{$keyword}%' THEN 1 WHEN LOWER(`name`) LIKE '% {$keyword}' THEN 2 ELSE 3 END"
        )
            ->paginate($perPage);

        $roots = [];
        if ($taxonNames->count()) {
            $roots = TaxonName::ancestors($taxonNames->pluck('id')->toArray());
        }

        return response()->json([
            'total' => $taxonNames->total(),
            'data' => TaxonNameCollection::collection($taxonNames->map(function ($taxonName) use ($roots) {
                $taxonName->root = $roots[$taxonName->id] ?? null;
                return $taxonName;
            })),
            'per_page' => $taxonNames->perPage(),
            'current_page' => $taxonNames->currentPage(),
            'last_page' => $taxonNames->lastPage(),
        ]);
    }

    public function simpleIndex(Request $request)
    {
        $perPage = 30;

        $keyword = trim(strtolower($request->get('keyword', '')));
        if (!$keyword) {
            return response()->json([
                'total' => 0,
                'data' => [],
                'per_page' => $perPage,
                'current_page' => 0,
                'last_page' => 0,
            ]);
        }

        $taxonNames = TaxonName::select('taxon_names.*')->with([
            'authors', 'exAuthors', 'nomenclature', 'rank', 'originalTaxonName.authors'
        ])
            ->leftJoin('ranks', 'taxon_names.rank_id', 'ranks.id')
            ->whereRaw('LOWER(`name`) like ? ', ['%' . $keyword . '%'])
            ->orWhereHas('authors', function (Builder $query) use ($keyword) {
                $query->whereRaw('LOWER(`last_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`first_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`middle_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`abbreviation_name`) like ? ', ['%' . $keyword . '%']);
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'total' => $taxonNames->total(),
            'data' => TaxonNameCollection::collection($taxonNames->items()),
            'per_page' => $taxonNames->perPage(),
            'current_page' => $taxonNames->currentPage(),
            'last_page' => $taxonNames->lastPage(),
        ]);
    }

    public function show($id)
    {
        $taxonName = TaxonName::with([
            'authors', 'exAuthors', 'reference', 'usages', 'nomenclature', 'rank'
        ])->find($id);

        if (!$taxonName) {
            return response([
                'message' => 'Not Found.'
            ], 404);
        }

        // 同名
        $homonymsCount = (boolean) TaxonName::where('id', '!=', $taxonName->id)
            ->where('name', $taxonName->name)
            ->count();

        // 是否有「有效學名」
        $hasAcceptedUsages = (boolean) ReferenceUsage::where('status', 'not-accepted')
            ->where('taxon_name_id', $id)
            ->where('is_indent', 1)
            ->count();

        // 異名數量
        $u = ReferenceUsage::where('status', 'accepted')
            ->where('taxon_name_id', $id)
            ->where('is_indent', 0)
            ->get();

        if ($u->count() == 0) {
            $synonymsCount = 0;
        } else {
            $synonymsQuery = ReferenceUsage::query();
            $synonymsQuery->where(function ($query) use ($u) {
                foreach ($u as $uu) {
                    $query->orWhere(function ($q) use ($uu) {
                        $q->where('reference_usages.reference_id', $uu->reference_id)
                            ->where('group', $uu->group)
                            ->where('status', 'not-accepted')
                            ->where('is_indent', 1);
                    });
                }
            });
            $synonymsCount = $synonymsQuery->count();
        }

        // 俗名顯示
        $commonNames = ReferenceUsage::query()
            ->select('reference_id', 'reference_usages.properties', 'references.publish_year')
            ->where('status', 'accepted')
            ->where('references.id', '!=', 153)
            ->where('taxon_name_id', $id)
            ->leftJoin('references', 'references.id', '=', 'reference_usages.reference_id')
            ->whereJsonLength('reference_usages.properties->common_names', '>', 0)
            ->orderBy('references.publish_year', 'desc')
            ->get();

        $referenceCount = ReferenceUsage::query()
            ->from(DB::raw('(SELECT reference_usages.*, references.type FROM reference_usages left join `references` on reference_usages.reference_id = references.id) t'))
            ->where('taxon_name_id', $id)
            ->where('type', '!=', Reference::TYPE_BACKBONE)
            ->count();

        $subTaxonNameCount = ReferenceUsage::where('parent_taxon_name_id', $taxonName->id)
            ->orderBy('created_at', 'desc')
            ->count();

        return response()->json([
            'taxon_name' => new TaxonNameResource($taxonName),
            'common_name' => $commonNames->sortByDesc('publish_year')->map(function($names) {
                return collect($names->properties['common_names'])
                    ->filter(function($name) {
                        return $name['language'] == 'zh-tw';
                    });
            })
                ->flatten(1)
                ->first(),
            'tabs' => [
                // 同名
                [
                    'key' => 'homonym',
                    'display' => (boolean) $homonymsCount > 0,
                ],
                // 有效學名
                [
                    'key' => 'accepted',
                    'display' => $hasAcceptedUsages,
                ],

                // 異名
                [
                    'key' => 'synonym',
                    'display' => (boolean) $synonymsCount > 0,
                ],

                // 引用文獻
                [
                    'key' => 'in-reference',
                    'display' => (boolean) $referenceCount > 0,
                ],
                [
                    'key' => 'sub-taxon-name',
                    'display' => (boolean) $subTaxonNameCount > 0,
                ],
                [
                    'key' => 'common-name',
                    'display' => (boolean) !!$commonNames->count(),
                ],
            ]
        ]);
    }

    public function info($id)
    {
        $taxonName = TaxonName::with([
            'authors', 'exAuthors', 'reference', 'usages', 'nomenclature', 'rank'
        ])->find($id);

        if (!$taxonName || $id != (int) $id) {
            return response([
                'message' => 'Not Found.'
            ], 404);
        }

        return response()->json([
            'data' => TaxonNameCollection::collection([$taxonName])[0]
        ]);
    }

    public function parents($id)
    {
        $root = TaxonName::ancestors([$id])->first();

        $parentIds = explode('>', $root->path ?? '');

        $parents = TaxonName::whereIn('id', $parentIds)->where('id', '!=', $id)->get();

        return response()->json([
            'data' => $parents->map(function($p){
                $usage = ReferenceUsage::select('reference_usages.*', 'references.publish_year')
                    ->leftJoin('references', 'reference_usages.reference_id', '=', 'references.id')
                    ->where('reference_usages.properties->common_names', 'like', '%zh-tw%')
                    ->where('taxon_name_id', $p->id)
                    ->where('status', 'accepted')
                    ->orderBy('references.publish_year', 'desc')
                    ->orderBy('updated_at', 'desc')
                    ->first();

                $commonName = $usage ? collect($usage->properties['common_names'])->where('language', 'zh-tw')->first()['name'] : '';

                return [
                    'id' => $p->id,
                    'nomenclature' => $p->nomenclature,
                    'rank' => $p->rank,
                    'name' => $p->name,
                    'authors' => PersonCollection::collection($p->authors),
                    'ex_authors' => PersonCollection::collection($p->exAuthors),
                    'publish_year' => $p->publish_year,
                    'properties' => $p->properties,
                    'common_name_tw' => $commonName,
                ];
            })->sortBy(function ($parent) {
                return $parent['rank']->order;
            })->values(),
        ]);
    }

    public function homonyms(Request $request, $id)
    {
        $taxonName = TaxonName::find($id);

        $homonymsQuery = TaxonName::select([
            'taxon_names.*',
            'references.publish_year as reference_publish_year',
        ])->with([
            'authors',
            'exAuthors',
            'reference',
            'nomenclature', 'rank',
            'originalTaxonName.authors',
            'originalTaxonName.exauthors'
        ])
            ->leftJoin('references', 'references.id', 'taxon_names.reference_id')
            ->where('taxon_names.id', '!=', $taxonName->id)
            ->where('name', $taxonName->name);

        $direction = $request->get('direction');
        if (!$direction) {
            $homonymsQuery->orderBy('taxon_names.name');
        } else {
            if ($request->get('sortby') === 'publish_year') {
                $homonymsQuery->orderBy('references.publish_year', $direction);
            }

            if ($request->get('sortby') === 'taxon_name') {
                $homonymsQuery->orderBy('taxon_names.name', $direction);
            }
        }

        $homonyms = $homonymsQuery->paginate();

        return response()->json([
            'total' => $homonyms->total(),
            'data' => TaxonNameCollection::collection($homonyms->items()),
            'per_page' => $homonyms->perPage(),
            'current_page' => $homonyms->currentPage(),
            'last_page' => $homonyms->lastPage(),
        ]);
    }

    public function accepted(Request $request, $id)
    {
        $u = ReferenceUsage::where('status', 'not-accepted')
            ->where('taxon_name_id', $id)
            ->where('is_indent', 1)
            ->get();

        if ($u->count() === 0) {
            return response()->json([
                'total' => 0,
                'data' => [],
            ]);
        }

        $acceptedUsagesQuery = ReferenceUsage::with([
            'reference',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
        ])
            ->select([
                'reference_usages.*',
                'references.publish_year as reference_publish_year',
                'taxon_names.name as taxon_name_name',
            ])
            ->leftJoin('taxon_names', 'taxon_names.id', 'reference_usages.taxon_name_id')
            ->leftJoin('references', 'references.id', 'reference_usages.reference_id');

        $acceptedUsagesQuery->where(function ($query) use ($u) {
            foreach ($u as $uu) {
                $query->orWhere(function ($q) use ($uu) {
                    $q->where('reference_usages.reference_id', $uu->reference_id)
                        ->where('group', $uu->group)
                        ->where('status', 'accepted')
                        ->where('is_indent', 0);
                });
            }
        });

        $direction = $request->get('direction');
        if (!$direction) {
            $acceptedUsagesQuery->orderBy('taxon_name_name')
                ->orderBy('reference_publish_year');
        } else {
            if ($request->get('sortby') === 'publish_year') {
                $acceptedUsagesQuery->orderBy('reference_publish_year', $direction);
            }

            if ($request->get('sortby') === 'taxon_name') {
                $acceptedUsagesQuery->orderBy('taxon_name_name', $direction);
            }
        }

        $acceptedUsages = $acceptedUsagesQuery->paginate();

        return response()->json([
            'total' => $acceptedUsages->total(),
            'data' => $acceptedUsages->map(function ($usage) {
                $usage->taxon_name = TaxonNameCollection::collection([$usage->taxon_name])[0];
                return $usage;
            }),
            'per_page' => $acceptedUsages->perPage(),
            'current_page' => $acceptedUsages->currentPage(),
            'last_page' => $acceptedUsages->lastPage(),
        ]);
    }

    public function synonyms(Request $request, $id)
    {
        $u = ReferenceUsage::where('status', 'accepted')
            ->where('taxon_name_id', $id)
            ->where('is_indent', 0)
            ->get();

        if ($u->count() === 0) {
            return response()->json([
                'total' => 0,
                'data' => [],
            ]);
        }

        $synonymsQuery = ReferenceUsage::with([
            'reference',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
        ])
            ->select([
                'reference_usages.*',
                'references.publish_year as reference_publish_year',
                'taxon_names.name as taxon_name_name',
            ])
            ->leftJoin('taxon_names', 'taxon_names.id', 'reference_usages.taxon_name_id')
            ->leftJoin('references', 'references.id', 'reference_usages.reference_id');

        $synonymsQuery->where(function ($query) use ($u) {
            foreach ($u as $uu) {
                $query->orWhere(function ($q) use ($uu) {
                    $q->where('reference_usages.reference_id', $uu->reference_id)
                        ->where('group', $uu->group)
                        ->where('status', 'not-accepted')
                        ->where('is_indent', 1);
                });
            }
        });

        $direction = $request->get('direction');
        if (!$direction) {
            $synonymsQuery->orderBy('taxon_name_name')
                ->orderBy('reference_publish_year');
        } else {
            if ($request->get('sortby') === 'publish_year') {
                $synonymsQuery->orderBy('reference_publish_year', $direction);
            }

            if ($request->get('sortby') === 'taxon_name') {
                $synonymsQuery->orderBy('taxon_name_name', $direction);
            }
        }

        $synonyms = $synonymsQuery->paginate();

        return response()->json([
            'total' => $synonyms->total(),
            'data' => $synonyms->map(function ($usage) {
                $usage->taxon_name = TaxonNameCollection::collection([$usage->taxon_name])[0];
                return $usage;
            }),
            'per_page' => $synonyms->perPage(),
            'current_page' => $synonyms->currentPage(),
            'last_page' => $synonyms->lastPage(),
        ]);
    }

    public function references(Request $request, $id)
    {
        $referenceQuery = ReferenceUsage::select(DB::raw('t.*'))
            ->with(['reference.authors', 'taxonName.rank', 'taxonName.nomenclature'])
            ->from(DB::raw('(SELECT * FROM reference_usages ORDER BY created_at DESC) t'))
            ->leftjoin('references', 'references.id', 't.reference_id')
            ->where('taxon_name_id', $id)
            ->where('references.type', '!=', Reference::TYPE_BACKBONE)
            ->where('is_title', false);

        $direction = $request->get('direction');
        if (!$direction) {
            $referenceQuery->orderBy('references.publish_year');
        } else {
            if ($request->get('sortby') === 'publish_year') {
                $referenceQuery->orderBy('references.publish_year', $direction);
            }
        }

        $reference = $referenceQuery->paginate();

        return response()->json([
            'total' => $reference->total(),
            'data' => $reference->items(),
            'per_page' => $reference->perPage(),
            'current_page' => $reference->currentPage(),
            'last_page' => $reference->lastPage(),
        ]);
    }

    private function getSubById($ids)
    {
        $taxonNameIds = ReferenceUsage::whereIn('parent_taxon_name_id', $ids)
            ->orderBy('reference_usages.created_at', 'desc')
            ->pluck('taxon_name_id');

        $taxonNamesQuery = TaxonName::select('taxon_names.*')
            ->with([
                'authors',
                'exAuthors',
                'reference',
                'nomenclature', 'rank',
                'originalTaxonName.authors',
                'originalTaxonName.exauthors'
            ])
            ->leftJoin('references', 'references.id', 'taxon_names.reference_id')
            ->whereIn('taxon_names.id', $taxonNameIds);

        return $taxonNamesQuery->get();
    }

    public function subTaxonNames(Request $request, $id)
    {
        $currentTaxonName = TaxonName::find($id);

        $taxonNameIds = ReferenceUsage::where('parent_taxon_name_id', $id)
            ->orderBy('reference_usages.created_at', 'desc')
            ->pluck('taxon_name_id');

        $needSubQueryIds = TaxonName::select('taxon_names.*')
            ->leftJoin('references', 'references.id', 'taxon_names.reference_id')
            ->leftJoin('ranks', 'ranks.id', 'taxon_names.rank_id')
            ->whereIn('taxon_names.id', $taxonNameIds)
            ->where('ranks.sub_group', $currentTaxonName->rank->id)
            ->get()
            ->pluck('id');

        $subTaxonNameIds = ReferenceUsage::whereIn('parent_taxon_name_id', $needSubQueryIds)
            ->orderBy('reference_usages.created_at', 'desc')
            ->pluck('taxon_name_id');

        $allIds = $taxonNameIds->merge($subTaxonNameIds);

        $taxonNamesQuery = TaxonName::select('taxon_names.*')
            ->with([
                'authors',
                'exAuthors',
                'reference',
                'nomenclature', 'rank',
                'originalTaxonName.authors',
                'originalTaxonName.exauthors'
            ])
            ->leftJoin('references', 'references.id', 'taxon_names.reference_id')
            ->whereIn('taxon_names.id', $allIds);

        $direction = $request->get('direction');
        if (!$direction) {
            $taxonNamesQuery->orderBy('name');
        } else {
            if ($request->get('sortby') === 'publish_year') {
                $taxonNamesQuery->orderBy('references.publish_year', $direction);
            }

            if ($request->get('sortby') === 'taxon_name') {
                $taxonNamesQuery->orderBy('name', $direction);
            }

            if ($request->get('sortby') === 'rank') {
                $taxonNamesQuery->orderBy('rank_id', $direction);
            }
        }

        $allSubNames = $taxonNamesQuery->paginate(50);

        return response()->json([
            'total' => $allSubNames->total(),
            'data' => TaxonNameCollection::collection($allSubNames->items()),
            'per_page' => $allSubNames->perPage(),
            'current_page' => $allSubNames->currentPage(),
            'last_page' => $allSubNames->lastPage(),
        ]);
    }

    public function commonNames(Request $request, $id)
    {
        $usagesQuery = ReferenceUsage::query()
            ->select(['reference_usages.properties->common_names as common_names', 'reference_usages.taxon_name_id', 'reference_usages.id', 'references.publish_year', 'references.id as reference_id'])
            ->where('status', 'accepted')
            ->where('taxon_name_id', $id)
            ->where('references.id', '!=', 153)
            ->leftJoin('references', 'references.id', '=', 'reference_usages.reference_id')
            ->whereJsonLength('reference_usages.properties->common_names', '>', 0);

        $direction = $request->get('direction');
        if (!$direction) {
            $usagesQuery->orderBy('references.publish_year', 'desc');;
        } else {
            if ($request->get('sortby') === 'publish_year') {
                $usagesQuery->orderBy('references.publish_year', $direction);
            }
        }

        $usages = $usagesQuery->get();

        $references = Reference::with(['authors'])
            ->whereIn('id', $usages->pluck('reference_id'))
            ->get()
            ->keyBy('id');

        $names = collect([]);

        $usages->each(function ($usage) use ($names, $references)  {
            collect(json_decode($usage->common_names))
                ->each(function($n) use ($names, $usage, $references) {
                $names->push([
                    'name' => $n->name,
                    'area' => $n->area,
                    'language' => $n->language,
                    'publish_year' => $usage->publish_year,
                    'reference' => ReferenceCollection::collection([$references[$usage->reference_id]])->first()
                ]);
            });
        });

        if ($request->get('sortby') === 'language') {
            $names = $direction === 'desc' ?  $names->sortByDesc('language')->values()->all() : $names->sortBy('language')->values()->all();
        }

        return response()->json([
            'data' => $names,
        ]);
    }

    public function update(TaxonNameRequest $request)
    {
        DB::beginTransaction();

        try {
            $taxonName = TaxonName::find($request->get('id'));
            $newTaxonName = (new TaxonNameService())->setAllProperties($request->all())
                ->saveAll($taxonName ?? null);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTrace(),
            ])->setStatusCode(500);
        }

        return response(TaxonNameCollection::collection([$newTaxonName])[0]);
    }

    public function store(TaxonNameRequest $request)
    {
        DB::beginTransaction();

        try {
            $newTaxonName = (new TaxonNameService())->setAllProperties($request->all())
                ->saveAll();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTrace(),
            ])->setStatusCode(500);
        }

        $taxonName = TaxonName::with([
            'authors', 'exAuthors', 'reference', 'usages', 'nomenclature', 'rank'
        ])->find($newTaxonName->id);

        // save to my favorite list
        $item = new FavoriteMineItem();
        $item->collectable_type = FavoriteMineItem::TYPE_TAXON_NAME;
        $item->collectable_id = $taxonName->id;
        $item->user_id = Auth::user()->id;
        $item->save();

        return response(TaxonNameCollection::collection([$taxonName])[0]);
    }
}
