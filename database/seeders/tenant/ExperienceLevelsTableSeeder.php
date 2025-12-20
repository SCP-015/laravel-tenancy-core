<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\ExperienceLevel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExperienceLevelsTableSeeder extends Seeder
{
    public function run(): void
    {
        ExperienceLevel::insert([
            [
                'name' => 'Fresh Graduate',
                'index' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => '1 - 3 tahun',
                'index' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => '3 - 5 tahun',
                'index' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => '5 tahun ke atas',
                'index' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
