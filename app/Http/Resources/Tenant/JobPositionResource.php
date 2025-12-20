<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobPositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'id_parent' => $this->id_parent,
            'nusawork_id' => $this->nusawork_id,
            'nusawork_name' => $this->nusawork_name,
            'children'  => self::collection($this->whenLoaded('children')),
            'created_at'=> $this->created_at,
            'updated_at'=> $this->updated_at,
        ];
    }
}
