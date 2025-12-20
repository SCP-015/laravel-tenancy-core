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
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'recruiter')
                ->update(['role' => 'admin']);
        }

        if (!Schema::hasTable('roles') || !Schema::hasColumn('roles', 'name')) {
            return;
        }

        $hasAdminRole = DB::table('roles')->where('name', 'admin')->exists();
        $hasRecruiterRole = DB::table('roles')->where('name', 'recruiter')->exists();

        if (!$hasRecruiterRole) {
            return;
        }

        if (!$hasAdminRole) {
            DB::table('roles')
                ->where('name', 'recruiter')
                ->update(['name' => 'admin']);

            return;
        }

        if (!Schema::hasTable('model_has_roles')) {
            return;
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $recruiterRoleId = DB::table('roles')->where('name', 'recruiter')->value('id');

        if (!$adminRoleId || !$recruiterRoleId) {
            return;
        }

        DB::table('model_has_roles')
            ->where('role_id', $recruiterRoleId)
            ->update(['role_id' => $adminRoleId]);

        DB::table('roles')->where('id', $recruiterRoleId)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena recruiter adalah role legacy yang tidak boleh digunakan lagi.
    }
};
