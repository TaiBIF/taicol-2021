<?php

namespace App\Http\Controllers;

use App\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportLogController extends Controller
{
    // 進頁補畫面：撈某 user 該 type 最近一筆
    public function latest(Request $request)
    {
        $type = $request->query('type', 'taxon_name');
        $namespaceId = $request->query('namespace_id');

        $log = ImportLog::where('user_id', $request->user()->id)
            ->where('type', $type)
            ->when($namespaceId, function ($q) use ($namespaceId) {
                $q->whereRaw("JSON_EXTRACT(context, '$.namespace_id') = ?", [(int) $namespaceId]);
            })
            ->whereNull('dismissed_at')
            ->orderByDesc('id')   // 用主鍵排序，取代 latest() 的 created_at
            ->first();

        return response()->json(['data' => $log ? $this->format($log) : null]);
    }

    // 輪詢單筆（限本人）
    public function show(Request $request, $id)
    {
        $log = ImportLog::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $this->format($log)]);
    }

    public function dismiss(Request $request, $id)
    {
        $log = ImportLog::where('user_id', $request->user()->id)->findOrFail($id);

        if ($log->error_file_path && file_exists(public_path($log->error_file_path))) {
            @unlink(public_path($log->error_file_path));
        }
        $log->update(['dismissed_at' => now(), 'error_file_path' => null]);

        return response()->json(['data' => true]);
    }

    private function format(ImportLog $log): array
    {
        return [
            'id'                => $log->id,
            'type'              => $log->type,
            'status'            => $log->status,
            'phase'             => $log->phase,
            'original_filename' => $log->original_filename,
            'total_rows'        => $log->total_rows,
            'processed_rows'    => $log->processed_rows,
            'success_count'     => $log->success_count,
            'error_message'     => $log->error_message,
            'error_file_url'    => $log->error_file_path ? url($log->error_file_path) : null,
            'started_at'        => $log->started_at,
            'completed_at'      => $log->completed_at,
            'created_at'        => $log->created_at,
            'cancel_requested_at' => $log->cancel_requested_at,
        ];
    }
    
    public function cancel(Request $request, $id)
    {
        $log = ImportLog::where('user_id', $request->user()->id)->findOrFail($id);

        if (!in_array($log->status, ['pending', 'processing'])) {
            return response()->json(['data' => false, 'message' => '工作已結束，無法取消'], 409);
        }

        $log->update(['cancel_requested_at' => now()]);
        return response()->json(['data' => true]);
    }
}