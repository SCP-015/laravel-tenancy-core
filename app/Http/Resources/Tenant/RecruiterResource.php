<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class RecruiterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Ambil tenant ID dari context saat ini
        $currentTenant = tenant();
        $currentTenantId = $currentTenant ? $currentTenant->id : null;

        // Ambil tenant user data yang sesuai dengan tenant saat ini
        $tenantUser = $this->tenantUsers->where('tenant_id', $currentTenantId)->first();

        return [
            'id' => $this->id,
            'global_id' => $this->global_id,
            'tenant_id' => $tenantUser->tenant_id ?? $currentTenantId,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'avatar' => $tenantUser->avatar ?? null,
            'role' => $tenantUser ? Str::title($tenantUser->role) : 'Admin',
            'join_date' => $tenantUser->tenant_join_date ?? $this->created_at,
            'last_login_ip' => $this->last_login_ip,
            'last_login_at' => $this->last_login_at,
        ];
    }

    public function with($request)
    {
        return [
            'company_code' => $this->super_admin ? $this->company_code ?? null : null,
            'is_super_admin' => $this->is_super_admin ?? false,
        ];
    }
}
