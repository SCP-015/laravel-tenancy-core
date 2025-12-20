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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tenant_join_date')->nullable()->after('remember_token');
            $table->string('last_login_ip')->nullable()->after('tenant_join_date');
            $table->timestamp('last_login_at')->nullable()->after('last_login_ip');
            $table->string('last_login_user_agent')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tenant_join_date');
            $table->dropColumn('last_login_ip');
            $table->dropColumn('last_login_at');
            $table->dropColumn('last_login_user_agent');
        });
    }
};
