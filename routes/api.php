<?php

use App\Http\Controllers;
use App\Http\Controllers\Tenant;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationIndonesiaController;

// Rute autentikasi publik
Route::prefix('auth')->group(function () {
    /* Public Key for Cross-Project SSO */
    Route::get('public-key', [Controllers\PublicKeyController::class, 'show'])->middleware(['throttle:60,1']);
    Route::get(
        'public-key/metadata',
        [Controllers\PublicKeyController::class, 'metadata']
    )
        ->middleware(['throttle:60,1']);

    Route::post('/login', [Controllers\Auth\AuthController::class, 'login']);
    Route::post('/validate-invite', [Controllers\Auth\AuthController::class, 'validateInvite']);

    // Login dengan Google dan Nusawork
    // Endpoint untuk redirect ke Google
    Route::get(
        '/google',
        [Controllers\Auth\AuthController::class, 'redirectToGoogle']
    );
    // Endpoint callback dari Google
    Route::get(
        '/google/callback',
        [Controllers\Auth\AuthController::class, 'handleGoogleCallback']
    );
    Route::post('/nusawork/callback', [Controllers\Auth\AuthController::class, 'nusaworkCallback']);

    // Rute yang dilindungi
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [Controllers\Auth\AuthController::class, 'logout']);
    });
});

    Route::middleware('auth:api')->group(function () {
        Route::post('/feedback', [Tenant\FeedbackController::class, 'submit']);
    });
    
Route::post('/feedback-public', [Tenant\FeedbackController::class, 'submitPublic']);

// location indonesia
Route::prefix('indonesia')->name('indonesia.')->group(function () {
    // Provinces
    Route::get('/provinces', [LocationIndonesiaController::class, 'indexProvinces'])->name('provinces.index');
    Route::get(
        '/provinces/{provinceCode}',
        [LocationIndonesiaController::class, 'showProvince']
    )
        ->name('provinces.show');
    Route::get(
        '/provinces/{provinceCode}/cities',
        [LocationIndonesiaController::class, 'indexCities']
    )
        ->name('provinces.cities.index');

    // Cities
    Route::get('/cities/{cityCode}', [LocationIndonesiaController::class, 'showCity'])->name('cities.show');
    Route::get(
        '/cities/{cityCode}/districts',
        [LocationIndonesiaController::class, 'indexDistricts']
    )
        ->name('cities.districts.index');

    // Districts
    Route::get('/districts/{districtCode}', [LocationIndonesiaController::class, 'showDistrict'])->name('districts.show');
    Route::get(
        '/districts/{districtCode}/villages',
        [LocationIndonesiaController::class, 'indexVillages']
    )
        ->name('districts.villages.index');

    // Villages
    Route::get('/villages/{villageCode}', [LocationIndonesiaController::class, 'showVillage'])->name('villages.show');
});

// Rute portal yang dilindungi
Route::middleware(['auth:api'])->group(function () {
    Route::get('/app-config', [Controllers\AppConfigController::class, 'show']);

    Route::prefix('portal')->group(function () {
        Route::get('/', [Controllers\TenantController::class, 'index']);
        Route::post('/', [Controllers\TenantController::class, 'store']);
        Route::get('/generate-code', [Controllers\TenantController::class, 'generateCode']);
        Route::post('/join', [Controllers\TenantController::class, 'join']);
        Route::get('/by-id/{id}', [Controllers\TenantController::class, 'showById']);
        Route::get('/{id}/nusawork-integration', [Controllers\TenantController::class, 'checkNusaworkIntegration']);
        Route::get('/{id}', [Controllers\TenantController::class, 'show']);
        Route::post('/{id}', [Controllers\TenantController::class, 'update']);
        Route::post('/{id}/slug', [Controllers\TenantController::class, 'updateSlug']);
        Route::delete('/{id}', [Controllers\TenantController::class, 'destroy']);
    });

    Route::apiResource('company-categories', Controllers\CompanyCategoryController::class)->except([
        'destroy',
    ]);
});
