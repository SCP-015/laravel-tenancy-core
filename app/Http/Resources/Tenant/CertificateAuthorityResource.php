<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateAuthorityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'common_name' => $this->common_name,
            'valid_from' => $this->valid_from,
            'valid_to' => $this->valid_to,
            'created_at' => $this->created_at,
        ];
    }
}
