<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DefaultSigner;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;                                                                                    
use Illuminate\Support\Facades\Log;

class DefaultSignerController extends Controller
{
    /**
     * Get all default signers grouped by workgroup.
     */
    public function index()
    {
        $signers = DefaultSigner::with('user')
            ->orderBy('workgroup')
            ->orderBy('step_order')
            ->get()
            ->groupBy('workgroup')
            ->map(function ($group, $workgroup) {
                return [
                    'workgroup' => $workgroup,
                    'signers' => $group->map(function ($signer) {
                        return [
                            'id' => $signer->id,
                            'user_id' => $signer->user_id,
                            'user_name' => $signer->user?->name ?? 'Unknown',
                            'user_email' => $signer->user?->email ?? '',
                            'step_order' => $signer->step_order,
                            'role' => $signer->role,
                            'is_active' => $signer->is_active,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $signers,
        ]);
    }

    /**
     * Get all available users in tenant for dropdown.
     */
    public function getAvailableUsers()
    {
        $users = TenantUser::all()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * Get distinct workgroups.
     */
    public function getWorkgroups()
    {
        $workgroups = DefaultSigner::distinct()->pluck('workgroup')->values();

        return response()->json([
            'status' => 'success',
            'data' => $workgroups,
        ]);
    }

    /**
     * Store a new default signer.
     */
    public function store(Request $request)
    {
        $request->validate([
            'workgroup' => 'required|string|max:100',
            'user_id' => 'required|exists:users,id',
            'step_order' => 'required|integer|min:1',
            'role' => 'nullable|string|max:100',
        ]);

        // Check if signer already exists for this workgroup (by user OR step)
        // 1. Cek User Duplicate di Workgroup
        $userExists = DefaultSigner::where('workgroup', $request->workgroup)
            ->where('user_id', $request->user_id)
            ->exists();
            
        if ($userExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'User ini sudah terdaftar sebagai signer di workgroup tersebut.',
            ], 422);
        }

        // 2. Cek Step Order Conflict
        $stepExists = DefaultSigner::where('workgroup', $request->workgroup)
            ->where('step_order', $request->step_order)
            ->exists();
            
        if ($stepExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Urutan signing nomor ' . $request->step_order . ' sudah digunakan di workgroup ini.',
            ], 422);
        }

        $signer = DefaultSigner::create([
            'workgroup' => $request->workgroup,
            'user_id' => $request->user_id,
            'step_order' => $request->step_order,
            'role' => $request->role,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Default signer berhasil ditambahkan.',
            'data' => $signer->load('user'),
        ], 201);
    }

    /**
     * Update a default signer.
     */
    public function update(Request $request, $tenant, $id)
    {
        // 1. Try direct match
        $signer = DefaultSigner::where('id', (string) $id)->first();
        
        if (!$signer) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'workgroup' => 'sometimes|required|string|max:100',
            'user_id' => 'sometimes|required|exists:users,id',
            'step_order' => 'sometimes|required|integer|min:1',
            'role' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $workgroup = $request->workgroup ?? $signer->workgroup;

        // 1. Check Duplicate User if changed
        if ($request->has('user_id') && $request->user_id != $signer->user_id) {
            $userExists = DefaultSigner::where('workgroup', $workgroup)
                ->where('user_id', $request->user_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($userExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ini sudah terdaftar sebagai signer di workgroup tersebut.',
                ], 422);
            }
        }

        // 2. Check Step Order Conflict if changed
        if ($request->has('step_order') && $request->step_order != $signer->step_order) {
            $stepExists = DefaultSigner::where('workgroup', $workgroup)
                ->where('step_order', $request->step_order)
                ->where('id', '!=', $id)
                ->exists();

            if ($stepExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Urutan signing nomor ' . $request->step_order . ' sudah digunakan di workgroup ini.',
                ], 422);
            }
        }

        $signer->update($request->only(['workgroup', 'user_id', 'step_order', 'role', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Default signer berhasil diperbarui.',
            'data' => $signer->load('user'),
        ]);
    }

    /**
     * Delete a default signer.
     */
    public function destroy($tenant, $id)
    {
        // 1. Try direct match
        $signer = DefaultSigner::where('id', (string) $id)->first();

        if (!$signer) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $signer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Default signer berhasil dihapus.',
        ]);
    }

    /**
     * Get signers for a specific workgroup (used when creating signing session).
     */
    public function getSignersForWorkgroup($tenant, $workgroup)
    {
        $signers = DefaultSigner::getSignersForWorkgroup($workgroup);

        return response()->json([
            'status' => 'success',
            'data' => $signers->map(function ($signer) {
                return [
                    'user_id' => $signer->user_id,
                    'user_name' => $signer->user?->name,
                    'user_email' => $signer->user?->email,
                    'step_order' => $signer->step_order,
                    'role' => $signer->role,
                ];
            }),
        ]);
    }
}
