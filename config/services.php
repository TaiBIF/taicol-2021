<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'daily_limit' => env('GEMINI_API_DAILY_LIMIT', 1000),
    ],

    // TaiCOL API（root 依環境設定，正式 / 測試站各自指向不同 API）
    'taicol_api' => [
        'root' => env('TAICOL_API_ROOT', 'https://api.taicol.tw'),
        // update/name、update/reference、generate_checklist 用的 Bearer token，需與 API 端 INTERNAL_API_TOKEN 相同
        'token' => env('TAICOL_API_TOKEN'),
    ],

    // TaiCOL 更新服務（/update/name、/update/reference 改打這裡）
    // root 沒設定時沿用上面的 TaiCOL API（切換前的行為），要退回舊版只要把 UPDATE_SERVICE_ROOT 拿掉
    'update_service' => [
        'root' => env('UPDATE_SERVICE_ROOT'), 
        'key' => env('UPDATE_SERVICE_API_KEY'),     // 與更新服務 .env 的 UPDATE_SERVICE_API_KEY 相同
        // 更新服務 job blocked／failed 時呼叫 /api/update-service/notify 用的 token，與更新服務 .env 的 UPDATE_NOTIFY_TOKEN 相同
        'notify_token' => env('UPDATE_NOTIFY_TOKEN'),
    ],
];
