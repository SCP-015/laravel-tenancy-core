<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Web\WebAuthController;

// Landing page public (tidak perlu auth) - Root URL
Route::get('/', fn () => Inertia::render('landing-page'))
    ->name('landing-page');

// Auth Routes (Guest only)
Route::get('/auth/login', fn() => Inertia::render('auth/login', [
    'meta' => ['requiresGuest' => true],
    'isLocal' => app()->environment('local'),
]))->name('login');

// Dev-only Nusawork callback playground (non-production environments only)
if (! app()->environment('production')) {
    Route::get('/dev/auth/nusawork-callback', fn() => Inertia::render('auth/nusawork-dev-callback', [
        'meta' => ['requiresGuest' => true],
    ]))->name('dev.nusawork.callback');
}

// Invite Recruiter Route
Route::get('/auth/{tenantSlug}/invite-recruiter', [WebAuthController::class, 'showInviteRecruiter'])->name('invite.recruiter');

// Google OAuth Routes (perlu session middleware)
Route::prefix('api/auth')->group(function () {
    Route::get('/google', [\App\Http\Controllers\Auth\AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [\App\Http\Controllers\Auth\AuthController::class, 'handleGoogleCallback']);
});


// Session Login Route (untuk Nusawork callback session flow)
Route::get('/session/{sessionId}', [WebAuthController::class, 'handleSessionLogin'])->name('session.login');

// Portal Setup (requires auth via frontend meta)
Route::get('/setup/portal', fn() => Inertia::render('portal/setup', [
    'meta' => [
        'requiresAuth' => true
    ],
]))->name('setup-portal');

// 404 catch-all - HARUS DI AKHIR FILE
Route::fallback(function () {
    return Inertia::render('errors/404', [])->toResponse(request())->setStatusCode(404);
});
