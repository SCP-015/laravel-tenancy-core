<?php

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Storage;

/**
 * Service untuk menangani logika file dan URL generation
 * Menghindari duplikasi kode di Resource classes
 */
class FileService
{
    /**
     * Mendapatkan URL profile photo dengan fallback ke gambar default
     * Jika file tidak ada di storage, return URL gambar default
     *
     * @param string|null $profilePhotoPath
     * @return string|null
     */
    public function getProfilePhotoUrl(?string $profilePhotoPath): ?string
    {
        // Jika tidak ada path, return null
        if (!$profilePhotoPath) {
            return null;
        }

        // Cek apakah file ada di storage
        if (Storage::disk('local')->exists($profilePhotoPath)) {
            // File ada, return URL ke route candidates.files.view
            return route('candidates.files.view', [
                'tenant' => tenant('id'),
                'path' => $profilePhotoPath,
            ]);
        }

        // File tidak ada, return URL gambar default
        return asset('images/default-profile.png');
    }
}
