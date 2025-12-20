<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([

            // PassportClientSeeder::class, // Jalankan PassportClientSeeder untuk membuat Passport client di tenant

            // JobPositionsTableSeeder::class,
            // JobLevelsTableSeeder::class,
            // EducationLevelsTableSeeder::class,
            // ExperienceLevelsTableSeeder::class,
            // GenderSeeder::class,
        ]);
    }
}
