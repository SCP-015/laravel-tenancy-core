<?php

namespace App\Services\Tenant;

use App\Models\Tenant\ExperienceLevel;
use Illuminate\Support\Facades\DB;

class ExperienceLevelService
{
    public function all()
    {
        return ExperienceLevel::orderBy('index', 'asc')->get();
    }

    public function create(array $data)
    {
        $existing = ExperienceLevel::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($existing && $existing->trashed()) {
            $existing->restore();
            if (array_key_exists('index', $data)) {
                $existing->index = $data['index'];
                $existing->save();
            }

            return $existing;
        }

        return ExperienceLevel::create($data);
    }

    public function update(ExperienceLevel $experienceLevel, array $data)
    {
        // Jika ada perubahan index (reorder dari drag), lakukan proper reindexing dalam transaction
        if (isset($data['index']) && $data['index'] !== $experienceLevel->index) {
            return DB::transaction(function () use ($experienceLevel, $data) {
                $newIndex = (int) $data['index'];
                
                // Ambil semua item aktif, urutkan berdasarkan index saat ini
                $allItems = ExperienceLevel::orderBy('index', 'asc')->get();
                
                // Hapus item yang di-drag dari list
                $draggedItem = $allItems->firstWhere('id', $experienceLevel->id);
                $otherItems = $allItems->where('id', '!=', $experienceLevel->id)->values();
                
                // Insert item di posisi baru
                $otherItems->splice($newIndex, 0, [$draggedItem]);
                
                // Reindex semua item secara berurutan (0, 1, 2, 3, ...)
                foreach ($otherItems as $idx => $item) {
                    if ($item->index !== $idx) {
                        $item->index = $idx;
                        $item->save();
                    }
                }
                
                return $experienceLevel->fresh();
            });
        }
        
        // Update biasa tanpa reorder
        $experienceLevel->update($data);
        return $experienceLevel;
    }

    public function delete(ExperienceLevel $experienceLevel)
    {
        return $experienceLevel->delete();
    }

    public function archived()
    {
        return ExperienceLevel::onlyTrashed()->orderBy('index', 'asc')->get();
    }

    public function restore(int $id)
    {
        $experienceLevel = ExperienceLevel::onlyTrashed()->findOrFail($id);
        $experienceLevel->restore();

        return $experienceLevel;
    }

    public function forceDelete(int $id)
    {
        $experienceLevel = ExperienceLevel::onlyTrashed()->findOrFail($id);

        return $experienceLevel->forceDelete();
    }
}
