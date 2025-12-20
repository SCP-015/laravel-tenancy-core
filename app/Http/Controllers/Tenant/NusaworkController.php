<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use App\Services\Tenant\NusaworkIntegrationService;
use App\Traits\HasPermissionTrait;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NusaworkController extends Controller
{
    use HasPermissionTrait;

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Mengambil data master (Job Position, Level, Education) dari Nusawork.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMasterData()
    {
        $this->checkPermission('integrations.nusawork.master-data');

        try {
            // Ambil user yang sedang login untuk mendapatkan domain dan token
            $user = Auth::user();
            
            // Ambil tenant user untuk mendapatkan domain dan token
            $tenantUser = TenantUser::where('global_user_id', $user->global_id)
                ->where('tenant_id', tenant('id'))
                ->first();
                
            if (!$tenantUser || !$tenantUser->nusawork_id) {
                throw new \Exception(__('Nusawork domain information not found in your account.'));
            }

            $integrationService = $this->container->make(NusaworkIntegrationService::class, [
                'domainUrl' => $tenantUser->getDomainUrl(),
                'apiToken' => $tenantUser->getTokenApi(),
            ]);

            // Panggil metode untuk mengambil data
            $masterData = $integrationService->fetchMasterData();

            // Kembalikan data sebagai respons sukses
            return ApiResponse::success($masterData, __('Master data from Nusawork successfully retrieved.'));
        } catch (\Exception $e) {
            // Tangkap jika ada error (misal: API Nusawork down)
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
