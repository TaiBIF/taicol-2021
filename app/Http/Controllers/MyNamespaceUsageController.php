<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyNamespaceCollection;
use App\Http\Resources\PersonCollection;
use App\MyNamespace;
use App\MyNamespaceUsage;
use App\Person;
use App\Reference;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MyNamespaceUsageController extends Controller
{

    public function show($namespaceId, $usageId)
    {
        $usage = MyNamespaceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
            'namespace'
        ])
            ->where('namespace_id', $namespaceId)
            ->where('id', $usageId)
            ->first();

        return response([
            'id' => $usage->id,
            'namespace' => $usage->namespace,
            'taxon_name' => $usage->taxonName,
            'parent_taxon_name' => $usage->parent,
            'status' => $usage->status,
            'properties' => $usage->properties,
            'per_usages' => collect($usage->per_usages)->map(function ($r) {
                $r['target'] = isset($r['reference_id']) ? Reference::with('authors')->find($r['reference_id']) : null;
                return $r;
            }),
            'type_specimens' => collect($usage->type_specimens)->map(function ($t) {
                    $t['collectors'] = PersonCollection::collection(Person::whereIn('id', $t['collectors'])->get());
                    return $t;
                }) ?? [],
            'name_remark' => $usage->name_remark,
            'custom_name_remark' => $usage->custom_name_remark,
        ]);
    }

    public function update(Request $request, $namespaceId, $usageId)
    {
        $request->validate([
            'type_specimens.*.use' => 'required',
            'type_specimens.*.kind' => 'required|integer',
            'type_specimens.*.country' => 'required_if:type_specimens.*.kind,1',
            'type_specimens.*.specimens.*.herbarium' => 'required|min:1',
            'type_specimens.*.collection_year' => 'max:4',
            'type_specimens.*.collection_month' => 'max:2',
            'type_specimens.*.collection_day' => 'max:2',
            'type_specimens.*.collectors' => 'required_if:type_specimens.*.kind,1|array|exists:persons,id',
            'type_specimens.*.isotypes.*.herbarium' => 'required',
            'per_usages.*.reference_id' => 'required',
            'per_usages.*.show_page' => 'integer|nullable',
        ], [
            'min' => '必填',
            'not_in' => '必填',
            'required' => '必填',
            'required_if' => '必填',
            'required_without' => '必填',
            'integer' => '須為數字',
        ]);

        $namespace = MyNamespace::find($namespaceId);

        if (!$namespace) {
            return response()->setStatusCode(404);
        }

        DB::beginTransaction();

        $namespace->touch();

        $usage = $request->all();

        try {
            $originUsage = MyNamespaceUsage::find($usageId);
            $originUsage->parent_taxon_name_id = $usage['parent_taxon_name_id'] ?? null;
            $originUsage->is_for_publish = false;
            $originUsage->status = $usage['status'] ?? false;
            $originUsage->type_specimens = $usage['type_specimens'] ?? [];
            $originUsage->name_remark = $usage['name_remark'] ?? '';
            $originUsage->custom_name_remark = $usage['custom_name_remark'] ?? '';
            $originUsage->properties = $usage['properties'] ?? [];
            $originUsage->per_usages = $usage['per_usages'] ?? [];
            $originUsage->save();

            DB::commit();
            return response()->json([
                'data' => $usage
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[usage] - udpate::fail $e->getMessage()");
            return response()->json([
                'message' => 'error'
            ], 500);
        }
    }

    public function store(Request $request, $namespaceId)
    {
        $usages = $request->all();

        $request->validate([
            '*.type_specimens.*.use' => 'required',
            '*.type_specimens.*.kind' => 'required|integer',
            '*.type_specimens.*.country' => 'required_if:type_specimens.*.kind,1',
            '*.type_specimens.*.specimens.*.herbarium' => 'required|min:1',
            '*.type_specimens.*.collection_year' => 'max:4',
            '*.type_specimens.*.collection_month' => 'max:2',
            '*.type_specimens.*.collection_day' => 'max:2',
            '*.type_specimens.*.collectors' => 'required_if:type_specimens.*.kind,1|array|exists:persons,id',
            '*.type_specimens.*.isotypes.*.herbarium' => 'required',
        ], [
            'min' => '必填',
            'not_in' => '必填',
            'required' => '必填',
            'required_if' => '必填',
            'required_without' => '必填',
        ]);

        DB::beginTransaction();

        $namespace = MyNamespace::find($namespaceId);

        if (!$namespace) {
            return response()->setStatusCode(404);
        }

        $namespace->touch();

        $group = 0;
        foreach ($usages as $index => $usage) {
            $isTitle = (bool) ($usage['is_title'] ?? false);

            if (!$usage['is_indent']) {
                $group += 1;
            }

            if (isset($usage['id']) && $usage['id']) {
                $currentUsage = MyNamespaceUsage::find($usage['id']);
                if ($currentUsage && isset($usage['is_deleted'])) {
                    $currentUsage->delete();
                    continue;
                }
            } else {
                $currentUsage = new MyNamespaceUsage();
                $currentUsage->namespace_id = $namespaceId;
            }

            $currentUsage->parent_taxon_name_id = $usage['parent_taxon_name_id'] ?? null;
            $currentUsage->is_for_publish = false;
            $currentUsage->status = $usage['status'] ?? false;
            $currentUsage->type_specimens = $usage['type_specimens'] ?? [];
            $currentUsage->name_remark = $usage['name_remark'] ?? '';
            $currentUsage->custom_name_remark = $usage['custom_name_remark'] ?? '';
            $currentUsage->properties = $usage['properties'] ?? [];
            $currentUsage->per_usages = $usage['per_usages'] ?? [];
            $currentUsage->taxon_name_id = (int) $usage['taxon_name_id'];
            $currentUsage->group = $group;
            $currentUsage->order = $index;

            $currentUsage->is_title = $isTitle;
            $currentUsage->is_indent = (bool) $usage['is_indent'];
            $currentUsage->save();
        }

        DB::commit();
        return response([]);
    }
}
