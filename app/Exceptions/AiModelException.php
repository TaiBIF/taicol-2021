<?php

namespace App\Exceptions;

/**
 * AI 模型 / AI 服務端錯誤，訊息可直接顯示給使用者
 * messageKey / messageParams 供前端依語言顯示（aiImport.server.*），messageEn 用於雙語通知信
 */
class AiModelException extends \Exception
{
    /** 原始錯誤內容（給管理員看） */
    public string $detail = '';

    /** 前端 i18n key（不含 aiImport.server. 前綴） */
    public string $messageKey = 'unknown';

    /** 前端 i18n 參數 */
    public array $messageParams = [];

    /** 英文訊息（通知信用） */
    public string $messageEn = '';

    private const MESSAGES = [
        'quota'       => ['AI 模型使用額度已達上限（免費版限制），請稍後或明天再試。', 'The AI usage quota has been reached (free tier limit). Please try again later or tomorrow.'],
        'busy'        => ['AI 模型目前忙碌中，請稍後再試。', 'The AI model is currently busy. Please try again later.'],
        'timeout'     => ['AI 模型回應逾時，請稍後再試。', 'The AI model timed out. Please try again later.'],
        'maxTokens'   => ['AI 回應內容超過長度上限，文件可能過長，請嘗試拆分檔案後再上傳。', 'The AI response exceeded the length limit. The document may be too long; please split the file and upload again.'],
        'safety'      => ['AI 模型拒絕處理此文件內容（被安全機制阻擋）。', 'The AI model declined to process this document (blocked by safety filters).'],
        'invalidJson' => ['AI 回傳的內容無法解析，請稍後再試。', 'The AI response could not be parsed. Please try again later.'],
        'unknown'     => ['AI 模型發生錯誤（{code}）：{error}', 'AI model error ({code}): {error}'],
        'connection'  => ['AI 服務暫時無法連線或回應逾時，請稍後再試。', 'The AI service is unavailable or timed out. Please try again later.'],
    ];

    public function __construct(string $message, int $code = 503, string $detail = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->detail = $detail;
    }

    private static function make(string $key, array $params, string $detail, ?\Throwable $previous = null): self
    {
        [$zh, $en] = self::MESSAGES[$key];
        $replace = fn($text) => strtr($text, collect($params)->mapWithKeys(fn($v, $k) => ['{' . $k . '}' => $v])->all());

        $e = new self($replace($zh), 503, $detail, $previous);
        $e->messageKey = $key;
        $e->messageParams = $params;
        $e->messageEn = $replace($en);
        return $e;
    }

    public static function fromResponse($response): ?self
    {
        $body = $response->json() ?? [];
        if (($body['error_type'] ?? null) !== 'ai_model') {
            return null;
        }

        $code = $body['error_code'] ?? null;
        $key = match ($code) {
            429 => 'quota',
            503 => 'busy',
            504 => 'timeout',
            'MAX_TOKENS' => 'maxTokens',
            'SAFETY', 'BLOCKED' => 'safety',
            'INVALID_JSON' => 'invalidJson',
            default => 'unknown',
        };

        $params = $key === 'unknown'
            ? ['code' => (string) ($code ?: '-'), 'error' => (string) ($body['error'] ?? '')]
            : [];

        $detail = sprintf(
            'error_code=%s, error_status=%s, error=%s',
            $code ?? '-',
            $body['error_status'] ?? '-',
            $body['error'] ?? '-'
        );

        return self::make($key, $params, $detail);
    }

    public static function connection(?\Throwable $previous = null): self
    {
        return self::make(
            'connection',
            [],
            'ConnectionException: ' . ($previous ? $previous->getMessage() : '-'),
            $previous
        );
    }
}
