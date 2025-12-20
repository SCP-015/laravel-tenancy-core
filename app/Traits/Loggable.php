<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait untuk logging yang tidak perlu di-cover oleh tests
 * 
 * @codeCoverageIgnore
 */
trait Loggable
{
    /**
     * Log info message
     */
    protected static function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Log error message
     */
    protected static function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    /**
     * Log warning message
     */
    protected static function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * Log debug message
     */
    protected static function logDebug(string $message, array $context = []): void
    {
        Log::debug($message, $context);
    }
}
