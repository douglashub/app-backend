<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LoggingService
{
    public function logInfo(string $message, array $context = []): void
    {
        Log::channel('application')->info($message, $context);
    }

    public function logError(string $message, array $context = []): void
    {
        Log::channel('application')->error($message, $context);
    }

    public function logWarning(string $message, array $context = []): void
    {
        Log::channel('application')->warning($message, $context);
    }

    public function logDebug(string $message, array $context = []): void
    {
        Log::channel('application')->debug($message, $context);
    }
}