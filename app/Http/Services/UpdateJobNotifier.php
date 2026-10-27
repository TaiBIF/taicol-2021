<?php

namespace App\Http\Services;

use App\Mail\Email;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 更新服務 job blocked／failed 的通知信：寄給所有啟用中的管理員
 * 信裡寫清楚卡在哪一步、要做什麼，管理員不用先去看 log
 */
class UpdateJobNotifier
{
    /** job 的名稱與說明（通知信、名錄更新頁共用） */
    const JOB_LABELS = [
        'base' => '每日文獻／學名更新',
        'taxon' => '名錄更新',
        'release' => '版本名錄',
        'usage_check' => '學名使用檢查',
        'ref' => '文獻更新',
        'name' => '學名更新',
        'taxon_tool' => '名錄更新（管理工具）',
    ];

    const JOB_DESCRIPTIONS = [
        'base' => '依序：文獻更新 → 學名更新。名錄更新已包含這兩步，這裡給手動補跑用。',
        'taxon' => '依序：文獻更新 → 學名更新 → 學名使用檢查 → 名錄更新。每天凌晨 2 點由排程執行，也可手動執行。',
        'release' => '依序：學名使用檢查 → 全表檢核 → 產生下載檔（每兩個月一次）。',
        'usage_check' => '只跑學名使用檢查（學名使用檢查頁的「開始檢查」）。',
        'ref' => '只跑文獻更新，開發人員補跑用。',
        'name' => '只跑學名更新，開發人員補跑用。',
        'taxon_tool' => '只跑名錄更新（可指定分類群、只預檢等），開發人員補跑用。',
    ];

    /** 步驟（服務的 current_step）的名稱與說明 */
    const STEP_LABELS = [
        'usage_check' => '學名使用檢查',
        'ref' => '文獻更新',
        'name' => '學名更新',
        'taxon' => '名錄更新',
        'audit' => '全表檢核',
        'download' => '產生下載檔',
        'schedule' => '排程等候',
    ];

    const STEP_DESCRIPTIONS = [
        'usage_check' => '檢查文獻中學名使用（usage）的設定問題；有尚未勾選確認的問題就停下。',
        'ref' => '把有異動的文獻整理成網站使用的文獻資料（api_citations）。',
        'name' => '把有異動的學名整理成網站使用的學名資料（api_names）。',
        'taxon' => '依最新文獻重新決定分類群、接受名與同物異名，更新名錄資料與網站搜尋（Solr）。',
        'audit' => '對所有分類群跑資料檢核（api_taxon_issue）；有未處理的 high issue 時擋下版本名錄。',
        'download' => '產生學名、物種、新舊學名編碼對照三個下載檔。',
        'schedule' => '排程觸發時已有其他任務在執行；等到上限（2 小時）仍未結束，當天的排程略過。',
    ];

    /** 給前端的名稱與說明（陣列：前端會把物件的 key 轉成 camelCase，用陣列保留原本的 key） */
    public static function labels(): array
    {
        $rows = fn ($labels, $descs) => array_map(
            fn ($k) => ['key' => $k, 'label' => $labels[$k], 'description' => $descs[$k] ?? ''],
            array_keys($labels)
        );
        return [
            'jobs' => $rows(self::JOB_LABELS, self::JOB_DESCRIPTIONS),
            'steps' => $rows(self::STEP_LABELS, self::STEP_DESCRIPTIONS),
        ];
    }

    const STATUS_LABELS = [
        'blocked' => '已中止，需要處理',
        'failed' => '執行失敗',
    ];

    /** 啟用中的管理員 email；一個都沒有時退回 TAICOL_EMAIL */
    public static function recipients(): array
    {
        $emails = User::where('role_id', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ENABLE)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();

        if (!$emails) {
            Log::warning('[Update Service] 沒有啟用中的管理員 email，改寄 TAICOL_EMAIL');
            $emails = [env('TAICOL_EMAIL', 'catalogueoflife.taiwan@gmail.com')];
        }
        return $emails;
    }

    public static function subject(array $job): string
    {
        $env = ($job['env'] ?? '') === 'prod' ? '正式站' : '測試站';
        $status = self::STATUS_LABELS[$job['status']] ?? $job['status'];
        $label = self::JOB_LABELS[$job['job']] ?? $job['job'];
        return "[TaiCOL 名錄更新][{$env}] {$label} {$status}（job #{$job['id']}）";
    }

    /** 寄出並回傳收件人；失敗丟例外（由呼叫端記錄並回 502 讓服務重試） */
    public static function send(array $job): array
    {
        $to = self::recipients();
        Mail::to($to)->send(new Email(self::body($job), self::subject($job), 'TaiCOL管理員'));
        return $to;
    }

