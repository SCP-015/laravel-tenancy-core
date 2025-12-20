<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Tenant\NusaworkIntegrationService;
use App\Traits\Loggable;

/**
 * Job: SyncNusaworkMasterData
 * 
 * This job is excluded from code coverage because it:
 * - Runs as background queue job (async processing)
 * - Requires external Nusawork API integration
 * - Involves complex multi-tenant context switching
 * - Handles OAuth token generation and API calls
 * - Better tested through integration/E2E tests
 * 
 * @codeCoverageIgnore
 */
class SyncNusaworkMasterData implements ShouldQueue
{
    use Queueable, Loggable;

    protected User $user;
    protected ?string $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, ?string $tenantId = null)
    {
        $this->user = $user;
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(Container $container): void
    {
        $this->afterCommit();

        try {
            // Load user tenants
            $this->user->load('tenantUsers');

            // Jika tenantId tidak disediakan, sync untuk semua tenant yang memiliki nusawork integration
            if (!$this->tenantId) {
                $this->syncAllTenants($container);
            } else {
                $this->syncSpecificTenant($container, $this->tenantId);
            }
        } catch (\Throwable $e) {
            $this->logError('Failed to sync Nusawork data', [
                'user_id' => $this->user->id,
                'tenant_id' => $this->tenantId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Sync Nusawork data for all tenants that have integration
     */
    private function syncAllTenants(Container $container): void
    {
        $tenantUsers = $this->user->tenantUsers()
            ->whereNotNull('nusawork_id')
            ->get();

        foreach ($tenantUsers as $tenantUser) {
            $this->syncTenantData($container, $tenantUser->tenant_id, $tenantUser);
        }
    }

    /**
     * Sync Nusawork data for specific tenant
     */
    private function syncSpecificTenant(Container $container, string $tenantId): void
    {
        $tenantUser = $this->user->tenantUsers()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('nusawork_id')
            ->first();

        if ($tenantUser) {
            $this->syncTenantData($container, $tenantId, $tenantUser);
        } else {
            $this->logInfo('No Nusawork integration found for tenant', [
                'user_id' => $this->user->id,
                'tenant_id' => $tenantId,
            ]);
        }
    }

    /**
     * Sync data for specific tenant
     */
    private function syncTenantData(Container $container, string $tenantId, $tenantUserCentral): void
    {
        try {
            // Gunakan domain dan token sesuai tenant
            $nusaworkId = $tenantUserCentral->nusawork_id;
            if (empty($nusaworkId)) {
                $this->logWarning('Empty nusawork_id for tenant user', [
                    'user_id' => $this->user->id,
                    'tenant_id' => $tenantId,
                ]);
                return;
            }

            // Gunakan method dari TenantUser untuk mendapatkan domain URL
            $domainUrl = $tenantUserCentral->getDomainUrl();

            if (empty($domainUrl)) {
                $this->logWarning('Empty domain URL for tenant user', [
                    'user_id' => $this->user->id,
                    'tenant_id' => $tenantId,
                    'nusawork_id' => $nusaworkId,
                ]);
                return;
            }

            // Generate API token menggunakan domain URL dari tenant user
            $apiToken = $tenantUserCentral->getTokenApi();

            if (empty($apiToken)) {
                $this->logWarning('Failed to generate API token for tenant', [
                    'user_id' => $this->user->id,
                    'tenant_id' => $tenantId,
                    'domain_url' => $domainUrl,
                ]);
                return;
            }

            $this->logInfo('Starting Nusawork sync for tenant', [
                'user_id' => $this->user->id,
                'tenant_id' => $tenantId,
                'domain_url' => $domainUrl,
            ]);

            // Dapatkan instance tenant
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->logError('Tenant not found', [
                    'tenant_id' => $tenantId
                ]);
                return;
            }

            // Jalankan dalam konteks tenant menggunakan $tenant->run()
            $userId = $this->user->id;
            $tenant->run(function () use ($container, $domainUrl, $apiToken, $tenantId, $userId, $tenantUserCentral) {
                $service = $container->make(NusaworkIntegrationService::class, [
                    'domainUrl' => $domainUrl,
                    'apiToken' => $apiToken,
                ]);
                $service->syncMasterData();

                $this->logInfo('Nusawork sync completed for tenant', [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                ]);

                // Integrasikan user dengan Nusawork, pastikan hanya pemilik tenant
                if (!$tenantUserCentral->is_nusawork_integrated) {
                    $integrationService = $container->make(NusaworkIntegrationService::class, [
                        'domainUrl' => $domainUrl,
                        'apiToken' => $tenantUserCentral->getTokenApi(),
                    ]);
                    $statusIntegration = $integrationService->registerNusaworkIntegration($tenantUserCentral);

                    if ($statusIntegration) {
                        $tenantUser = \App\Models\Tenant\User::where('global_id', $tenantUserCentral->global_user_id)->first();
                        if ($tenantUser) {
                            $tenantUser->update([
                                'is_nusawork_integrated' => true,
                                'nusawork_integrated_at' => now(),
                            ]);
                        }

                        $this->logInfo('Nusawork integration registered for tenant', [
                            'user_id' => $userId,
                            'tenant_id' => $tenantId,
                        ]);
                    } else {
                        $this->logWarning('Failed to register Nusawork integration for tenant', [
                            'user_id' => $userId,
                            'tenant_id' => $tenantId,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->logError('Failed to sync Nusawork data for tenant', [
                'user_id' => $this->user->id,
                'tenant_id' => $tenantId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
