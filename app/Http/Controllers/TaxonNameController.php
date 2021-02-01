<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxonNameRequest;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Services\TaxonNameService;
use App\Rank;
use App\ReferenceUsage;
use App\TaxonName;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxonNameController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 30;

        $keyword = trim(strtolower($request->get('keyword', '')));
        $query = TaxonName::select('taxon_names.*')->with([
            'authors', 'exAuthors', 'reference', 'usages',
            'nomenclature.ranks', 'rank', 'originalTaxonName.authors'
        ])
            ->leftJoin('ranks', 'taxon_names.rank_id', 'ranks.id')
            ->whereRaw('LOWER(`name`) like ? ', ['%' . $keyword . '%'])
            ->orWhereHas('authors', function (Builder $query) use ($keyword) {
                $query->whereRaw('LOWER(`last_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`first_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`middle_name`) like ? ', ['%' . $keyword . '%'])
                    ->orWhereRaw('LOWER(`abbreviation_name`) like ? ', ['%' . $keyword . '%']);
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

        $taxonNames = $query->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'total' => $taxonNames->total(),
            'data' => TaxonNameCollection::collection($taxonNames->items()),
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
            'authors', 'exAuthors', 'nomenclature.ranks', 'rank', 'originalTaxonName.authors'
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
            'authors', 'exAuthors', 'reference', 'usages', 'nomenclature.ranks', 'rank'
        ])->find($id);

        if (!$taxonName) {
            return response([
                'message' => 'Not Found.'
            ], 404);
        }

        // 同名
        $homonymsTaxonNameIds = TaxonName::with([
            'authors', 'exAuthors', 'reference', 'usages',
            'nomenclature.ranks', 'rank', 'originalTaxonName.authors'
        ])
            ->where('id', '!=', $taxonName->id)
            ->where('name', $taxonName->name)
            ->pluck('id');

        $homonymsCount = ReferenceUsage::select(DB::raw('t.*'))
            ->with(['reference', 'taxonName.rank'])
            ->from(DB::raw('(SELECT * FROM reference_usages ORDER BY created_at DESC) t'))
            ->whereIn('taxon_name_id', $homonymsTaxonNameIds)
            ->groupBy('t.taxon_name_id')
            ->count();

        $acceptedUsagesCount = ReferenceUsage::where('status', 'accepted')
            ->where('taxon_name_id', $id)
            ->count();

        $synonymsCount = ReferenceUsage::where('status', '!=', 'accepted')
            ->where('taxon_name_id', $id)
            ->count();

        $referenceCount = ReferenceUsage::select(DB::raw('t.*'))
            ->with(['reference', 'taxonName.rank'])
            ->from(DB::raw('(SELECT * FROM reference_usages ORDER BY created_at DESC) t'))
            ->where('taxon_name_id', $id)
            ->count();

        $subTaxonNameCount = ReferenceUsage::where('parent_taxon_name_id', $taxonName->id)
            ->orderBy('created_at', 'desc')
            ->count();

        return response()->json([
            'taxon_name' => TaxonNameCollection::collection([$taxonName])[0],
            // 同名
            'has_homonyms' => (Boolean) $homonymsCount > 0,

            // 有效學名
            'has_accepted' => (Boolean) $acceptedUsagesCount > 0,

            // 異名
            'has_synonyms' => (Boolean) $synonymsCount > 0,

            // 引用文獻
            'has_in_reference' => (Boolean) $referenceCount > 0,

            'has_sub_taxon_names' => (Boolean) $subTaxonNameCount > 0,
        ]);
    }

    public function info($id)
    {
        $taxonName = TaxonName::with([
            'authors', 'exAuthors', 'reference', 'usages', 'nomenclature.ranks', 'rank'
        ])->find($id);

        if (!$taxonName) {
            return response([
                'message' => 'Not Found.'
            ], 404);
        }

        return response()->json([
            'data' => TaxonNameCollection::collection([$taxonName])[0]
        ]);
    }

    public function homonyms($id)
    {
        $taxonName = TaxonName::find($id);

        $homonymsTaxonNameIds = TaxonName::with([
            'authors', 'exAuthors', 'reference', 'usages',
            'nomenclature.ranks', 'rank',
            'originalTaxonName.authors'
        ])
            ->where('id', '!=', $taxonName->id)
            ->where('name', $taxonName->name)
            ->pluck('id');

        $homonyms = ReferenceUsage::select(DB::raw('t.*'))
            ->with(['reference', 'taxonName.rank'])
            ->from(DB::raw('(SELECT * FROM reference_usages ORDER BY created_at DESC) t'))
            ->whereIn('taxon_name_id', $homonymsTaxonNameIds)
            ->groupBy('t.taxon_name_id')
            ->paginate();


        return response()->json([
            'total' => $homonyms->total(),
            'data' => $homonyms->items(),
            'per_page' => $homonyms->perPage(),
            'current_page' => $homonyms->currentPage(),
            'last_page' => $homonyms->lastPage(),
        ]);
    }

    public function accepted($id)
    {
        $acceptedUsages = ReferenceUsage::with([
            'reference',
            'taxonName.rank'
        ])
            ->where('status', 'accepted')
            ->where('taxon_name_id', $id)
            ->paginate();

        return response()->json([
            'total' => $acceptedUsages->total(),
            'data' => $acceptedUsages->items(),
            'per_page' => $acceptedUsages->perPage(),
            'current_page' => $acceptedUsages->currentPage(),
            'last_page' => $acceptedUsages->lastPage(),
        ]);
    }

    public function synonyms($id)
    {
        $synonyms = ReferenceUsage::with([
            'reference',
            'taxonName.rank'
        ])
            ->where('status', '!=', 'accepted')
            ->where('taxon_name_id', $id)
            ->paginate();

        return response()->json([
            'total' => $synonyms->total(),
            'data' => $synonyms->items(),
            'per_page' => $synonyms->perPage(),
            'current_page' => $synonyms->currentPage(),
            'last_page' => $synonyms->lastPage(),
        ]);
    }

    public function references($id)
    {
        $reference = ReferenceUsage::select(DB::raw('t.*'))
            ->with(['reference.authors', 'taxonName.rank'])
            ->from(DB::raw('(SELECT * FROM reference_usages ORDER BY created_at DESC) t'))
            ->where('taxon_name_id', $id)
            ->groupBy('t.reference_id')
            ->paginate();

        return response()->json([
            'total' => $reference->total(),
            'data' => $reference->items(),
            'per_page' => $reference->perPage(),
            'current_page' => $reference->currentPage(),
            'last_page' => $reference->lastPage(),
        ]);
    }

    public function subTaxonNames($id)
    {
        $subTaxonNames = ReferenceUsage::with(['reference', 'taxonName.rank'])
            ->where('parent_taxon_name_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return response()->json([
            'total' => $subTaxonNames->total(),
            'data' => $subTaxonNames->items(),
            'per_page' => $subTaxonNames->perPage(),
            'current_page' => $subTaxonNames->currentPage(),
            'last_page' => $subTaxonNames->lastPage(),
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

        return response(TaxonNameCollection::collection([$newTaxonName])[0]);
    }
}
