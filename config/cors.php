<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        '*', // Permitir todos os caminhos
    ],

    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['*'],
    'max_age' => 0,
    'supports_credentials' => true, // Habilitar se estiver usando cookies/sessões
];