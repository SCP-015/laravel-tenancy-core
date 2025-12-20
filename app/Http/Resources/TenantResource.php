<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_changed_at' => $this->slug_changed_at,
            'enable_slug_history_redirect' => (bool) ($this->enable_slug_history_redirect ?? false),
            'code' => $this->code,
            'plan' => $this->plan ?? 'free',
            'theme_color' => $this->theme_color,
            'header_image' => $this->getFullImageUrl('header_image'),
            'profile_image' => $this->getFullImageUrl('profile_image'),
            'company_values' => $this->company_values,
            'employee_range_start' => $this->employee_range_start,
            'employee_range_end' => $this->employee_range_end,
            'company_category_id' => $this->company_category_id,
            'company_category' => new CompanyCategoryResource($this->companyCategory),
            'linkedin' => $this->linkedin,
            'instagram' => $this->instagram,
            'website' => $this->website,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
