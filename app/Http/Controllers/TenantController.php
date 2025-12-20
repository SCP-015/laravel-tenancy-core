<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinTenantRequest;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\TenantRequest;
use App\Http\Requests\UpdateTenantSlugRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Services\TenantJoinService;
use App\Services\TenantService;
use App\Traits\HasPermissionTrait;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use HasPermissionTrait;

    /**
     * Display Tenants list.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenants = $user->tenants;

        return TenantResource::collection($tenants);
    }

    /**
     * Show guest portal
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function guestPortal()
    {
        return response()->json([
            'portal' => TenantResource::make(tenant()),
        ]);
    }

    /**
     * Create a new tenant.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTenantRequest $request)
    {
        // Ambil data dari validasi request
        $input = $request->validated();

        // generate kan code dari belakang
        $input['code'] = Tenant::generateCode();

        // Panggil service untuk membuat portal
        $result = TenantService::store($input);

        // Cek jika statusnya success
        if ('success' === $result['status']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'portal' => TenantResource::make($result['portal']),
            ]);
        }

        // Jika gagal, kembalikan response error
        return response()->json(
            [
                'status' => $result['status'],
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
                'trace' => $result['trace'] ?? null,
            ],
            'warning' == $result['status'] ? 422 : 500
        );
    }

    /**
     * Join a tenant
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function join(JoinTenantRequest $request)
    {
        $input = $request->validated();

        $tenant = Tenant::where('code', $input['code'])->first();
        if (! $tenant) {
            return response()->json([
                'status' => 'error',
                'message' => __('Portal with the given code not found.'),
            ], 404);
        }

        $user = $request->user();
        if ($tenant->users->where('id', $user->id)->first()) {
            return response()->json([
                'status' => 'warning',
                'message' => __('You have already joined this portal.'),
            ], 422);
        }

        // 1. Sync user di tenant database
        TenantJoinService::syncTenantUser($tenant, $user, $request);

        // 2. Attach relation jika belum ada
        TenantJoinService::attachUserToTenant($user, $tenant);

        // 3. Update tenant_user record di central database
        TenantJoinService::updateCentralTenantUser($user, $tenant);

        return response()->json([
            'status' => 'success',
            'message' => __('You have successfully joined the portal.'),
            'portal' => TenantResource::make($tenant),
        ]);
    }

    /**
     * Get a specific tenant.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        return TenantResource::make(Tenant::findOrFail($id));
    }

    /**
     * Get a specific tenant by ID but return as collection for frontend compatibility.
     * 
     * @param  string  $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showById(string $id)
    {
        $user = request()->user();
        $tenant = $user->tenants()->where('tenants.id', $id)->get();

        if ($tenant->isEmpty()) {
            // Jika tenant dengan ID tersebut tidak ditemukan atau user tidak memiliki akses,
            // kembalikan semua tenant yang dimiliki user
            return $this->index(request());
        }

        return TenantResource::collection($tenant);
    }

    /**
     * Update a specific tenant.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(TenantRequest $request, string $id)
    {
        $this->checkIsNotRecruiter();

        $result = TenantService::update($request->validated(), $id);

        // Service sudah handle semua error, tinggal return response sesuai status
        $statusCode = match ($result['status'] ?? null) {
            'success' => 200,
            'warning' => 422,
            'forbidden' => 403,
            default => 500,
        };
        
        return response()->json($result, $statusCode);
    }

    public function updateSlug(UpdateTenantSlugRequest $request, string $id)
    {
        $this->checkIsNotRecruiter();

        $result = TenantService::updateSlug($request->validated()['slug'], $id);

        $statusCode = match ($result['status'] ?? null) {
            'success' => 200,
            'warning' => 422,
            'forbidden' => 403,
            default => 500,
        };

        return response()->json($result, $statusCode);
    }

    /**
     * Delete a specific tenant.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $result = TenantService::destroy($id);

        // Service sudah handle semua error, tinggal return response sesuai status
        $statusCode = $result['status'] === 'success' ? 200 : 500;
        
        return response()->json($result, $statusCode);
    }

    /**
     * Generate a new code for the tenant.
     */
    public function generateCode()
    {
        return response()->json([
            'status' => 'success',
            'message' => __('Code generated successfully'),
            'code' => Tenant::generateCode(),
        ]);
    }

    /**
     * Check if tenant owner is integrated with Nusawork.
     *
     * @param  Request  $request
     * @param  string  $tenantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkNusaworkIntegration(Request $request, string $tenantId)
    {
        // Ambil owner dari tenant
        $ownerTenantUser = TenantUser::where('tenant_id', $tenantId)
            ->where('is_owner', true)
            ->first();

        $isIntegrated = $ownerTenantUser ? (bool) $ownerTenantUser->is_nusawork_integrated : null;
        $integratedAt = $ownerTenantUser ? $ownerTenantUser->nusawork_integrated_at : null;

        return response()->json([
            'status' => 'success',
            'is_nusawork_integrated' => $isIntegrated,
            'nusawork_integrated_at' => $integratedAt,
        ]);
    }
}
