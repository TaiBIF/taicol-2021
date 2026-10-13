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
];
