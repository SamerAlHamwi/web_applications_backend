<?php
// config/cors.php
// IMPORTANT: Configure CORS for Flutter Web

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        'http://localhost:54607', // Flutter web default port
        'http://localhost:64090',
        'http://localhost:59763',
        'http://localhost:*', // Flutter web default port
        'https://web.autotap.site', // Production domain
        // Add your Flutter web development URL
        // Add your production domain when deploying
    ],

    'allowed_origins_patterns' => [
        '^http://localhost(:\d+)?$', // Allow localhost with or without port for development
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // FALSE for JWT (no cookies)
];
