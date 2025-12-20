<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Permissions
    |--------------------------------------------------------------------------
    |
    | Daftar permission default yang tersedia di aplikasi.
    |
    */
    
    // Permission untuk Manajemen Tim
    'team' => [
        'view' => 'team.view',
        'invite' => 'team.invite',
        'remove' => 'team.remove',
        'manage_roles' => 'team.manage_roles',
    ],
    
    // Permission untuk Master Data
    'master_data' => [
        // Job Positions
        'job_positions' => [
            'view' => 'job_positions.view',
            'create' => 'job_positions.create',
            'update' => 'job_positions.update',
            'delete' => 'job_positions.delete',
            'restore' => 'job_positions.restore',
            'force_delete' => 'job_positions.force_delete',
        ],
        // Job Levels
        'job_levels' => [
            'view' => 'job_levels.view',
            'create' => 'job_levels.create',
            'update' => 'job_levels.update',
            'delete' => 'job_levels.delete',
            'restore' => 'job_levels.restore',
            'force_delete' => 'job_levels.force_delete',
        ],
        // Education Levels
        'education_levels' => [
            'view' => 'education_levels.view',
            'create' => 'education_levels.create',
            'update' => 'education_levels.update',
            'delete' => 'education_levels.delete',
            'restore' => 'education_levels.restore',
            'force_delete' => 'education_levels.force_delete',
        ],
        // Experience Levels
        'experience_levels' => [
            'view' => 'experience_levels.view',
            'create' => 'experience_levels.create',
            'update' => 'experience_levels.update',
            'delete' => 'experience_levels.delete',
            'restore' => 'experience_levels.restore',
            'force_delete' => 'experience_levels.force_delete',
        ],
    ],
    
    // Permission untuk Pengaturan
    'settings' => [
        'view' => 'settings.view',
        'update' => 'settings.update',
        'generate_code' => 'settings.generate_code',
        'generate_invite_link' => 'settings.generate_invite_link',
    ],

    'integrations' => [
        'nusawork' => [
            'master_data' => 'integrations.nusawork.master-data',
        ],
    ],

    'audit_logs' => [
        'view' => 'audit_logs.view',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Recruiter Permissions
    |--------------------------------------------------------------------------
    |
    | Daftar permission default untuk role recruiter.
    |
    */
    'admin_default_permissions' => [
        'team.view',
        'team.invite',
        'team.remove',
        'team.manage_roles',

        'settings.view',
        'settings.update',
        'settings.generate_code',
        'settings.generate_invite_link',

        'audit_logs.view',

        'job_positions.view',
        'job_positions.create',
        'job_positions.update',
        'job_positions.delete',
        'job_positions.restore',
        'job_positions.force_delete',

        'job_levels.view',
        'job_levels.create',
        'job_levels.update',
        'job_levels.delete',
        'job_levels.restore',
        'job_levels.force_delete',

        'education_levels.view',
        'education_levels.create',
        'education_levels.update',
        'education_levels.delete',
        'education_levels.restore',
        'education_levels.force_delete',

        'experience_levels.view',
        'experience_levels.create',
        'experience_levels.update',
        'experience_levels.delete',
        'experience_levels.restore',
        'experience_levels.force_delete',

        'integrations.nusawork.master-data',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Super Admin Permissions
    |--------------------------------------------------------------------------
    |
    | Permission yang dimiliki oleh super admin (semua permission)
    |
    */
    'super_admin_permissions' => [
        '*' // Semua permission
    ],
];
