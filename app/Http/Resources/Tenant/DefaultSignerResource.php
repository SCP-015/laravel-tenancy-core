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
            'user_name' => $this->user ? $this->user->name : 'Unknown User', // Flat key for frontend
            'user_email' => $this->user ? $this->user->email : '-', // Flat key for frontend
            'step_order' => $this->step_order,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'user' => $this->relationLoaded('user') ? (new UserResource($this->user))->resolve() : null,
            'workgroup' => $this->whenLoaded('workgroup'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}
