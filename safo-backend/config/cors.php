<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('ADMIN_URL', 'http://localhost:5173'),
        env('SUPPLIER_DASHBOARD_URL', 'http://localhost:3001'),
        'https://htsoftpro-ui.github.io',
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.github\.io$#',
        '#^https://.*\.vercel\.app$#',
        '#^https://.*\.netlify\.app$#',
        '#^https://.*\.railway\.app$#',
        '#^https://.*\.infinityfreeapp\.com$#',
        '#^https://.*\.wuaze\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
