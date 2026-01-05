<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user ? $this->user->name : 'Unknown User',
            'label' => $this->label,
            'common_name' => $this->common_name,
            'email' => $this->email,
            'serial_number' => $this->serial_number,
            'valid_from' => $this->valid_from,
            'valid_to' => $this->valid_to,
            'is_revoked' => $this->is_revoked,
            'is_active' => $this->is_active,
            'status' => $this->getStatus(),
        ];
    }
}
