<?php

namespace App\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @codeCoverageIgnore
 */
class InertiaExceptionHandler
{
    /**
     * Register exception handling for Inertia responses
     *
     * @param Exceptions $exceptions
     * @return void
     */
    public static function handle(Exceptions $exceptions): void
    {
        // Report exception ke log channel (Discord, daily, dll)
        $exceptions->report(function (HttpException $e) {
            // Log untuk error 500 dan error kritis lainnya
            if ($e->getStatusCode() >= 500) {
                Log::error('HTTP Exception: ' . $e->getMessage(), [
                    'status_code' => $e->getStatusCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            return self::renderInertiaError($e, $request);
        });
    }

    /**
     * Handle HTTP exceptions for Inertia responses
     *
     * @param HttpException $e
     * @param Request $request
     * @return \Illuminate\Http\Response|null
     */
    private static function renderInertiaError(HttpException $e, Request $request)
    {
        $status = $e->getStatusCode();

        // Status codes yang akan ditangani dengan halaman error kustom
        $handledStatuses = [404, 500];

        // Cek jika status code adalah salah satu dari yang ingin kita tangani
        if (in_array($status, $handledStatuses)) {
            // Render halaman error yang sesuai menggunakan Inertia
            return Inertia::render("errors/{$status}", [
                'status' => $status,
                'message' => $e->getMessage() ?: self::getDefaultMessage($status)
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        }

        // Jika tidak ditangani, return null agar Laravel melanjutkan ke handler default
        return null;
    }

    /**
     * Get default error message for status code
     *
     * @param int $status
     * @return string
     */
    private static function getDefaultMessage(int $status): string
    {
        return match ($status) {
            404 => 'Page not found.',
            500 => 'Internal server error.',
            default => 'An error occurred.',
        };
    }
}
