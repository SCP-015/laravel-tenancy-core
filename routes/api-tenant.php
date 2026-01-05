<?php

use App\Http\Controllers\Tenant;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

$integer = '([0-9]+)';

// Rute API publik
Route::group(['prefix' => 'public'], function () use ($integer) {
    Route::get('/portal', [TenantController::class, 'guestPortal'])
        ->name('portal.guest');
});

// Rute API yang dilindungi dengan token dari central API
Route::middleware(['auth:api'])->group(function () use ($integer) {
    Route::get('/app-config', [\App\Http\Controllers\AppConfigController::class, 'show']);

    Route::group(['prefix' => 'settings'], function () use ($integer) {
        Route::get('profile', [Tenant\ProfileController::class, 'index'])
            ->name('profile.index');

        Route::get('/roles', [Tenant\RecruiterController::class, 'getRoles'])
            ->name('recruiter.roles')
            ->middleware('permission:team.view');

        // Recruiter
        Route::group(['prefix' => 'recruiter'], function () use ($integer) {
            Route::get('/', [Tenant\RecruiterController::class, 'index'])
                ->name('recruiter.index')
                ->middleware('permission:team.view');

            Route::post('/invite', [Tenant\RecruiterController::class, 'invite'])
                ->name('recruiter.invite')
                ->middleware('permission:team.invite');

            Route::delete('/{recruiter}', [Tenant\RecruiterController::class, 'destroy'])
                ->name('recruiter.destroy')
                ->where('recruiter', $integer)
                ->middleware('permission:team.remove');

            Route::put('/{recruiter}/role', [Tenant\RecruiterController::class, 'updateRole'])
                ->name('recruiter.update-role')
                ->where('recruiter', $integer)
                ->middleware('permission:team.manage_roles');
        });

        Route::post('refresh-code', [Tenant\SettingController::class, 'generateCode'])
            ->name('refresh-code')
            ->middleware('permission:settings.generate_code');

        // Job Positions
        Route::prefix('job-positions')->group(function () {
            Route::get('archived', [Tenant\JobPositionController::class, 'archived'])
                ->middleware('permission:job_positions.view');

            Route::post('{jobPositionId}/restore', [Tenant\JobPositionController::class, 'restore'])
                ->middleware('permission:job_positions.restore');

            Route::delete('{jobPositionId}/force', [Tenant\JobPositionController::class, 'forceDelete'])
                ->middleware('permission:job_positions.force_delete');
        });

        Route::apiResource('job-positions', Tenant\JobPositionController::class)
            ->middleware([
                'permission:job_positions.view|job_positions.create|job_positions.update|job_positions.delete'
            ]);

        // Job Levels
        Route::prefix('job-levels')->group(function () {
            Route::get('archived', [Tenant\JobLevelController::class, 'archived'])
                ->middleware('permission:job_levels.view');

            Route::post('{jobLevelId}/restore', [Tenant\JobLevelController::class, 'restore'])
                ->middleware('permission:job_levels.restore');

            Route::delete('{jobLevelId}/force', [Tenant\JobLevelController::class, 'forceDelete'])
                ->middleware('permission:job_levels.force_delete');
        });

        Route::apiResource('job-levels', Tenant\JobLevelController::class)
            ->middleware([
                'permission:job_levels.view|job_levels.create|job_levels.update|job_levels.delete'
            ]);

        // Education Levels
        Route::prefix('education-levels')->group(function () {
            Route::get('archived', [Tenant\EducationLevelController::class, 'archived'])
                ->middleware('permission:education_levels.view');

            Route::post('{educationLevelId}/restore', [Tenant\EducationLevelController::class, 'restore'])
                ->middleware('permission:education_levels.restore');

            Route::delete('{educationLevelId}/force', [Tenant\EducationLevelController::class, 'forceDelete'])
                ->middleware('permission:education_levels.force_delete');
        });

        Route::apiResource('education-levels', Tenant\EducationLevelController::class)
            ->middleware([
                'permission:education_levels.view|education_levels.create|education_levels.update|education_levels.delete'
            ]);

        // Experience Levels
        Route::prefix('experience-levels')->group(function () {
            Route::get('archived', [Tenant\ExperienceLevelController::class, 'archived'])
                ->middleware('permission:experience_levels.view');

            Route::post('{experienceLevelId}/restore', [Tenant\ExperienceLevelController::class, 'restore'])
                ->middleware('permission:experience_levels.restore');

            Route::delete('{experienceLevelId}/force', [Tenant\ExperienceLevelController::class, 'forceDelete'])
                ->middleware('permission:experience_levels.force_delete');
        });

        Route::apiResource('experience-levels', Tenant\ExperienceLevelController::class)
            ->middleware([
                'permission:experience_levels.view|experience_levels.create|experience_levels.update|experience_levels.delete'
            ]);

        Route::apiResource('genders', Tenant\GenderController::class)->only('index');
    });

    // Audit Logs (Global)
    Route::get('/audit-logs', [Tenant\AuditLogController::class, 'index'])
        ->name('audit-logs.index')
        ->middleware('permission:audit_logs.view');
    
    Route::get('/audit-logs/model-types', [Tenant\AuditLogController::class, 'getModelTypes'])
        ->name('audit-logs.model-types')
        ->middleware('permission:audit_logs.view');
    
    Route::get('/audit-logs/event-types', [Tenant\AuditLogController::class, 'getEventTypes'])
        ->name('audit-logs.event-types')
        ->middleware('permission:audit_logs.view');

    Route::group(['prefix' => 'integrations/nusawork'], function () {
        Route::get('/master-data', [Tenant\NusaworkController::class, 'getMasterData'])
            ->name('integrations.nusawork.master-data');
    });

    Route::post('/feedback', [Tenant\FeedbackController::class, 'submit']);

    Route::get('/my-feedback', [Tenant\ListFeedbackController::class, 'index'])
    ->name('my-feedback.index');

    // Default Signers Management (untuk Digital Signature)
    Route::prefix('default-signers')->name('default-signers.')->group(function () {
        Route::get('/', [Tenant\DefaultSignerController::class, 'index'])->name('index');
        Route::get('/users', [Tenant\DefaultSignerController::class, 'getAvailableUsers'])->name('users');
        Route::get('/workgroups', [Tenant\DefaultSignerController::class, 'getWorkgroups'])->name('workgroups');
        Route::get('/workgroup/{workgroup_id}', [Tenant\DefaultSignerController::class, 'getSignersForWorkgroup'])->name('by-workgroup');
        Route::post('/', [Tenant\DefaultSignerController::class, 'store'])->name('store');
        Route::put('/{id}', [Tenant\DefaultSignerController::class, 'update'])->name('update');
        Route::delete('/{id}', [Tenant\DefaultSignerController::class, 'destroy'])->name('destroy');
    });

    // API MOBILE - Digital Signature
    // Unified Controller with Web
    Route::prefix('digital-signature')->name('api.digital-signature.')->group(function () {
        // 1. Dashboard & Data
        Route::get('/dashboard', [Tenant\DigitalSignatureController::class, 'index'])->name('dashboard');
        
        // 2. Admin: Create Root CA
        Route::post('/ca', [Tenant\DigitalSignatureController::class, 'storeCA'])->name('ca.store');
        
        // 3. User: Issue Certificate
        Route::post('/certificates', [Tenant\DigitalSignatureController::class, 'issueCertificate'])->name('certificates.issue');
        
        // 4. Create Signing Session (Upload & Assign)
        Route::post('/session', [Tenant\DigitalSignatureController::class, 'createSession'])->name('session.create');
        
        // 5. Sign Document
        Route::post('/sign/{signatureId}', [Tenant\DigitalSignatureController::class, 'signDocument'])->name('sign');
        
        // 6. Verify File
        Route::post('/verify', [Tenant\DigitalSignatureController::class, 'verifyUploadedFile'])->name('verify');
        
        // 7. Download Document
        Route::get('/download/{documentId}', [Tenant\DigitalSignatureController::class, 'downloadDocument'])->name('download');
        
        // 8. Scan QR Code (untuk test di localhost)
        Route::post('/scan-qr', [Tenant\DigitalSignatureController::class, 'scanQRCode'])->name('scan-qr');
    });
});


