<?php

namespace App\Services\Tenant;

use App\Models\Tenant\DefaultSigner;
use App\Models\Tenant\User as TenantUser;
use Exception;

class DefaultSignerService
{
    public function getAllGroupedByWorkgroup()
    {
        return \App\Models\Tenant\Workgroup::with(['defaultSigners.user'])
            ->whereHas('defaultSigners')
            ->get();
    }

    public function getAvailableUsers()
    {
        return TenantUser::all();
    }

    public function getWorkgroups()
    {
        return \App\Models\Tenant\Workgroup::where('is_active', true)->get();
    }

    public function store(array $data): DefaultSigner
    {
        $userExists = DefaultSigner::where('workgroup_id', $data['workgroup_id'])
            ->where('user_id', $data['user_id'])
            ->exists();
            
        if ($userExists) {
            throw new Exception('User ini sudah terdaftar sebagai signer di workgroup tersebut.');
        }

        $stepExists = DefaultSigner::where('workgroup_id', $data['workgroup_id'])
            ->where('step_order', $data['step_order'])
            ->exists();
            
        if ($stepExists) {
            throw new Exception('Urutan signing nomor ' . $data['step_order'] . ' sudah digunakan di workgroup ini.');
        }

        return DefaultSigner::create([
            'workgroup_id' => $data['workgroup_id'],
            'user_id' => $data['user_id'],
            'step_order' => $data['step_order'],
            'role' => $data['role'] ?? null,
            'is_active' => true,
        ]);
    }

    public function update(string $id, array $data): DefaultSigner
    {
        $signer = DefaultSigner::where('id', $id)->firstOrFail();
        
        $workgroupId = $data['workgroup_id'] ?? $signer->workgroup_id;

        if (isset($data['user_id']) && $data['user_id'] != $signer->user_id) {
            $userExists = DefaultSigner::where('workgroup_id', $workgroupId)
                ->where('user_id', $data['user_id'])
                ->where('id', '!=', $id)
                ->exists();

            if ($userExists) {
                throw new Exception('User ini sudah terdaftar sebagai signer di workgroup tersebut.');
            }
        }

        if (isset($data['step_order']) && $data['step_order'] != $signer->step_order) {
            $stepExists = DefaultSigner::where('workgroup_id', $workgroupId)
                ->where('step_order', $data['step_order'])
                ->where('id', '!=', $id)
                ->exists();

            if ($stepExists) {
                throw new Exception('Urutan signing nomor ' . $data['step_order'] . ' sudah digunakan di workgroup ini.');
            }
        }

        $signer->update(array_intersect_key($data, array_flip(['workgroup_id', 'user_id', 'step_order', 'role', 'is_active'])));

        return $signer->fresh(['user', 'workgroup']);
    }

    public function delete(string $id): bool
    {
        $signer = DefaultSigner::where('id', $id)->firstOrFail();
        return $signer->delete();
    }

    public function getSignersForWorkgroup(string $workgroupId)
    {
        return DefaultSigner::where('workgroup_id', $workgroupId)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->with('user')
            ->get();
    }
}
