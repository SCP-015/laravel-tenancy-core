<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkgroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workgroup' => $this->name, // Frontend expects 'workgroup' key for the name
            'description' => $this->description,
            'is_active' => $this->is_active,
            'signers' => DefaultSignerResource::collection($this->whenLoaded('defaultSigners'))->resolve(),
        ];
    }

}
