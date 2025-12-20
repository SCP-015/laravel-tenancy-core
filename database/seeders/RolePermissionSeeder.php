<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions from config
        $allPermissions = [];
        
        // Ambil semua permission dari config
        $permissionGroups = config('permissions');
        
        // Loop melalui semua group permission (kecuali admin_default_permissions dan super_admin_permissions)
        foreach ($permissionGroups as $group => $permissions) {
            if (in_array($group, ['admin_default_permissions', 'super_admin_permissions'])) {
                continue;
            }
            
            if (is_array($permissions)) {
                foreach ($permissions as $key => $permission) {
                    if (is_array($permission)) {
                        // Jika permission berupa array (seperti di dalam group)
                        foreach ($permission as $subPermission) {
                            $allPermissions[] = $subPermission;
                        }
                    } else {
                        // Jika permission berupa string langsung
                        $allPermissions[] = $permission;
                    }
                }
            }
        }
        
        // Hapus duplikat
        $allPermissions = array_unique($allPermissions);
        
        // Buat permission di database
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api']
            );
        }

        // Create roles and assign permissions
        // Super Admin - gets all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - gets permissions from config
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminPermissions = config('permissions.admin_default_permissions', []);
        $admin->givePermissionTo($adminPermissions);

        // Assign super_admin role to first user (usually the one created during installation)
        if ($user = \App\Models\User::first()) {
            $user->assignRole('super_admin');
        }
    }
}
