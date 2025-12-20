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
        if (!Schema::hasColumn('users', 'is_owner')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_owner')->default(false);
            });

            // Update is_owner to true where tenant_id equals global_id
            DB::table('users')
                ->whereColumn('tenant_id', 'global_id')
                ->whereNotNull('tenant_id')
                ->whereNotNull('global_id')
                ->update(['is_owner' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_owner')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_owner');
            });
        }
    }
};
