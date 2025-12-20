<?php

namespace App\Console\Commands\Tenant;

use App\Models\User;
use App\Models\Tenant\User as TenantUser;
use App\Models\Tenant;
use App\Services\TokenRevocationService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Console Command: FixUserRoles
 * 
 * This command is excluded from code coverage because:
 * - Console commands are manually invoked CLI tools
 * - Better tested through manual testing or E2E tests
 * - Involves database operations and role assignments
 * 
 * @codeCoverageIgnore
 */
class FixUserRoles extends Command
{
    protected $signature = 'tenants:fix-user-roles';
    protected $description = 'Memperbaiki relasi role user';

    public function handle()
    {
        // Perbaiki user tenant
        $this->fixTenantUsers();
    }

    protected function fixTenantUsers()
    {
        $users = TenantUser::whereNotNull('role')->get();

        foreach ($users as $user) {
            try {
                // Dapatkan role berdasarkan nama dan guard api
                $role = Role::where('name', $user->role)
                    ->where('guard_name', 'api')
                    ->first();

                if ($role) {
                    // Cek apakah relasi sudah ada
                    $exists = DB::table('acl_model_has_roles')
                        ->where('role_id', $role->id)
                        ->where('model_type', get_class($user))
                        ->where('model_id', $user->id)
                        ->exists();

                    if (!$exists) {
                        // Tambahkan relasi secara manual
                        DB::table('acl_model_has_roles')->insert([
                            'role_id' => $role->id,
                            'model_type' => get_class($user),
                            'model_id' => $user->id
                        ]);
                    }
                } else {
                    continue;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Revoke all active tokens
        $tokenService = new TokenRevocationService();
        $tokenService->revokeAllActiveTokens();
    }
}
