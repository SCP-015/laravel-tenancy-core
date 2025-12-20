<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'slug')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('slug')->unique()->nullable()->after('name');
            });
        }

        // Update semua tenant yang belum memiliki slug
        Tenant::whereNull('slug')->orWhere('slug', '')->each(function ($tenant) {
            $tenant->slug = $tenant->generateSlug($tenant->name);
            $tenant->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'slug')) {
            Schema::table('tenants', function (Blueprint $table) {
                try {
                    $table->dropUnique('tenants_slug_unique');
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropColumn('slug');
            });
        }
    }
};
