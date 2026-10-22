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
    const JOB_LABELS = [
        'base' => '每日文獻／學名更新（base）',
        'taxon' => '名錄更新（taxon）',
        'release' => '版本名錄（release）',
        'usage_check' => 'usage check',
        'ref' => '文獻更新（ref）',
        'name' => '學名更新（name）',
        'taxon_tool' => '名錄更新管理工具（taxon_tool）',
    ];

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
            '停在哪一步' => $job['current_step'] ?? '',
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
                return 'usage check 有尚未確認的問題，這次沒有更新。請到'
                    . $link($usageUrl, '學名使用檢查') . '處理並勾選 is_checked，再重新執行。';
            }
            if ($step === 'audit') {
                return '版本名錄把關未通過：有未處理的 high issue，這次沒有產出下載檔。'
                    . '請到' . $link($pageUrl, '名錄更新頁') . '下載 high_issues.csv 處理；'
                    . '確認可以略過時，勾選 force 重新產生。';
            }
            if ($step === 'taxon') {
                return '更新邏輯預檢發現需要確認的問題，這次沒有更新 DB 與 Solr。'
                    . '請到' . $link($pageUrl, '名錄更新頁') . '下載 CSV（issues.csv、issue_rows.csv）確認，處理後重新執行。';
            }
            return '這次沒有完成，請到' . $link($pageUrl, '名錄更新頁') . '查看。';
        }

        // failed
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
