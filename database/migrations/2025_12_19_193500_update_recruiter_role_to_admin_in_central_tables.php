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
        if (Schema::hasTable('tenant_users') && Schema::hasColumn('tenant_users', 'role')) {
            DB::table('tenant_users')
                ->where('role', 'recruiter')
                ->update(['role' => 'admin']);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'recruiter')
                ->update(['role' => 'admin']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena recruiter adalah role legacy yang tidak boleh digunakan lagi.
    }
};
