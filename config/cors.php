<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_origins' => [
        // Your frontend subdomain(s)
        'http://web.micasan.com.br',
        'https://web.micasan.com.br',

        // Local dev
        'http://localhost:5173',

        // If you still need the old Railway subdomain
        'http://app-frontend-production-22ea.up.railway.app',
        'https://app-frontend-production-22ea.up.railway.app',
    ],

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
