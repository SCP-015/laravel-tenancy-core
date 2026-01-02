<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\DigitalSignatureController;
use App\Http\Middleware\InitializeTenancyByPathOrId;
use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByPathOrId::class,
    LocalizationMiddleware::class,
])->prefix('{tenant}')->group(base_path('routes/web-tenant.php'));

// Route API tenant (menggunakan path)
Route::middleware([
    'api',
    InitializeTenancyByPathOrId::class,
    LocalizationMiddleware::class,
])->prefix('{tenant}/api')->group(base_path('routes/api-tenant.php'));
