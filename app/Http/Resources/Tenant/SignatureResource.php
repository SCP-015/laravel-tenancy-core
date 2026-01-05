<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'signing_session_id' => $this->signing_session_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user ? $this->user->name : 'Unknown',
            'document_id' => $this->document_id,
            'certificate_id' => $this->certificate_id,
            'role' => $this->role,
            'step_order' => $this->step_order,
            'is_required' => $this->is_required,
            'status' => $this->status,
            'signed_at' => $this->signed_at,
            'ip_address' => $this->ip_address,
            'session_status' => $this->signingSession ? $this->signingSession->status : null,
            'document' => $this->relationLoaded('document') ? (new DocumentResource($this->document))->resolve() : null,
            'signing_session' => $this->relationLoaded('signingSession') ? (new SigningSessionResource($this->signingSession))->resolve() : null,
        ];
    }


}
