<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;

class RecruiterService
{
    public function getRecruiters(Tenant $tenant, array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;
        $search = $filters['search'] ?? '';

        return $tenant->users()
            ->with(['tenantUsers' => function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            }])
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query) use ($search) {
                $searchTerm = strtolower($search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $searchTerm . '%']);
                });
            })
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
