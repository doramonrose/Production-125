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

    'department_api' => [
        'url' => env('DEPARTMENT_API_URL'),
        'token' => env('DEPARTMENT_API_TOKEN'),
    ],

    'staff_rx_api' => [
        'token_refresh_url' => env('STAFF_RX_TOKEN_REFRESH_URL'),
        'check_staff_url' => env('STAFF_RX_CHECK_STAFF_URL'),
        'list_staff_url' => env('STAFF_RX_LIST_STAFF_URL'),
        'signature_api_base' => env('STAFF_RX_SIGNATURE_API_BASE', 'https://api.pharmacy.cmu.ac.th/api/staffSignature?staffID='),
    ],

    'notification_api' => [
        'url' => env('NOTIFICATION_API_URL', 'https://api.pharmacy.cmu.ac.th/api/notifications'),
    ],

    'project_app' => [
        'base_url' => env('PROJECT_APP_BASE_URL', 'http://172.17.1.145:10011/project'),
        'approve_memo_pdf_path' => env('PROJECT_APPROVE_MEMO_PDF_PATH', '/User/api_approve_memo_pdf.php'),
        'print_pdf_path' => env('PROJECT_PRINT_PDF_PATH', '/User/api_project_print_pdf.php'),
        'chk_session_path' => env('PROJECT_CHK_SESSION_PATH', '/User/chk_session.php'),
        'page_path' => env('PROJECT_PAGE_PATH', '/User/page.php'),
        'pending_withdraw_path' => env('PROJECT_PENDING_WITHDRAW_PATH', '/User/api_pbm_withdraw_pending.php'),
        // api_approve_memo_pdf.php / api_project_print_pdf.php รับ project_code แล้วคืน PDF โดยตรง
        'operate_id_scan_max' => (int) env('PROJECT_OPERATE_ID_SCAN_MAX', 250),
        'operate_id_scan_min' => (int) env('PROJECT_OPERATE_ID_SCAN_MIN', 1),
    ],

    'project_pbm_api' => [
        'detail_url' => env('PROJECT_PBM_DETAIL_URL', 'https://api.pharmacy.cmu.ac.th/api/detailProjectPBM'),
        'list_url' => env('PROJECT_PBM_LIST_URL', 'https://api.pharmacy.cmu.ac.th/api/listProjectPBM'),
    ],

    'one_planning' => [
        'add_budget_used_url' => env(
            'ONE_PLANNING_ADD_BUDGET_USED_URL',
            'https://www.pos.oou.cmu.ac.th/oneplanning/api-calls/add-budget-used'
        ),
        'auth_code' => env('ONE_PLANNING_AUTH_CODE', 'p1@nCmu_One_P1@n'),
        'pass_key' => env('ONE_PLANNING_PASS_KEY', 'd0487946b96e50be$38935f2269774_b12c48112c4b8c2eb42'),
        'ref_code' => env('ONE_PLANNING_REF_CODE', 'BGAPMC'),
        'budget_departments_id' => env('ONE_PLANNING_BUDGET_DEPARTMENTS_ID', '11'),
    ],

    'document_number' => [
        'api_url' => env('DOCUMENT_NUMBER_API_URL', 'https://mis.pharmacy.cmu.ac.th/document_number/api/issue.php'),
        'api_key' => env('DOCUMENT_NUMBER_API_KEY', ''),
    ],

    'leaves_mission' => [
        'base_url' => env('LEAVES_MISSION_URL', 'https://mis.pharmacy.cmu.ac.th/leaves_mission'),
    ],

];
