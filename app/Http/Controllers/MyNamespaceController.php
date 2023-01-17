<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyNamespaceCollection;
use App\ImportUsageLog;
use App\MyNamespace;
use App\MyNamespaceUsage;
use App\Reference;
use App\ReferenceUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MyNamespaceController extends Controller
{
    public function index(Request $request)
    {
        $namespaces = $request->user()
            ->namespaces()
            ->orderBy('id', 'desc')
            ->get();

        return response([
            'data' => MyNamespaceCollection::collection($namespaces),
        ]);
    }

    public function show(Request $request, $id)
    {
        $namespace = MyNamespace::with([
            'usages.parent',
            'usages.taxonName.nomenclature',
            'usages.taxonName.rank',
            'usages.taxonName.authors',
            'usages.taxonName.exAuthors',
            'usages.taxonName.reference.authors',
            'usages.taxonName.originalTaxonName.authors',
            'usages.taxonName.originalTaxonName.exAuthors',
        ])->find($id);

        if (!$namespace) {
            return response()->json([], 404);
        }

        if ($namespace->user_id !== $request->user()->id) {
            return response()->json([], 401);
        }

        return response()->json(MyNamespaceCollection::collection([$namespace])->first());
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
        ]);

        $namespace = MyNamespace::find($id);

        if ($namespace->user_id !== $request->user()->id) {
            return response()->json([], 401);
        }

        $namespace->title = $request->get('title');
        $namespace->save();

        return response([
            'data' => $namespace,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
        ]);

        $namespace = new MyNamespace();
        $namespace->title = $request->get('title');

        $request->user()->namespaces()->save($namespace);

        return response([
            'data' => MyNamespaceCollection::collection([$namespace])->first(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $namespace = MyNamespace::find($id);
        $namespace->delete();
    }

    public function import(Request $request, $referenceId)
    {
        $namespaceIds = $request->get('ids');
        $overwrite = $request->get('overwrite', false);
        $note = $request->get('note');

        $importUsages = MyNamespaceUsage::whereIn('namespace_id', $namespaceIds)
            ->orderBy('namespace_id')
            ->get();

        $reference = Reference::with('usages')->find($referenceId);

        try {
            DB::beginTransaction();
            if ($overwrite) {
                $reference->usages()->delete();
            }

            $latestUsage = ReferenceUsage::where('reference_id', $referenceId)
                ->orderBy('order')
                ->orderBy('group', 'desc')
                ->first();

            $groupLast = $latestUsage ? $latestUsage->group + 1 : 0;
            $groupUsages = $importUsages->groupBy('namespace_id');
            foreach ($groupUsages as $groupUsage) {

                foreach ($groupUsage as $usage) {
                    $referenceUsage = new ReferenceUsage();
                    $referenceUsage->parent_taxon_name_id = $usage->parent_taxon_name_id;
                    $referenceUsage->is_for_publish = false;
                    $referenceUsage->status = $usage->status;
                    $referenceUsage->type_specimens = $usage->type_specimens;
                    $referenceUsage->name_remark = $usage->name_remark;
                    $referenceUsage->custom_name_remark = $usage->custom_name_remark;
                    $referenceUsage->properties = $usage->properties;
                    $referenceUsage->per_usages = $usage->per_usages;
                    $referenceUsage->taxon_name_id = (int) $usage->taxon_name_id;
                    $referenceUsage->group = $usage->group + $groupLast;
                    $referenceUsage->order = $usage->order;

                    $referenceUsage->is_title = $usage->is_title;
                    $referenceUsage->is_indent = (bool) $usage->is_indent;
                    $reference->usages()->save($referenceUsage);
                }

                $groupLast = $usage->group + $groupLast;
            }

            $log = new ImportUsageLog();
            $log->reference_id = $reference->id;
            $log->action = $overwrite ? ImportUsageLog::ACTION_OVERWRITE : ImportUsageLog::ACTION_APPEND;
            $log->user_id = Auth::user()->id;
            $log->note = $note;
            $log->save();

            DB::commit();

            return response()->json([
                'usages' => $reference->usages
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
        }
    }
}
