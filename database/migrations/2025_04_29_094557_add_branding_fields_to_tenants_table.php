<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('theme_color', 7)->nullable();
            $table->text('header_image')->nullable();
            $table->text('profile_image')->nullable();
            $table->text('company_values')->nullable();
            $table->unsignedInteger('employee_range_start')->nullable();
            $table->unsignedInteger('employee_range_end')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'theme_color',
                'header_image',
                'profile_image',
                'company_values',
                'employee_range_start',
                'employee_range_end',
            ]);
        });
    }
};
