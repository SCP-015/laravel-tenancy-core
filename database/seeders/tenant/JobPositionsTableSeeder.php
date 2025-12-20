<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\JobPosition as TenantJobPosition;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobPositionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TenantJobPosition::insert([
            [
                'id_parent' => null,
                'name' => 'Direktur Utama',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_parent' => null,
                'name' => 'Manager Operasional',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_parent' => null,
                'name' => 'Staff Operasional',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
