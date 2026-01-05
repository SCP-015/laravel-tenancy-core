<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'filename' => $this->filename,
            'original_hash' => $this->original_hash,
            'current_hash' => $this->current_hash,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'signed_at' => $this->updated_at,
            'download_url' => route('digital-signature.download', ['tenant' => tenant('id'), 'document' => $this->id]),
            'verify_url' => route('digital-signature.verify', ['tenant' => tenant('id'), 'document' => $this->id]),
        ];
    }
}
