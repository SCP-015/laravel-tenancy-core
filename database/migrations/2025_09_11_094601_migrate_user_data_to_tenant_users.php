<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrasikan data dari users ke tenant_users
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            // Cek apakah user sudah memiliki record di tenant_users
            $existingTenantUser = DB::table('tenant_users')
                ->where('global_user_id', $user->global_id)
                ->where('tenant_id', $user->tenant_id)
                ->first();

            if (!$existingTenantUser && $user->tenant_id) {
                // Insert data ke tenant_users
                DB::table('tenant_users')->insert([
                    'tenant_id' => $user->tenant_id,
                    'global_user_id' => $user->global_id,
                    'google_id' => $user->google_id,
                    'nusawork_id' => $user->nusawork_id,
                    'avatar' => $user->avatar,
                    'role' => $user->role === 'recruiter' ? 'admin' : $user->role,
                    'is_owner' => $user->tenant_id == $user->global_id,
                    'is_nusawork_integrated' => $user->is_nusawork_integrated ?? false,
                    'tenant_join_date' => $user->tenant_join_date ?? $user->created_at,
                    'nusawork_integrated_at' => $user->nusawork_integrated_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($existingTenantUser) {
                // Update existing record dengan data dari users
                DB::table('tenant_users')
                    ->where('id', $existingTenantUser->id)
                    ->update([
                        'google_id' => $user->google_id,
                        'nusawork_id' => $user->nusawork_id,
                        'avatar' => $user->avatar,
                        'role' => $user->role === 'recruiter' ? 'admin' : $user->role,
                        'is_owner' => $user->tenant_id == $user->global_id,
                        'is_nusawork_integrated' => $user->is_nusawork_integrated ?? false,
                        'tenant_join_date' => $user->tenant_join_date ?? $user->created_at,
                        'nusawork_integrated_at' => $user->nusawork_integrated_at,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan data dari tenant_users ke users
        $tenantUsers = DB::table('tenant_users')->get();

        foreach ($tenantUsers as $tenantUser) {
            DB::table('users')
                ->where('global_id', $tenantUser->global_user_id)
                ->update([
                    'tenant_id' => $tenantUser->tenant_id,
                    'google_id' => $tenantUser->google_id,
                    'nusawork_id' => $tenantUser->nusawork_id,
                    'avatar' => $tenantUser->avatar,
                    'role' => $tenantUser->role,
                    'is_nusawork_integrated' => $tenantUser->is_nusawork_integrated,
                    'tenant_join_date' => $tenantUser->tenant_join_date,
                    'nusawork_integrated_at' => $tenantUser->nusawork_integrated_at,
                ]);
        }
    }
};
