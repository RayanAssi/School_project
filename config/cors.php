<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // ✅ أضف login و save-fcm-token و logout إلى المسارات
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'save-fcm-token',
        'logout',
        '*'
    ],

    'allowed_methods' => ['*'],

    // ✅ حدد الـ Origins بدلاً من *
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];