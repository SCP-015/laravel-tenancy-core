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
});

// Redirect /home to /
// Route::redirect('/home', '/');
