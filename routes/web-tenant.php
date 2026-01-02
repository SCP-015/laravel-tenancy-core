<?php

use App\Http\Resources\TenantResource;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Routes
Route::get('/', function () {
    return Inertia::render('portal/public-company', [
        'company' => TenantResource::make(tenant())->resolve(),
    ]);
})->name('landing-page');

// Protected Routes (Auth only)
Route::group(['prefix' => 'admin'], function () {
    Route::get('/onboarding', fn() => Inertia::render('dashboard/index', [
        'meta' => [
            'parent_menu' => 'dashboard',
            'requiresAuth' => true
        ],
    ]))->name('home');

    Route::get('/', fn() => Inertia::render('dashboard/index', [
        'meta' => [
            'parent_menu' => 'dashboard',
            'requiresAuth' => true
        ],
    ]))->name('dashboard');

    Route::get('/settings', fn() => Inertia::render('settings/index', [
        'meta' => [
            'parent_menu' => 'settings',
            'requiresAuth' => true
        ],
    ]))->name('settings');

    // Admin Digital Signature Module
    Route::middleware([\App\Http\Middleware\InjectBearerFromCookie::class])->prefix('digital-signature')->name('digital-signature.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'index'])->name('index');
        Route::post('/ca', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'storeCA'])->name('ca.store');
        Route::post('/certificates', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'issueCertificate'])->name('certificates.store');
        
        Route::post('/session', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'createSession'])->name('session.store');
        Route::post('/sign/{signatureId}', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'signDocument'])->name('sign');
        
        Route::get('/verify-signature', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'verifyPage'])->name('verify-page');
        Route::post('/verify-file', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'verifyUploadedFile'])->name('verify-file');
        
        Route::get('/download/{document}', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'downloadDocument'])->name('download');
        Route::get('/verify/{document}', [\App\Http\Controllers\Tenant\DigitalSignatureController::class, 'verifyDocument'])->name('verify');
    });
});

// Redirect /home to /
// Route::redirect('/home', '/');
