<?php

use Database\Seeders\Tenant\JobPositionsTableSeeder;
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
        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_parent')->nullable()->index();
            $table->string('name');

            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('job_positions')) {
            Artisan::call('db:seed', [
                '--class' => JobPositionsTableSeeder::class,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_positions');
    }
};
