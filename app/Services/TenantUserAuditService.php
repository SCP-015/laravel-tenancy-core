<?php

namespace App\Services;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class TenantUserAuditService
{
    /**
     * Log audit event login untuk Tenant User.
     * Struktur data disamakan dengan implementasi manual yang sudah ada.
     *
     * @param  TenantUser  $tenantUser
     * @param  array  $newValues
     * @param  Request|null  $request
     * @param  mixed  $actorUser  User yang login (central user), jika null gunakan auth()->user()
     * @return void
     */
    public static function logLogin(TenantUser $tenantUser, array $newValues, ?Request $request = null, $actorUser = null): void
    {
        $request = $request ?? request();
        $actorUser = $actorUser ?? auth()->user();

        Audit::create([
            'user_type' => $actorUser ? get_class($actorUser) : null,
            'user_id' => $actorUser ? $actorUser->id : null,
            'auditable_type' => get_class($tenantUser),
            'auditable_id' => $tenantUser->id,
            'event' => 'login',
            'old_values' => null,
            'new_values' => $newValues,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => 'login',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log audit event update role recruiter di tenant.
     *
     * @param  mixed  $actorUser  User yang melakukan perubahan
     * @param  TenantUser  $tenantUser  User tenant yang diubah rolenya
     * @param  array  $oldValues
     * @param  array  $newValues
     * @return void
     */
    public static function logRoleUpdated($actorUser, TenantUser $tenantUser, array $oldValues, array $newValues): void
    {
        $request = request();

        Audit::create([
            'user_type' => get_class($actorUser),
            'user_id' => $actorUser->id,
            'auditable_type' => get_class($tenantUser),
            'auditable_id' => $tenantUser->id,
            'event' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => 'role_updated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log audit event delete recruiter di tenant.
     *
     * @param  mixed  $actorUser  User yang menghapus recruiter
     * @param  TenantUser  $tenantUser  User tenant yang dihapus
     * @param  array  $oldValues
     * @param  array  $newValues
     * @return void
     */
    public static function logRecruiterDeleted($actorUser, TenantUser $tenantUser, array $oldValues, array $newValues): void
    {
        $request = request();

        Audit::create([
            'user_type' => get_class($actorUser),
            'user_id' => $actorUser->id,
            'auditable_type' => get_class($tenantUser),
            'auditable_id' => $tenantUser->id,
            'event' => 'deleted',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => 'recruiter_deleted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
