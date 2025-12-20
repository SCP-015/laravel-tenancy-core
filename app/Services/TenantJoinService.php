<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Tenant\User as TenantUser;
use App\Services\TenantUserAuditService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class TenantJoinService
{
    /**
     * Sync user di tenant database
     *
     * @param  Tenant  $tenant
     * @param  User  $user
     * @param  Request  $request
     * @return void
     */
    public static function syncTenantUser(Tenant $tenant, User $user, Request $request)
    {
        $tenant->run(function () use ($user, $tenant, $request) {
            $tenantUser = \App\Models\Tenant\User::where('global_id', $user->global_id)->first();
            $isNewUser = !$tenantUser;

            if ($tenantUser) {
                self::updateExistingTenantUser($tenantUser, $tenant, $request);
            } else {
                $tenantUser = self::createNewTenantUser($user, $tenant, $request);
            }

            // Assign role ke user tenant
            if ($tenantUser && $tenantUser->role) {
                Role::firstOrCreate([
                    'name' => $tenantUser->role,
                    'guard_name' => 'api',
                ]);

                $tenantUser->syncRoles([$tenantUser->role]);
            }

            // Create manual audit log untuk event login
            self::createLoginAuditLog($tenantUser, $isNewUser, $request, $user);
        });
    }

    /**
     * Update existing tenant user
     *
     * @param  \App\Models\Tenant\User  $tenantUser
     * @param  Tenant  $tenant
     * @param  Request  $request
     * @return void
     */
    public static function updateExistingTenantUser($tenantUser, Tenant $tenant, Request $request)
    {
        // Disable auditing untuk update ini karena kita akan create manual audit log dengan event 'login'
        $tenantUser->disableAuditing();
        
        $tenantUser->update([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'tenant_join_date' => is_null($tenantUser->tenant_join_date) ? now() : $tenantUser->tenant_join_date,
            'last_login_ip' => $request->ip(),
            'last_login_at' => now(),
            'last_login_user_agent' => $request->userAgent(),
            'updated_at' => now(),
        ]);
        
        $tenantUser->enableAuditing();
    }

    /**
     * Create new tenant user
     *
     * @param  User  $user
     * @param  Tenant  $tenant
     * @param  Request  $request
     * @return \App\Models\Tenant\User
     */
    public static function createNewTenantUser(User $user, Tenant $tenant, Request $request)
    {
        // Disable auditing untuk create ini karena kita akan create manual audit log dengan event 'login'
        \App\Models\Tenant\User::disableAuditing();
        
        $tenantUser = \App\Models\Tenant\User::create([
            'global_id' => $user->global_id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'password' => $user->password,
            'tenant_id' => $tenant->id,
            'google_id' => $user->google_id,
            'nusawork_id' => $user->nusawork_id,
            'avatar' => $user->avatar,
            'role' => 'admin',
            'tenant_join_date' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_at' => now(),
            'last_login_user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        \App\Models\Tenant\User::enableAuditing();
        
        return $tenantUser;
    }

    /**
     * Attach user to tenant if not already attached
     *
     * @param  User  $user
     * @param  Tenant  $tenant
     * @return void
     * 
     * @codeCoverageIgnore - Requires complex pivot table setup with central/tenant connections
     */
    public static function attachUserToTenant(User $user, Tenant $tenant)
    {
        if (!$user->tenants()->where('tenant_id', $tenant->id)->exists()) {
            $user->tenants()->attach($tenant->id);
        }
    }

    /**
     * Update tenant user record di central database
     *
     * @param  User  $user
     * @param  Tenant  $tenant
     * @return void
     */
    public static function updateCentralTenantUser(User $user, Tenant $tenant)
    {
        $tenantUserCentral = $user->tenantUsers()->where('tenant_id', $tenant->id)->first();
        if ($tenantUserCentral) {
            $tenantUserCentral->update([
                'google_id' => $user->google_id,
                'nusawork_id' => $user->nusawork_id,
                'avatar' => $user->avatar,
                'role' => 'admin',
                'tenant_join_date' => is_null($tenantUserCentral->tenant_join_date) ? now() : $tenantUserCentral->tenant_join_date,
                'created_at' => is_null($tenantUserCentral->created_at) ? now() : $tenantUserCentral->created_at,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Create manual audit log untuk event login
     *
     * @param  \App\Models\Tenant\User  $tenantUser
     * @param  bool  $isNewUser
     * @param  Request  $request
     * @param  User  $actorUser  Central user yang login
     * @return void
     */
    private static function createLoginAuditLog($tenantUser, bool $isNewUser, Request $request, User $actorUser)
    {
        // Format last_login_at safely (bisa string atau Carbon object)
        $lastLoginAt = $tenantUser->last_login_at;
        if ($lastLoginAt instanceof \Carbon\Carbon) {
            $lastLoginAt = $lastLoginAt->format('Y-m-d H:i:s');
        }
        
        // Prepare new_values untuk audit log - selalu sertakan detail user
        $newValues = [
            'role' => $tenantUser->role,
            'name' => $tenantUser->name,
            'email' => $tenantUser->email,
            'last_login_ip' => $tenantUser->last_login_ip,
            'last_login_at' => $lastLoginAt,
        ];

        if ($isNewUser && $tenantUser->role === 'admin') {
            $newValues['is_new_admin'] = true;
        }

        // Create audit log manual dengan event 'login'
        TenantUserAuditService::logLogin($tenantUser, $newValues, $request, $actorUser);
    }
}
