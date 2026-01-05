<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefaultSignerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workgroup_id' => $this->workgroup_id,
            'user_id' => $this->user_id,
            'step_order' => $this->step_order,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'user' => new UserResource($this->whenLoaded('user')),
            'workgroup' => $this->whenLoaded('workgroup'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