    /** 信件內容（HTML；Email 的 view 用 {!! !!} 輸出，所以這裡要自己 escape） */
    public static function body(array $job): string
    {
        $e = fn ($v) => e((string) ($v ?? ''));
        $base = rtrim(config('app.url'), '/');
        $pageUrl = "{$base}/zh-tw/admin/update-service";
        $usageUrl = "{$base}/zh-tw/admin/usage-check";

        $rows = [
            '環境' => ($job['env'] ?? '') === 'prod' ? '正式站' : '測試站',
            'job' => '#' . $job['id'] . ' ' . (self::JOB_LABELS[$job['job']] ?? $job['job']),
            '狀態' => self::STATUS_LABELS[$job['status']] ?? $job['status'],
            '停在哪一步' => self::STEP_LABELS[$job['current_step'] ?? ''] ?? ($job['current_step'] ?? ''),
            '訊息' => $job['message'] ?? '',
            '觸發方式' => ($job['trigger_by'] ?? '') === 'cron' ? '排程' : '手動',
            '開始／結束' => ($job['started_at'] ?? '') . ' ～ ' . ($job['finished_at'] ?? ''),
        ];
        $table = '<table cellpadding="4" style="border-collapse:collapse">';
        foreach ($rows as $k => $v) {
            $table .= '<tr><td style="color:#666;white-space:nowrap">' . $e($k) . '</td><td>' . $e($v) . '</td></tr>';
        }
        $table .= '</table>';

        $html = '更新服務的 job 沒有完成：<br><br>' . $table
            . '<br><b>要處理的事</b><br>' . self::advice($job, $pageUrl, $usageUrl);

        if (!empty($job['log_tail'])) {
            $html .= '<br><br><b>log 最後幾行</b>'
                . '<pre style="background:#f5f5f5;padding:8px;font-size:12px;white-space:pre-wrap">'
                . $e($job['log_tail']) . '</pre>';
        }
        $html .= '<br>執行紀錄與完整 log：<a href="' . $e($pageUrl) . '">' . $e($pageUrl) . '</a>';
        return $html;
    }

    /** 依停下的步驟說明下一步要做什麼 */
    public static function advice(array $job, string $pageUrl, string $usageUrl): string
    {
        $step = $job['current_step'] ?? '';
        $link = fn ($url, $text) => '<a href="' . e($url) . '">' . e($text) . '</a>';

        if (($job['message'] ?? '') === '服務重啟，執行中斷') {
            return '更新服務在執行途中重啟，這次沒有跑完。確認服務正常後，到' . $link($pageUrl, '名錄更新頁') . '重新執行。';
        }

        if ($job['status'] === 'blocked') {
            if ($step === 'usage_check') {
                return 'usage check 有尚未確認的問題，這次名錄沒有更新（文獻、學名已更新）。請到'
                    . $link($usageUrl, '學名使用檢查') . '處理並勾選 is_checked，再重新執行。';
            }
            if ($step === 'audit') {
                return '版本名錄把關未通過：有未處理的 high issue，這次沒有產出下載檔。'
                    . '請到' . $link($pageUrl, '名錄更新頁') . '下載 taxon_issues.csv（blocks_release=1 是擋下的那些）處理；'
                    . '確認可以略過時，勾選 force 重新產生。';
            }
            if ($step === 'taxon') {
                return '更新邏輯預檢發現需要確認的問題，這次沒有更新 DB 與 Solr。'
                    . '請到' . $link($pageUrl, '名錄更新頁') . '按「下載 issue CSV」（issues.csv、issue_rows.csv）確認，處理後重新執行。';
            }
            return '這次沒有完成，請到' . $link($pageUrl, '名錄更新頁') . '查看。';
        }

        // failed
        if ($step === 'schedule') {
            return '排程觸發時一直有其他任務在執行，今天的排程略過，資料沒有更新（訊息裡寫了是哪個任務）。'
                . '確認該任務結束後，到' . $link($pageUrl, '名錄更新頁') . '按「執行名錄更新」；'
                . '或等明天的排程，會一併補上。';
        }
        $hint = '程式執行失敗，請看下方 log 找出原因，修正後重新執行。';
        if (in_array($step, ['ref', 'name'], true)) {
            $hint .= '文獻／學名更新有失敗時水位不會推進，下次執行會重撈同一段，不會漏資料。';
        }
        if (str_contains((string) ($job['message'] ?? ''), '訊號 9')) {
            $hint .= '訊號 9 通常是記憶體不足，可先確認更新服務機器的記憶體用量。';
        }
        return $hint;
    }
}
