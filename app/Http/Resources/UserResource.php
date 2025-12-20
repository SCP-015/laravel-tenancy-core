<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "global_id" => $this->global_id,
            "name" => $this->name,
            "email" => $this->email,
            "email_verified_at" => $this->email_verified_at,
            "has_portal" => $this->tenants->isNotEmpty(),
            "tenants_count" => $this->tenants->count(),
            "available_tenants" => $this->when($this->tenants->isNotEmpty(), function () {
                return $this->tenants->map(function ($tenant) {
                    // Cari tenantUser dari collection yang sudah di-load untuk menghindari N+1 query
                    $tenantUser = $this->tenantUsers->firstWhere('tenant_id', $tenant->id);

                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'code' => $tenant->code,
                        'slug' => $tenant->slug,
                        'role' => $tenantUser?->role ?? 'admin',
                        'avatar' => $tenantUser?->avatar,
                        'is_nusawork_integrated' => $tenantUser?->is_nusawork_integrated ?? false,
                        'tenant_join_date' => $tenantUser?->tenant_join_date,
                        'last_login_at' => $tenantUser?->last_login_at,
                    ];
                })->values();
            }),
        ];
    }
}
