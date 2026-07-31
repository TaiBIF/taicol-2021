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

    public function import(Request $request, $referenceId) // 異名表匯入
    {

         $namespaceIds = $request->get('ids');

        // 從「匯入綁定文獻」匯入的
        $fromBindReference = !$request->get('from_reference_page');
        if ($fromBindReference){

            // 非 bind_only 才檢查是否已有 usage，有的話直接擋下（不存綁定）
            if (!$request->boolean('bind_only')) {
                $hasUsageExists = ReferenceUsage::where('reference_id', $referenceId)
                    ->whereNull('deleted_at')
                    ->exists();

                // 這邊會造成原本文獻頁的匯入異名表無法加入
                if ($hasUsageExists){
                    return response([
                        'data' =>  $hasUsageExists
                    ]);
                }
            }

            // 通過檢查（或 bind_only）才儲存綁定
            $namespace = MyNamespace::find($namespaceIds[0]);
            $namespace->reference_id = $referenceId;
            $namespace->save();

            // 僅儲存綁定，不執行學名使用匯入
            if ($request->boolean('bind_only')) {
                return response()->json(['bind_only' => true]);
            }
        }

        $overwrite = $request->get('overwrite', false);
        $note = $request->get('note');

        $importUsages = MyNamespaceUsage::whereIn('namespace_id', $namespaceIds)
            ->orderBy('namespace_id')
            ->orderBy('group')
            ->orderBy('order')
            ->get();

        $reference = Reference::with('usages')->find($referenceId);

        // --- 1. 預載入：減少資料庫查詢次數 ---
        $existingUsages = ReferenceUsage::where('reference_id', $referenceId)
            ->whereNull('deleted_at')
            ->get();

        // 快速比對 Key
        $lookup = $existingUsages->mapWithKeys(function ($item) {
            $key = "{$item->taxon_name_id}|{$item->status}|{$item->accepted_taxon_name_id}|{$item->is_title}";
            return [$key => true];
        });

        // 建立 Group 與 Order 對照表
        $groupMap = $existingUsages->whereNotNull('accepted_taxon_name_id')
            ->groupBy('accepted_taxon_name_id')
            ->map(function ($items) {
                return [
                    'group' => $items->first()->group,
                    'max_order' => $items->max('order')
                ];
            })->toArray();

        $globalMaxGroup = $existingUsages->max('group') ?? -1;
        $hasDuplicate = false; // 旗標：用來標記是否有任何重複被跳過

        try {
            DB::beginTransaction();

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

            $groupLast = $globalMaxGroup + 1;
            $groupUsages = $importUsages->groupBy('namespace_id');

            foreach ($groupUsages as $groupUsage) {

                foreach ($groupUsage as $usage) {

                    // 取得本次匯入分組中的有效名 ID
                    $acceptedTaxonNameId = $importUsages->where('status', 'accepted')
                                                        ->where('group', $usage->group)
                                                        ->first()?->taxon_name_id;

                    // --- 2. 判定重複：若重複則跳過並記錄旗標 ---
                    $currentKey = "{$usage->taxon_name_id}|{$usage->status}|{$acceptedTaxonNameId}|{$usage->is_title}";
                    if (isset($lookup[$currentKey])) {
                        $hasDuplicate = true;
                        continue; // 僅跳過此筆，繼續下一筆
                    }

                    // --- 3. 處理 Group 與 Order ---
                    if ($acceptedTaxonNameId && isset($groupMap[$acceptedTaxonNameId])) {
                        // 併入既有 Group
                        $targetGroup = $groupMap[$acceptedTaxonNameId]['group'];
                        $groupMap[$acceptedTaxonNameId]['max_order']++;
                        $targetOrder = $groupMap[$acceptedTaxonNameId]['max_order'];
                    } else {
                        // 建立新 Group
                        $targetGroup = $usage->group + $groupLast;
                        $targetOrder = $usage->order;

                        if ($acceptedTaxonNameId) {
                            $groupMap[$acceptedTaxonNameId] = [
                                'group' => $targetGroup,
                                'max_order' => $targetOrder
                            ];
                        }
                    }

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
                    $referenceUsage->group = $targetGroup;
                    $referenceUsage->order = $targetOrder;

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

                    // 更新 lookup 防止同一批次內出現重複
                    $lookup[$currentKey] = true;

                    $edit_log = new ImportUsageLog();
                    $edit_log->reference_usage_id = $referenceUsage->id;
                    $edit_log->reference_id = $referenceId;
                    $edit_log->taxon_name_id = $referenceUsage->taxon_name_id;
                    $edit_log->action = ImportUsageLog::ACTION_USAGE_ADD;
                    $edit_log->action_log_id = $action_log_id;
                    $edit_log->user_id = $request->user()->id;
                    $edit_log->save();
    
                }

                $groupLast = $groupLast + ($groupUsage->max('group') ?? 0) + 1;
            }


            DB::commit();


            // --- 5. 回傳結果 ---
            $response = ['usages' => $reference->usages->fresh()];
            
            if ($hasDuplicate) {
                $response['message'] = '已有相同學名使用存在，無法匯入所有學名使用。若需要更新已建立學名使用內容，請使用「編輯異名表」更新內容。';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
        }
    }
}
