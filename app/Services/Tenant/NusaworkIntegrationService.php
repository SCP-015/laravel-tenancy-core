<?php

namespace App\Services\Tenant;

use App\Models\Tenant\EducationLevel;
use App\Models\Tenant\JobLevel;
use App\Models\Tenant\JobPosition;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\SsoTokenService;

class NusaworkIntegrationService
{
    use Loggable;
    
    protected string $domainUrl;
    protected string $apiToken;

    public function __construct($domainUrl, $apiToken = null)
    {
        $this->domainUrl = $domainUrl;
        $this->apiToken = $apiToken;
    }

    public function fetchMasterData(): ?array
    {
        $this->logInfo('Fetching master data from Nusawork...');

        // Panggilan API ke Nusawork (saat ini tidak pakai token)
        $response = Http::get($this->domainUrl . config('services.nusawork.master_data_path'), [
            'company_structure' => 1,
            'show_education' => 1,
        ]);

        if ($response->failed()) {
            $this->logError('Failed to fetch data from Nusawork.', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Gagal mengambil data dari Nusawork.');
        }

        $this->logInfo('Successfully fetched data from Nusawork.');
        return $response->json('data'); // Mengambil array di dalam key 'data'
    }

    public function syncMasterData(): void
    {
        $masterData = $this->fetchMasterData();

        if (!$masterData) {
            $this->logWarning('No master data found to sync.');
            return;
        }

        JobLevel::truncate();
        EducationLevel::truncate();
        JobPosition::truncate();

        // Gunakan transaksi agar semua data berhasil disimpan atau tidak sama sekali
        DB::transaction(function () use ($masterData) {
            $this->logInfo('Starting master data synchronization.');

            if (isset($masterData['job_level'])) {
                $this->syncJobLevels($masterData['job_level']);
            }
            if (isset($masterData['education'])) {
                $this->syncEducationLevels($masterData['education']);
            }
            if (isset($masterData['job_position'])) {
                // Job Position disinkronkan dalam 2 tahap karena ada relasi parent-child
                $this->syncJobPositionsPass1($masterData['job_position']);
                $this->syncJobPositionsPass2($masterData['job_position']);
            }

            $this->logInfo('Master data synchronization completed.');
        });
    }

    private function syncJobLevels(array $levels): void
    {
        foreach ($levels as $level) {
            JobLevel::updateOrCreate(
                ['nusawork_id' => $level['id']], // Cari berdasarkan ID Nusawork
                [
                    'nusawork_name' => $level['name'],
                    'name' => $level['name'],
                    'index' => $level['position'],
                ]
            );
        }
        $this->logInfo(count($levels) . ' job levels synced.');
    }

    private function syncEducationLevels(array $educations): void
    {
        foreach ($educations as $education) {
            EducationLevel::updateOrCreate(
                ['nusawork_id' => $education['id']],
                [
                    'nusawork_name' => $education['value'],
                    'name' => $education['value'],
                    'index' => $education['order'],
                ]
            );
        }
        $this->logInfo(count($educations) . ' education levels synced.');
    }

    private function syncJobPositionsPass1(array $positions): void
    {
        foreach ($positions as $position) {
            JobPosition::updateOrCreate(
                ['nusawork_id' => $position['id']],
                [
                    'nusawork_name' => $position['name'],
                    'name' => $position['name']
                ]
            );
        }
    }

    private function syncJobPositionsPass2(array $positions): void
    {
        foreach ($positions as $position) {
            if (!empty($position['id_parent'])) {
                $child = JobPosition::where('nusawork_id', $position['id'])->first();
                $parent = JobPosition::where('nusawork_id', $position['id_parent'])->first();

                if ($child && $parent) {
                    $child->id_parent = $parent->id; // Gunakan ID lokal, bukan ID Nusawork
                    $child->save();
                }
            }
        }
        $this->logInfo(count($positions) . ' job positions synced.');
    }

    /**
     * Register Nusawork integration for tenant user.
     * 
     * @codeCoverageIgnore
     * Excluded from coverage: Requires external Nusawork API integration
     * This method should be tested via integration tests with proper API setup
     */
    public function registerNusaworkIntegration($tenantUserCentral)
    {
        $domainUrl = $this->domainUrl;
        $apiToken = $this->apiToken;

        if (!$domainUrl || !$apiToken) {
            return;
        }

        $userIdNusaork = $tenantUserCentral->getUserIdNusawork();
        $defaultIss = SsoTokenService::getIssuer();

        // 2 adalah id integrasi Nusawork
        $response = Http::withToken($apiToken)->put($domainUrl . '/api/integrations/2', [
            'url' => $defaultIss,
            'user_id' => $userIdNusaork,
            'tenant_id' => $tenantUserCentral->tenant_id,
            'global_id' => $tenantUserCentral->global_user_id,
            'token' => $apiToken,
        ]);

        if ($response->successful()) {
            $tenantUserCentral->update([
                'is_nusawork_integrated' => true,
                'nusawork_integrated_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
