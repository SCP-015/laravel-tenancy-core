<?php

namespace App\Services\Tenant;

use App\Models\Tenant\JobPosition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JobPositionService
{
    public function all()
    {
        return JobPosition::with('children')->orderBy('id', 'asc')->get();
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $search = isset($filters['search']) ? (string) $filters['search'] : '';
        $source = isset($filters['source']) ? (string) $filters['source'] : null;

        $query = JobPosition::query()->with('children');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nusawork_name', 'like', '%' . $search . '%');
            });
        }

        if ($source === 'manual') {
            $query->whereNull('nusawork_id');
        } elseif ($source === 'nusawork') {
            $query->whereNotNull('nusawork_id');
        }

        return $query
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        // Cek jika record dengan nama sama sudah soft-deleted
        $existing = JobPosition::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($existing && $existing->trashed()) {
            // Restore dan update parent kalau dikirim
            $existing->restore();
            if (isset($data['id_parent'])) {
                $existing->id_parent = $data['id_parent'];
                $existing->save();
            }

            return $existing;
        }

        // Jika belum ada atau bukan soft-deleted
        return JobPosition::create($data);
    }

    public function update(JobPosition $jobPosition, array $data)
    {
        $jobPosition->update($data);

        return $jobPosition;
    }

    public function delete(JobPosition $jobPosition)
    {
        return $jobPosition->delete();
    }

    public function show(JobPosition $jobPosition)
    {
        return $jobPosition->load('children');
    }

    public function archived()
    {
        return JobPosition::onlyTrashed()->orderBy('id', 'asc')->get();
    }

    public function restore(int $id)
    {
        $jobPosition = JobPosition::onlyTrashed()->findOrFail($id);
        $jobPosition->restore();

        return $jobPosition;
    }

    public function forceDelete(int $id)
    {
        $jobPosition = JobPosition::onlyTrashed()->findOrFail($id);
        $jobPosition->forceDelete();

        return true;
    }
}
