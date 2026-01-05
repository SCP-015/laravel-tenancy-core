<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Document;
use App\Models\Tenant\Signature;
use Illuminate\Support\Facades\Storage;

class DocumentVerificationService
{
    public function verifyUploadedFile($file): array
    {
        $hash = hash_file('sha256', $file->getRealPath());
        
        $doc = Document::where('current_hash', $hash)->first();
        
        $isOriginalFile = false;
        if (!$doc) {
            $originalDoc = Document::where('original_hash', $hash)->first();
            if ($originalDoc) {
                $isOriginalFile = true;
                $doc = $originalDoc;
            }
        }
        
        $logEntries = collect([]);
        if ($doc && !$isOriginalFile) {
            $logEntries = Signature::where('document_id', $doc->id)
                ->where('status', 'signed')
                ->with(['user', 'userCertificate'])
                ->get()
                ->filter(function($sig) {
                    return $sig->user && $sig->userCertificate;
                })
                ->map(function($sig) {
                    $cert = $sig->userCertificate;
                    $certStatus = $cert->getStatus();
                    
                    return [
                        'signer' => $sig->user->name,
                        'signed_at' => $sig->signed_at,
                        'serial' => $cert->serial_number,
                        'ip' => $sig->ip_address,
                        'cert_valid_from' => $cert->valid_from->toDateTimeString(),
                        'cert_valid_to' => $cert->valid_to->toDateTimeString(),
                        'cert_status' => $certStatus,
                    ];
                });
        }
        
        $isValid = $doc && !$isOriginalFile && $logEntries->isNotEmpty();
        $isOrphanedSigned = $doc && $doc->status === 'signed' && $logEntries->isEmpty();
        
        if ($isValid) {
            return [
                'success' => true,
                'message' => 'Document Verified ✓',
                'description' => 'This document has valid digital signatures and is authenticated.',
                'document' => [
                    'title' => $doc->title,
                    'filename' => $doc->filename,
                    'current_hash' => $doc->current_hash,
                    'status' => $doc->status,
                ],
                'signatures' => $logEntries
            ];
        }
        
        if ($isOrphanedSigned) {
            return [
                'success' => false,
                'message' => 'Document Status Inconsistent',
                'description' => 'This document appears to have been signed (has QR code), but signature records are missing from the database. This may occur if signature data was deleted. Please contact administrator.',
                'filename' => $file->getClientOriginalName(),
                'document' => [
                    'title' => $doc->title,
                    'filename' => $doc->filename,
                    'current_hash' => $doc->current_hash,
                    'status' => $doc->status,
                ],
                'signatures' => []
            ];
        }
        
        if ($isOriginalFile) {
            return [
                'success' => false,
                'message' => 'Original Unsigned Document',
                'description' => 'This is the original document before signing. It does not contain any QR code or digital signatures. Please upload the SIGNED version to verify authenticity.',
                'filename' => $file->getClientOriginalName(),
                'document' => null,
                'signatures' => []
            ];
        }
        
        if (!$doc) {
            $invalidMessage = 'Document Not Found';
            $invalidDescription = 'This file is not registered in our system. It may have never been uploaded for signing.';
        } else {
            $invalidMessage = 'Document Invalid or Not Signed';
            $invalidDescription = 'This document has been uploaded but does not have any valid digital signatures.';
        }
        
        return [
            'success' => false,
            'message' => $invalidMessage,
            'description' => $invalidDescription,
            'filename' => $file->getClientOriginalName(),
            'document' => $doc ? [
                'title' => $doc->title,
                'filename' => $doc->filename,
                'current_hash' => $doc->current_hash,
                'status' => $doc->status,
            ] : null,
            'signatures' => []
        ];
    }
    
    public function scanQR(string $qrData): array
    {
        $data = json_decode($qrData, true);
        
        if (!$data || !isset($data['document']) || !isset($data['signers'])) {
            return [
                'success' => false,
                'message' => 'Invalid QR Code',
                'description' => 'The QR code data is invalid or corrupted.',
            ];
        }
        
        return [
            'success' => true,
            'message' => 'QR Code Verified',
            'description' => 'This QR code contains valid document signature information.',
            'data' => $data,
        ];
    }
}
