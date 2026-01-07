<?php

namespace Database\Seeders;

use App\Models\CentralRootCA;
use App\Services\PKIService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CentralRootCASeeder extends Seeder
{
    public function run(): void
    {
        // Skip jika sudah ada Central Root CA aktif
        if (CentralRootCA::getActive()) {
            $this->command->info('Central Root CA already exists. Skipping...');
            return;
        }

        $pkiService = new PKIService();

        $caData = $pkiService->createRootCA(
            'Nusawork Root CA',
            'Nusawork',
            'ID',
            'Jakarta',
            'Jakarta',
            'ca@nusawork.com',
            3650 // 10 tahun
        );

        // Simpan ke storage central
        $certPath = 'central/ca/nusawork-root-ca.crt';
        $keyPath = 'central/ca/nusawork-root-ca.key';

        // Ensure directory exists
        Storage::disk('public')->makeDirectory('central/ca', 0755, true);
        
        Storage::disk('public')->put($certPath, $caData['certificate']);
        Storage::disk('public')->put($keyPath, $caData['private_key']);

        CentralRootCA::create([
            'name' => 'Nusawork Root CA',
            'common_name' => 'Nusawork Root CA',
            'serial_number' => $caData['serial_number'],
            'certificate_path' => $certPath,
            'private_key_path' => $keyPath,
            'organization' => 'Nusawork',
            'organization_unit' => 'Digital Signature Root CA',
            'country' => 'ID',
            'state' => 'Jakarta',
            'city' => 'Jakarta',
            'email' => 'ca@nusawork.com',
            'valid_from' => $caData['valid_from'],
            'valid_to' => $caData['valid_to'],
            'is_active' => true,
        ]);

        $this->command->info('Central Root CA created successfully!');
    }
}
