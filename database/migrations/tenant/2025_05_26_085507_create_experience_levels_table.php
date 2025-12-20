<?php

use Database\Seeders\Tenant\ExperienceLevelsTableSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('experience_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('index')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('experience_levels')) {
            Artisan::call('db:seed', [
                '--class' => ExperienceLevelsTableSeeder::class,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experience_levels');
    }
};
