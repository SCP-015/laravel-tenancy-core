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
        // Tambah kolom baru ke tenant_users untuk mendukung multiple tenant
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('global_user_id');
            $table->string('nusawork_id')->nullable()->after('google_id');
            $table->string('avatar')->nullable()->after('nusawork_id');
            $table->enum('role', ['admin', 'super_admin'])->default('admin')->after('avatar');
            $table->boolean('is_owner')->default(false)->after('role');
            $table->boolean('is_nusawork_integrated')->default(false)->after('is_owner');
            $table->timestamp('tenant_join_date')->nullable()->after('is_nusawork_integrated');
            $table->timestamp('nusawork_integrated_at')->nullable()->after('tenant_join_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'nusawork_id',
                'avatar',
                'role',
                'is_owner',
                'is_nusawork_integrated',
                'tenant_join_date',
                'nusawork_integrated_at',
                'created_at',
                'updated_at'
            ]);
        });
    }
};
