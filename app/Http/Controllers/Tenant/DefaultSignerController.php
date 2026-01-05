<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DefaultSigner\StoreDefaultSignerRequest;
use App\Http\Requests\Tenant\DefaultSigner\UpdateDefaultSignerRequest;
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

    public function index()
    {
        try {
            $signers = $this->defaultSignerService->getAllGroupedByWorkgroup();
            return ApiResponse::success($signers);
        } catch (Exception $e) {
            Log::error('Get default signers error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve signers'], 500);
        }
    }

    public function getAvailableUsers()
    {
        try {
            $users = $this->defaultSignerService->getAvailableUsers();
            return ApiResponse::success($users);
        } catch (Exception $e) {
            Log::error('Get available users error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve users'], 500);
        }
    }

    public function getWorkgroups()
    {
        try {
            $workgroups = $this->defaultSignerService->getWorkgroups();
            return ApiResponse::success($workgroups);
        } catch (Exception $e) {
            Log::error('Get workgroups error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve workgroups'], 500);
        }
    }

    public function store(StoreDefaultSignerRequest $request)
    {
        try {
            $signer = $this->defaultSignerService->store($request->validated());
            return ApiResponse::success($signer->load(['user', 'workgroup']), 'Default signer berhasil ditambahkan.', 201);
        } catch (Exception $e) {
            Log::error('Store default signer error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateDefaultSignerRequest $request, $tenant, $id)
    {
        try {
            $signer = $this->defaultSignerService->update((string)$id, $request->validated());
            return ApiResponse::success($signer, 'Default signer berhasil diperbarui.');
        } catch (Exception $e) {
            Log::error('Update default signer error: ' . $e->getMessage());
            
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }
            
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($tenant, $id)
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

    public function getSignersForWorkgroup($tenant, $workgroupId)
    {
        try {
            $signers = $this->defaultSignerService->getSignersForWorkgroup((int)$workgroupId);
            return ApiResponse::success($signers);
        } catch (Exception $e) {
            Log::error('Get signers for workgroup error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve signers'], 500);
        }
    }
}
