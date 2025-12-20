<?php

use Database\Seeders\Indonesia\CitiesSeeder;
use Database\Seeders\Indonesia\DistrictsSeeder;
use Database\Seeders\Indonesia\ProvincesSeeder;
use Database\Seeders\Indonesia\VillagesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => ProvincesSeeder::class,
        ]);
        Artisan::call('db:seed', [
            '--class' => CitiesSeeder::class,
        ]);
        Artisan::call('db:seed', [
            '--class' => DistrictsSeeder::class,
        ]);
        Artisan::call('db:seed', [
            '--class' => VillagesSeeder::class,
        ]);
    }
};
