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
        $tables = [
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'role_has_permissions',
            'roles'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasTable("acl_{$table}")) {
                Schema::rename($table, "acl_{$table}");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'role_has_permissions',
            'roles'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable("acl_{$table}")) {
                Schema::rename("acl_{$table}", $table);
            }
        }
    }
};
