<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\DB;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = new ClientRepository();

        // Buat Password Grant Client jika belum ada
        if (!DB::table('oauth_clients')->where('name', 'NusaHire Password Grant Client')->exists()) {
            $client->createPasswordGrantClient(
                null,
                'NusaHire Password Grant Client',
                config('app.url')
            );
        }

        // Buat Personal Access Client jika belum ada
        if (!DB::table('oauth_clients')->where('name', 'NusaHire Personal Access Client')->exists()) {
            $client->createPersonalAccessClient(
                null,
                'NusaHire Personal Access Client',
                config('app.url')
            );
        }
    }
}
