<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Web\WebAuthController;

// 404 catch-all
Route::fallback(function () {
    return Inertia::render('/errors/404', [])->toResponse(request())->setStatusCode(404);
});

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

// Session Login Route (untuk Nusawork callback session flow)
Route::get('/session/{sessionId}', [WebAuthController::class, 'handleSessionLogin'])->name('session.login');

// Landing page public (tidak perlu auth) - Root URL
Route::get('/', fn () => Inertia::render('landing-page'))
    ->name('landing-page');

Route::get('/setup/portal', fn() => Inertia::render('portal/setup', [
    'meta' => [
        'requiresAuth' => true
    ],
]))->name('setup-portal');
