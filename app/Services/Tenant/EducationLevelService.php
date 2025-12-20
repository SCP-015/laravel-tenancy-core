<?php

namespace App\Services\Tenant;

use App\Models\Tenant\EducationLevel;
use Illuminate\Support\Facades\DB;

class EducationLevelService
{
    public function all()
    {
        return EducationLevel::orderBy('index', 'asc')->get();
    }

    public function create(array $data)
    {
        $existing = EducationLevel::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($existing && $existing->trashed()) {
            $existing->restore();
            if (isset($data['index'])) {
                $existing->index = $data['index'];
                $existing->save();
            }

            return $existing;
        }

        return EducationLevel::create($data);
    }

    public function update(EducationLevel $educationLevel, array $data)
    {
        // Jika ada perubahan index (reorder dari drag), lakukan proper reindexing dalam transaction
        if (isset($data['index']) && $data['index'] !== $educationLevel->index) {
            return DB::transaction(function () use ($educationLevel, $data) {
                $newIndex = (int) $data['index'];
                
                // Ambil semua item aktif, urutkan berdasarkan index saat ini
                $allItems = EducationLevel::orderBy('index', 'asc')->get();
                
                // Hapus item yang di-drag dari list
                $draggedItem = $allItems->firstWhere('id', $educationLevel->id);
                $otherItems = $allItems->where('id', '!=', $educationLevel->id)->values();
                
                // Insert item di posisi baru
                $otherItems->splice($newIndex, 0, [$draggedItem]);
                
                // Reindex semua item secara berurutan (0, 1, 2, 3, ...)
                foreach ($otherItems as $idx => $item) {
                    if ($item->index !== $idx) {
                        $item->index = $idx;
                        $item->save();
                    }
                }
                
                return $educationLevel->fresh();
            });
        }
        
        // Update biasa tanpa reorder
        $educationLevel->update($data);
        return $educationLevel;
    }

    public function delete(EducationLevel $educationLevel)
    {
        return $educationLevel->delete();
    }

    public function archived()
    {
        return EducationLevel::onlyTrashed()->orderBy('index', 'asc')->get();
    }

    public function restore(int $id)
    {
        $educationLevel = EducationLevel::onlyTrashed()->findOrFail($id);
        $educationLevel->restore();

        return $educationLevel;
    }

    public function forceDelete(int $id)
    {
        $educationLevel = EducationLevel::onlyTrashed()->findOrFail($id);

        return $educationLevel->forceDelete();
    }
}
