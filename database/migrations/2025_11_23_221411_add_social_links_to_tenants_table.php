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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('linkedin')->nullable()->after('company_category_id');
            $table->string('instagram')->nullable()->after('linkedin');
            $table->string('website')->nullable()->after('instagram');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['linkedin', 'instagram', 'website']);
        });
    }
};
