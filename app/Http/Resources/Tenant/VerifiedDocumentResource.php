<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerifiedDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'document' => [
                'id' => $this->id,
                'title' => $this->title,
                'filename' => $this->filename,
                'status' => $this->status,
                'original_hash' => $this->original_hash,
                'current_hash' => $this->current_hash,
            ],
            'signatures' => $this->signatures->map(function($sig) {
                $cert = $sig->userCertificate;
                return [
                    'signer' => $sig->user ? $sig->user->name : 'Unknown',
                    'signed_at' => $sig->signed_at,
                    'status' => $sig->status,
                    'certificate_serial' => $cert ? $cert->serial_number : null,
                    'certificate_valid_from' => $cert ? $cert->valid_from : null,
                    'certificate_valid_to' => $cert ? $cert->valid_to : null,
                ];
            }),
        ];
    }
}
