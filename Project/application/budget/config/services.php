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

    'oneplanning' => [
        'url' => env('ONEPLANNING_URL', 'https://www.pos.oou.cmu.ac.th/oneplanning/api-calls/out-budget'),
        'auth_code' => env('ONEPLANNING_AUTH_CODE'),
        'pass_key' => env('ONEPLANNING_PASS_KEY'),
        'ref_code' => env('ONEPLANNING_REF_CODE', 'DIMPMC'),
        'fiscal_year_th' => env('ONEPLANNING_FISCAL_YEAR_TH', 2569),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'auth_api' => [
        'token_refresh_url' => env('AUTH_TOKEN_REFRESH_URL', 'https://api.pharmacy.cmu.ac.th/api/tokenRefresh'),
    ],

    'staff_rx_api' => [
        'token_refresh_url' => env('STAFF_RX_TOKEN_REFRESH_URL', env('AUTH_TOKEN_REFRESH_URL', 'https://api.pharmacy.cmu.ac.th/api/tokenRefresh')),
        'api_token' => env('STAFF_RX_API_TOKEN', ''),
    ],

    'project_pbm_api' => [
        'detail_url' => env('PROJECT_PBM_DETAIL_URL', 'https://api.pharmacy.cmu.ac.th/api/detailProjectPBM'),
        'list_url' => env('PROJECT_PBM_LIST_URL', 'https://api.pharmacy.cmu.ac.th/api/listProjectPBM'),
    ],

];
