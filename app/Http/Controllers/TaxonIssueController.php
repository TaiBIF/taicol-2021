<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 名錄更新頁「版本名錄」分頁：版本名錄檢核結果（api_taxon_issue）的管理
 * - 依 issue_code 統計未解決、未忽略的筆數
 * - 下載某個 issue_code 的錯誤資料（CSV，串接 api_taxon、api_names）
 * - 依貼入的 api_taxon_issue id 標記解決（is_solved=1）或忽略（ignore=1），只更新該列的 issue_code
 */
class TaxonIssueController extends Controller
{
    const SEVERITY_ORDER = ['high' => 0, 'medium' => 1, 'low' => 2];

    /** 未解決、未忽略的 issue */
    private function openIssues()
    {
        return DB::table('api_taxon_issue as ti')
            ->where('ti.is_solved', 0)
            ->where('ti.ignore', 0);
    }

    public function index()
    {
        $rows = $this->openIssues()
            ->select('ti.issue_code', 'ti.severity', DB::raw('COUNT(*) as count'))
            ->groupBy('ti.issue_code', 'ti.severity')
            ->get()
            ->map(function ($r) {
                $r->count = (int) $r->count;
                return $r;
            })
            ->sortBy(fn ($r) => [self::SEVERITY_ORDER[$r->severity] ?? 9, -$r->count, $r->issue_code])
            ->values();

        return response()->json(['issues' => $rows]);
    }

    public function export(Request $request)
    {
        $request->validate(['issue_code' => 'required|string|max:64']);
        $issueCode = $request->get('issue_code');

        $query = $this->openIssues()
            ->leftJoin('api_taxon as t', 't.taxon_id', '=', 'ti.taxon_id')
            ->leftJoin('api_names as n', 'n.taxon_name_id', '=', 't.accepted_taxon_name_id')
            ->where('ti.issue_code', $issueCode)
            ->select(
                'ti.id', 'ti.issue_code', 'ti.severity', 'ti.issue_detail', 'ti.taxon_id',
                't.rank_id', 't.accepted_taxon_name_id', 'n.formatted_name', 'n.name_author',
                't.is_in_taiwan', 't.fixed_reference_id'
            )
            ->orderBy('ti.id');

        $columns = ['id', 'issue_code', 'severity', 'issue_detail', 'taxon_id', 'rank_id',
            'accepted_taxon_name_id', 'formatted_name', 'name_author', 'is_in_taiwan', 'fixed_reference_id'];
        $filename = 'taxon_issue_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $issueCode) . '_' . date('Ymd') . '.csv';

        return response()->streamDownload(function () use ($query, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM：Excel 開啟中文不亂碼
            fputcsv($out, $columns);
            foreach ($query->cursor() as $row) {
                fputcsv($out, array_map(fn ($c) => $row->$c, $columns));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * 解決名單、忽略名單：api_taxon_issue 的 id，以「,」分隔（也接受空白、換行）
     * 只更新屬於這個 issue_code 的 id，其餘回傳在 skipped
     */
    public function update(Request $request)
    {
        $request->validate([
            'issue_code' => 'required|string|max:64',
            'solve_ids' => 'nullable|string',
            'ignore_ids' => 'nullable|string',
        ]);
        $issueCode = $request->get('issue_code');

        $invalid = [];
        $solveIds = $this->parseIds($request->get('solve_ids'), $invalid);
        $ignoreIds = $this->parseIds($request->get('ignore_ids'), $invalid);

        if ($invalid) {
            return response()->json([
                'message' => '格式錯誤，id 必須是數字：' . implode('、', array_slice($invalid, 0, 20)),
                'errors' => [],
            ], 422);
        }
        if (!$solveIds && !$ignoreIds) {
            return response()->json(['message' => '請輸入 id', 'errors' => []], 422);
        }
        $both = array_values(array_intersect($solveIds, $ignoreIds));
        if ($both) {
            return response()->json([
                'message' => '同一個 id 不能同時在解決名單與忽略名單：' . implode('、', array_slice($both, 0, 20)),
                'errors' => [],
            ], 422);
        }

        // 只更新屬於這個 issue_code 的 id
        $allIds = array_merge($solveIds, $ignoreIds);
        $validIds = DB::table('api_taxon_issue')
            ->where('issue_code', $issueCode)
            ->whereIn('id', $allIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $skipped = array_values(array_diff($allIds, $validIds));

        $solved = 0;
        $ignored = 0;
        DB::transaction(function () use ($solveIds, $ignoreIds, $validIds, &$solved, &$ignored) {
            $s = array_values(array_intersect($solveIds, $validIds));
            $i = array_values(array_intersect($ignoreIds, $validIds));
            if ($s) {
                $solved = DB::table('api_taxon_issue')->whereIn('id', $s)
                    ->update(['is_solved' => 1, 'updated_at' => now()]);
            }
            if ($i) {
                $ignored = DB::table('api_taxon_issue')->whereIn('id', $i)
                    ->update(['ignore' => 1, 'updated_at' => now()]);
            }
        });

        return response()->json([
            'solved' => $solved,
            'ignored' => $ignored,
            'skipped' => $skipped,
        ]);
    }

    /** 以「,」、空白、換行分隔的 id 字串轉成不重複的整數陣列；非數字收進 $invalid */
    private function parseIds(?string $text, array &$invalid): array
    {
        $ids = [];
        foreach (preg_split('/[\s,，、]+/u', (string) $text, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (ctype_digit($token)) {
                $ids[] = (int) $token;
            } else {
                $invalid[] = $token;
            }
        }
        return array_values(array_unique($ids));
    }
}
