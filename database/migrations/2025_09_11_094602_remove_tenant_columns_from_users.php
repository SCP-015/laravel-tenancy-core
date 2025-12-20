<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus kolom yang sudah dipindahkan ke tenant_users
        // Tetap simpan google_id, nusawork_id, dan avatar di users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tenant_id',
                'role',
                'is_nusawork_integrated',
                'tenant_join_date',
                'nusawork_integrated_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan kolom ke users (kecuali google_id, nusawork_id, avatar yang tetap ada)
        Schema::table('users', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('global_id');
            $table->enum('role', ['admin', 'super_admin'])->default('admin')->after('avatar');
            $table->boolean('is_nusawork_integrated')->default(false)->after('role');
            $table->timestamp('tenant_join_date')->nullable()->after('is_nusawork_integrated');
            $table->timestamp('nusawork_integrated_at')->nullable()->after('tenant_join_date');
        });
    }
};
