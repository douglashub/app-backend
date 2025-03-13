<?php

use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'application', 'console'],
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        'application' => [
            'driver' => 'daily',
            'path' => storage_path('logs/application.log'),
            'level' => 'debug',
            'days' => 14,
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'allowInlineLineBreaks' => true,
                'includeStacktraces' => true,
                'ignoreEmptyContextAndExtra' => true,
            ]
        ],
        'console' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'allowInlineLineBreaks' => true,
                'includeStacktraces' => true,
            ]
        ],
    ],
];