<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'enable_slug_history_redirect')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->boolean('enable_slug_history_redirect')
                    ->default(false)
                    ->after('slug_changed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'enable_slug_history_redirect')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('enable_slug_history_redirect');
            });
        }
    }
};
