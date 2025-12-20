<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\EducationLevel;
use App\Models\Tenant\JobLevel;
use App\Models\Tenant\JobPosition;
use App\Services\Tenant\NusaworkIntegrationService;
use Illuminate\Support\Facades\Http;
use Tests\Feature\TenantTestCase;

/**
 * ======================================================================
 * Catatan untuk Menjalankan Tes Ini Secara Spesifik:
 * ======================================================================
 *
 * Untuk menjalankan hanya file tes ini dan bukan seluruh test suite,
 * Anda bisa menggunakan perintah artisan berikut di terminal Anda:
 *
 * php artisan test tests/Feature/Tenant/NusaworkIntegrationTest.php
 *
 * Ini sangat berguna untuk fokus pada pengujian fitur spesifik ini
 * tanpa harus menunggu semua tes lain selesai.
 * ======================================================================
 */
class NusaworkIntegrationTest extends TenantTestCase
{
    /**
     * Tes utama untuk memastikan sinkronisasi data master dari Nusawork berjalan sukses.
     *
     * @return void
     */
    public function test_can_sync_master_data_from_nusawork_api(): void
    {
        // ------------------
        // ARRANGE (PERSIAPAN)
        // ------------------

        // 1. Kosongkan tabel master data yang akan disinkronkan.
        //    Ini memastikan kita menguji proses sync pada database yang bersih.
        JobPosition::truncate();
        JobLevel::truncate();
        EducationLevel::truncate();

        // 2. Siapkan data dummy dari API Nusawork
        $dummyApiResponse = [
            "data" => [
                "job_position" => [
                    ["id" => 1, "id_parent" => 0, "name" => "Direktur Utama"],
                    ["id" => 2, "id_parent" => 1, "name" => "Manajer HRD"],
                    ["id" => 3, "id_parent" => 1, "name" => "Manajer Keuangan"],
                    ["id" => 4, "id_parent" => 2, "name" => "Staff HRD"],
                ],
                "job_level" => [
                    // Gunakan nama yang berbeda dari data seeder default
                    ["id" => 10, "name" => "Level Direksi", "position" => 1],
                    ["id" => 11, "name" => "Level Manajer", "position" => 2],
                    ["id" => 12, "name" => "Level Staff", "position" => 3],
                ],
                "education" => [
                    ["id" => 100, "value" => "SMA/SMK", "order" => 1],
                    ["id" => 101, "value" => "Sarjana (S1)", "order" => 2],
                ]
            ]
        ];

        // 3. Mocking HTTP Request ke Nusawork
        // Ini adalah langkah krusial. Kita tidak benar-benar memanggil API luar saat testing.
        // Sebaliknya, kita memberitahu Laravel untuk "berpura-pura" memanggil API
        // dan langsung mengembalikan data dummy yang sudah kita siapkan.
        Http::fake([
            'nusanet22.app.dev.nusa.work/*' => Http::response($dummyApiResponse, 200),
        ]);

        // ------------------
        // ACT (AKSI)
        // ------------------

        // Panggil metode syncMasterData dari service
        $domainUrl = 'https://nusanet22.app.dev.nusa.work';
        $apiToken = 'dummy-token-for-testing';
        $integrationService = new NusaworkIntegrationService($domainUrl, $apiToken);
        $integrationService->syncMasterData();


        // ------------------
        // ASSERT (PEMERIKSAAN)
        // ------------------

        // 1. Periksa tabel education_levels
        $this->assertDatabaseCount('education_levels', 2);
        $this->assertDatabaseHas('education_levels', [
            'nusawork_id' => 101,
            'name' => 'Sarjana (S1)',
            'index' => 2,
        ]);

        // 2. Periksa tabel job_levels
        $this->assertDatabaseCount('job_levels', 3);
        $this->assertDatabaseHas('job_levels', [
            'nusawork_id' => 11,
            'name' => 'Level Manajer',
            'index' => 2,
        ]);

        // 3. Periksa tabel job_positions (termasuk relasi parent-child)
        $this->assertDatabaseCount('job_positions', 4);
        $this->assertDatabaseHas('job_positions', [
            'nusawork_id' => 2,
            'name' => 'Manajer HRD',
        ]);
        $this->assertDatabaseHas('job_positions', [
            'nusawork_id' => 4,
            'name' => 'Staff HRD',
        ]);

        // Verifikasi relasi parent-child
        $parent = JobPosition::where('nusawork_id', 2)->first(); // Manajer HRD
        $child = JobPosition::where('nusawork_id', 4)->first(); // Staff HRD

        $this->assertNotNull($parent);
        $this->assertNotNull($child);
        $this->assertEquals($parent->id, $child->id_parent); // Pastikan id_parent diisi dengan ID LOKAL
    }

    /**
     * Test: syncMasterData returns early when no master data found
     * Coverage: Line 55-57
     */
    public function test_sync_master_data_returns_early_when_no_master_data(): void
    {
        // ARRANGE
        JobPosition::truncate();
        JobLevel::truncate();
        EducationLevel::truncate();

        // Mock API response dengan data kosong
        $emptyApiResponse = [
            "data" => null
        ];

        Http::fake([
            'nusanet22.app.dev.nusa.work/*' => Http::response($emptyApiResponse, 200),
        ]);

        // ACT
        $domainUrl = 'https://nusanet22.app.dev.nusa.work';
        $apiToken = 'dummy-token-for-testing';
        $integrationService = new NusaworkIntegrationService($domainUrl, $apiToken);
        $integrationService->syncMasterData();

        // ASSERT - Database should remain empty
        $this->assertDatabaseCount('education_levels', 0);
        $this->assertDatabaseCount('job_levels', 0);
        $this->assertDatabaseCount('job_positions', 0);
    }

