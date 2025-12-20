<?php

namespace App\Services\Tenant;

use App\Models\Tenant\JobLevel;
use Illuminate\Support\Facades\DB;

class JobLevelService
{
    public function all()
    {
        return JobLevel::orderBy('index', 'asc')->get();
    }

    public function create(array $data)
    {
        // Cek jika record dengan nama sama sudah soft-deleted
        $existing = JobLevel::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($existing && $existing->trashed()) {
            // Restore dan update index kalau dikirim
            $existing->restore();
            if (array_key_exists('index', $data)) {
                $existing->index = $data['index'];
                $existing->save();
            }

            return $existing;
        }

        // Jika belum ada atau bukan soft-deleted
        return JobLevel::create($data);
    }

    public function update(JobLevel $jobLevel, array $data)
    {
        // Jika ada perubahan index (reorder dari drag), lakukan proper reindexing dalam transaction
        if (isset($data['index']) && $data['index'] !== $jobLevel->index) {
            return DB::transaction(function () use ($jobLevel, $data) {
                $newIndex = (int) $data['index'];
                
                // Ambil semua item aktif, urutkan berdasarkan index saat ini
                $allItems = JobLevel::orderBy('index', 'asc')->get();
                
                // Hapus item yang di-drag dari list
                $draggedItem = $allItems->firstWhere('id', $jobLevel->id);
                $otherItems = $allItems->where('id', '!=', $jobLevel->id)->values();
                
                // Insert item di posisi baru
                $otherItems->splice($newIndex, 0, [$draggedItem]);
                
                // Reindex semua item secara berurutan (0, 1, 2, 3, ...)
                foreach ($otherItems as $idx => $item) {
                    if ($item->index !== $idx) {
                        $item->index = $idx;
                        $item->save();
                    }
                }
                
                return $jobLevel->fresh();
            });
        }
        
        // Update biasa tanpa reorder
        $jobLevel->update($data);
        return $jobLevel;
    }

    public function delete(JobLevel $jobLevel)
    {
        return $jobLevel->delete();
    }

    public function archived()
    {
        return JobLevel::onlyTrashed()->orderBy('index', 'asc')->get();
    }

    public function restore(int $id)
    {
        $jobLevel = JobLevel::onlyTrashed()->findOrFail($id);
        $jobLevel->restore();

        return $jobLevel;
    }

    public function forceDelete(int $id)
    {
        $jobLevel = JobLevel::onlyTrashed()->findOrFail($id);

        return $jobLevel->forceDelete();
    }
}
