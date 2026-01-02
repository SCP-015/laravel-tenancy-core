<?php

use App\Exceptions\ApiExceptionHandler;
use App\Exceptions\InertiaExceptionHandler;
use App\Http\Middleware\InjectBearerFromCookie;
use App\Http\Middleware\LocalizationMiddleware;
use App\Http\Middleware\SetCacheHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Set cache headers untuk static assets dan landing page
        $middleware->append(SetCacheHeaders::class);
        
        // Exempt digital signature routes from CSRF
        $middleware->validateCsrfTokens(except: [
            '*/admin/digital-signature/*',
        ]);
        
        $middleware->group('universal', [
            LocalizationMiddleware::class,
        ]);
        $middleware->group('api', [
            InjectBearerFromCookie::class,
            LocalizationMiddleware::class,
        ]);
        
        // Register permission middleware
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
        ApiExceptionHandler::handle($exceptions);
        InertiaExceptionHandler::handle($exceptions);
    })->create();
