<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update guard_name untuk permissions
        if (Schema::hasTable('acl_permissions')) {
            DB::table('acl_permissions')
                ->where('guard_name', 'web')
                ->update(['guard_name' => 'api']);
        }

        // Update guard_name untuk roles
        if (Schema::hasTable('acl_roles')) {
            DB::table('acl_roles')
                ->where('guard_name', 'web')
                ->update(['guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke web jika ingin rollback
        if (Schema::hasTable('acl_permissions')) {
            DB::table('acl_permissions')
                ->where('guard_name', 'api')
                ->update(['guard_name' => 'web']);
        }

        if (Schema::hasTable('acl_roles')) {
            DB::table('acl_roles')
                ->where('guard_name', 'api')
                ->update(['guard_name' => 'web']);
        }
    }
};