    /**
     * Test: syncMasterData handles empty array response
     * Coverage: Line 55-57 (alternative scenario)
     */
    public function test_sync_master_data_handles_empty_array_response(): void
    {
        // ARRANGE
        JobPosition::truncate();
        JobLevel::truncate();
        EducationLevel::truncate();

        // Mock API response dengan empty array
        $emptyApiResponse = [
            "data" => []
        ];

        Http::fake([
            'nusanet22.app.dev.nusa.work/*' => Http::response($emptyApiResponse, 200),
        ]);

        // ACT
        $domainUrl = 'https://nusanet22.app.dev.nusa.work';
        $apiToken = 'dummy-token-for-testing';
        $integrationService = new NusaworkIntegrationService($domainUrl, $apiToken);
        $integrationService->syncMasterData();

        // ASSERT - Database should remain empty
        $this->assertDatabaseCount('education_levels', 0);
        $this->assertDatabaseCount('job_levels', 0);
        $this->assertDatabaseCount('job_positions', 0);
    }

    /**
     * Test: fetchMasterData throws exception on API failure
     * Coverage: Line 39-44 (API error handling)
     */
    public function test_fetch_master_data_throws_exception_on_api_failure(): void
    {
        // ARRANGE
        Http::fake([
            'nusanet22.app.dev.nusa.work/*' => Http::response([], 500),
        ]);

        // ACT & ASSERT
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Gagal mengambil data dari Nusawork.');

        $domainUrl = 'https://nusanet22.app.dev.nusa.work';
        $apiToken = 'dummy-token-for-testing';
        $integrationService = new NusaworkIntegrationService($domainUrl, $apiToken);
        $integrationService->fetchMasterData();
    }

    /**
     * Test: syncMasterData with partial data (only job_level)
     * Coverage: Line 68-70 (conditional sync)
     */
    public function test_sync_master_data_with_only_job_level(): void
    {
        // ARRANGE
        JobPosition::truncate();
        JobLevel::truncate();
        EducationLevel::truncate();

        $partialApiResponse = [
            "data" => [
                "job_level" => [
                    ["id" => 10, "name" => "Level Direksi", "position" => 1],
                    ["id" => 11, "name" => "Level Manajer", "position" => 2],
                ],
            ]
        ];

        Http::fake([
            'nusanet22.app.dev.nusa.work/*' => Http::response($partialApiResponse, 200),
        ]);

        // ACT
        $domainUrl = 'https://nusanet22.app.dev.nusa.work';
        $apiToken = 'dummy-token-for-testing';
        $integrationService = new NusaworkIntegrationService($domainUrl, $apiToken);
        $integrationService->syncMasterData();

        // ASSERT
        $this->assertDatabaseCount('job_levels', 2);
        $this->assertDatabaseCount('education_levels', 0);
        $this->assertDatabaseCount('job_positions', 0);
    }

    /**
     * Test: syncMasterData with partial data (only education)
     * Coverage: Line 71-72 (conditional sync)
     */
    public function test_sync_master_data_with_only_education(): void
    {
        // ARRANGE
        JobPosition::truncate();
        JobLevel::truncate();
        EducationLevel::truncate();

        $partialApiResponse = [
            "data" => [
                "education" => [
                    ["id" => 100, "value" => "SMA/SMK", "order" => 1],
                    ["id" => 101, "value" => "Sarjana (S1)", "order" => 2],
                ]
            ]
        ];

        Http::fake([
            'nusanet22.app.dev.nusa.work/*' => Http::response($partialApiResponse, 200),
        ]);

        // ACT
        $domainUrl = 'https://nusanet22.app.dev.nusa.work';
        $apiToken = 'dummy-token-for-testing';
        $integrationService = new NusaworkIntegrationService($domainUrl, $apiToken);
        $integrationService->syncMasterData();

        // ASSERT
        $this->assertDatabaseCount('education_levels', 2);
        $this->assertDatabaseCount('job_levels', 0);
        $this->assertDatabaseCount('job_positions', 0);
    }

    /**
     * Test: syncMasterData with partial data (only job_position)
     * Coverage: Line 74-77 (conditional sync)
     */
    public function test_sync_master_data_with_only_job_position(): void
    {
        // ARRANGE
        JobPosition::truncate();
        JobLevel::truncate();
        EducationLevel::truncate();

        $partialApiResponse = [
            "data" => [
                "job_position" => [
                    ["id" => 1, "id_parent" => 0, "name" => "Direktur Utama"],
                    ["id" => 2, "id_parent" => 1, "name" => "Manajer HRD"],
                ]
            ]
        ];

        Http::fake([
            'nusanet22.app.dev.nusa.work/*' => Http::response($partialApiResponse, 200),
        ]);

        // ACT
        $domainUrl = 'https://nusanet22.app.dev.nusa.work';
        $apiToken = 'dummy-token-for-testing';
        $integrationService = new NusaworkIntegrationService($domainUrl, $apiToken);
        $integrationService->syncMasterData();

        // ASSERT
        $this->assertDatabaseCount('job_positions', 2);
        $this->assertDatabaseCount('job_levels', 0);
        $this->assertDatabaseCount('education_levels', 0);
    }
}
