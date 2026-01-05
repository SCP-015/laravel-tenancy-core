<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DefaultSigner\StoreDefaultSignerRequest;
use App\Http\Requests\Tenant\DefaultSigner\UpdateDefaultSignerRequest;
use App\Http\Resources\Tenant\DefaultSignerResource;
use App\Http\Resources\Tenant\UserResource;
use App\Http\Resources\Tenant\WorkgroupResource;
use App\Services\Tenant\DefaultSignerService;
use Exception;
use Illuminate\Support\Facades\Log;

class DefaultSignerController extends Controller
{
    protected DefaultSignerService $defaultSignerService;

    public function __construct(DefaultSignerService $defaultSignerService)
    {
        $this->defaultSignerService = $defaultSignerService;
    }

    public function index(Request $request, $tenant)
    {
        $workgroups = $this->defaultSignerService->getAllGroupedByWorkgroup();
        return ApiResponse::success(WorkgroupResource::collection($workgroups)->resolve());
    }

    public function getAvailableUsers(Request $request, $tenant)
    {
        $users = $this->defaultSignerService->getAvailableUsers();
        return ApiResponse::success(UserResource::collection($users)->resolve());
    }

    public function getWorkgroups(Request $request, $tenant)
    {
        $workgroups = $this->defaultSignerService->getWorkgroups();
        return ApiResponse::success(WorkgroupResource::collection($workgroups)->resolve());
    }

    public function store(StoreDefaultSignerRequest $request, $tenant)
    {
        try {
            $signer = $this->defaultSignerService->store($request->validated());
            return ApiResponse::success(new DefaultSignerResource($signer->load(['user', 'workgroup'])), 'Default signer berhasil ditambahkan.', 201);
        } catch (Exception $e) {
            Log::error('Store default signer error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateDefaultSignerRequest $request, $tenant, $id)
    {
        try {
            $signer = $this->defaultSignerService->update((string)$id, $request->validated());
            return ApiResponse::success(new DefaultSignerResource($signer), 'Default signer berhasil diperbarui.');
        } catch (Exception $e) {
            Log::error('Update default signer error: ' . $e->getMessage());
            
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }
            
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, $tenant, $id)
    {
        try {
            $this->defaultSignerService->delete((string)$id);
            return ApiResponse::success(null, 'Default signer berhasil dihapus.');
        } catch (Exception $e) {
            Log::error('Delete default signer error: ' . $e->getMessage());
            
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }
            
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getSignersForWorkgroup(Request $request, $tenant, $workgroupId)
    {
        $signers = $this->defaultSignerService->getSignersForWorkgroup((string)$workgroupId);
        return ApiResponse::success(DefaultSignerResource::collection($signers)->resolve());
    }

}
