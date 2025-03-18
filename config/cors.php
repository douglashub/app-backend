<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_origins' => [
        'http://web.micaelsan.com', 
        'https://web.micaelsan.com',
        'http://localhost:5173',
        'http://app-frontend-production-22ea.up.railway.app',
        'https://app-frontend-production-22ea.up.railway.app'
    ],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];