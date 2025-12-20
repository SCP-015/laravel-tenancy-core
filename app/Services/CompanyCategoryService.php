<?php

namespace App\Services;

use App\Models\CompanyCategory;
use Illuminate\Database\Eloquent\Collection;

class CompanyCategoryService
{
    public function getAll(): Collection
    {
        return CompanyCategory::orderBy('id')->get();
    }

    public function create(array $validatedData): CompanyCategory
    {
        // Logika pembuatan slug sudah ditangani oleh model event
        return CompanyCategory::create($validatedData);
    }

    public function update(CompanyCategory $category, array $validatedData): CompanyCategory
    {
        $category->update($validatedData);

        return $category;
    }

    /**
     * Delete a company category
     * 
     * @codeCoverageIgnore - Method tidak digunakan karena route destroy di-exclude (api.php line 95)
     */
    public function delete(CompanyCategory $category): ?bool
    {
        return $category->delete();
    }
}
