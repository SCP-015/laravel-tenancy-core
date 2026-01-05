<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SigningSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'title' => $this->title,
            'mode' => $this->mode,
            'status' => $this->status,
            'current_step_order' => $this->current_step_order,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'document' => new DocumentResource($this->whenLoaded('document')),
            'signatures' => SignatureResource::collection($this->whenLoaded('signatures')),
        ];
    }
}
