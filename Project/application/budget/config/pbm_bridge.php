<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bridge Secret
    |--------------------------------------------------------------------------
    |
    | Shared secret between usermanager and destination systems.
    | Set PBM_BRIDGE_SECRET to the same value in both applications.
    |
    */
    'secret' => env('PBM_BRIDGE_SECRET', 'pbm-bridge-local-shared-secret'),

    /*
    |--------------------------------------------------------------------------
    | Acceptable payload age (seconds)
    |--------------------------------------------------------------------------
    */
    'ttl' => (int) env('PBM_BRIDGE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Expected system slug in payload
    |--------------------------------------------------------------------------
    */
    'system_slug' => env('PBM_SYSTEM_SLUG', 'budget'),

    /*
    |--------------------------------------------------------------------------
    | Usermanager login URL
    |--------------------------------------------------------------------------
    |
    | Redirect destination when PBM session/auth data is missing or expired.
    |
    */
    'relogin_url' => env('PBM_RELOGIN_URL', '/login'),

    /*
    |--------------------------------------------------------------------------
    | Shared SSO marker cookie
    |--------------------------------------------------------------------------
    |
    | Issued by usermanager. If missing, downstream apps force re-login.
    |
    */
    'sso_cookie_name' => env('PBM_SSO_COOKIE_NAME', 'pbm_sso_active'),
    'sso_cookie_value' => env('PBM_SSO_COOKIE_VALUE', '1'),
];
