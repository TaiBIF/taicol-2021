<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyNamespaceCollection;
use App\ImportUsageLog;
use App\MyNamespace;
use App\MyNamespaceUsage;
use App\Reference;
use App\Book;
use App\ReferenceUsage;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Services\TaxonNameLogService;
use App\Http\Services\ReferenceLogService;
use App\Http\Services\LogService;
use App\Http\Services\LogType;
use App\Http\Services\LogAction;

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
        $namespace->type = $request->get('type');

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

            $latestUsage = ReferenceUsage::select('group')->where('reference_id', $referenceId)
                ->orderBy('group', 'desc')
                ->first();


            $log = new ImportUsageLog();
            $log->reference_id = $reference->id;

            // 先判斷這個referernce_id是否已經有任何的usages
            if (!ImportUsageLog::where('reference_id', $reference->id)->exists()){
                // 沒有的話是第一次匯入
                $nowAction = ImportUsageLog::ACTION_FIRST_IMPORT;
            } else if ($overwrite){
                $nowAction = ImportUsageLog::ACTION_OVERWRITE;
            } else {
                $nowAction = ImportUsageLog::ACTION_APPEND;
            }


            $log->action = $nowAction;
            $log->user_id = Auth::user()->id;
            $log->note = $note;
            $log->save();
            $action_log_id = $log->id;

            if ($overwrite) {
                foreach ($reference->usages()->get() as $usage) {
                    $edit_log = new ImportUsageLog();
                    $edit_log->reference_usage_id = $usage->id;
                    $edit_log->reference_id = $referenceId;
                    $edit_log->taxon_name_id = $usage->taxon_name_id;
                    $edit_log->action = ImportUsageLog::ACTION_USAGE_DELETE;
                    $edit_log->action_log_id = $action_log_id;
                    $edit_log->user_id = $request->user()->id;
                    $edit_log->save();
                }
                $reference->usages()->delete();
            }

            $groupLast = $latestUsage ? $latestUsage->group + 1 : 0;
            $groupUsages = $importUsages->groupBy('namespace_id');
            foreach ($groupUsages as $groupUsage) {


                foreach ($groupUsage as $usage) {

                    $acceptedTaxonName = $importUsages->where('status', '=', 'accepted')->where('group', $usage->group)->first();
                    $referenceUsage = new ReferenceUsage();
                    $referenceUsage->parent_taxon_name_id = $usage->parent_taxon_name_id;
                    $referenceUsage->accepted_taxon_name_id = $acceptedTaxonName ? $acceptedTaxonName->taxon_name_id : null;
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


                    foreach($usage->per_usages as $per_usage){


                        if (Reference::where('id',$per_usage['reference_id'])->where('is_publish',false)->count() >0){

                            $publishingReference = Reference::find($per_usage['reference_id']);
                            $publishingReference->is_publish = true;
                            $publishingReference->save();

                            // 如果文獻一起被發佈了 要加上update log
                            $referenceLogService = new ReferenceLogService();
                            $referenceLogService->initOriginData($publishingReference);
                            $referenceLogService->write(LogType::REFERENCE, $publishingReference->id, LogAction::UPDATE, ['is_publish']);
            
                        } else {
                            $publishingReference = Reference::find($referenceId);
                        }
            
                        if (isset($publishingReference->book_id)){
                            $publishingBook = Book::find($publishingReference->book_id);
                            $publishingBook->is_publish = true;
                            $publishingBook->save();
                        }

                    }

                    $nameIds = [];

                    if ($usage->parent_taxon_name_id){
                        array_push($nameIds, $usage->parent_taxon_name_id);
                    }

                    if ($acceptedTaxonName){
                        array_push($nameIds, $acceptedTaxonName->taxon_name_id);
                    }

                    array_push($nameIds, (int) $usage->taxon_name_id);

                    if ($usage->properties){
                        if (isset($usage->properties['type_name'])){
                            array_push($nameIds, $usage->properties['type_name']);
                        }
                    }

                    $publishingNames = TaxonName::whereIn('id', $nameIds)->where('is_publish',false)->get();
                    foreach ($publishingNames as $publishingName){
                        
                        // 如果學名一起被發佈了 要加上update log
                        $publishingName->is_publish = true;
                        $publishingName->save();

                        $taxonNameLogService = new TaxonNameLogService();
                        $taxonNameLogService->initOriginData($publishingName);
                        $taxonNameLogService->write(LogType::TAXON_NAME, $publishingName->id, LogAction::UPDATE, ['is_publish']);

                    }

                    $referenceUsage->is_title = $usage->is_title;
                    $referenceUsage->is_indent = (bool) $usage->is_indent;
                    $reference->usages()->save($referenceUsage);

                    $edit_log = new ImportUsageLog();
                    $edit_log->reference_usage_id = $referenceUsage->id;
                    $edit_log->reference_id = $referenceId;
                    $edit_log->taxon_name_id = $referenceUsage->taxon_name_id;
                    $edit_log->action = ImportUsageLog::ACTION_USAGE_ADD;
                    $edit_log->action_log_id = $action_log_id;
                    $edit_log->user_id = $request->user()->id;
                    $edit_log->save();
    
                }

                $groupLast = $usage->group + $groupLast;
            }


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
