<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Document;
use App\Models\Tenant\Signature;
use App\Models\Tenant\SigningSession;
use App\Models\Tenant\UserCertificate;
use App\Models\Tenant\User as TenantUser;
use App\Services\PKIService;
use App\Services\PDFService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentSigningService
{
    protected PKIService $pkiService;
    protected PDFService $pdfService;

    public function __construct(PKIService $pkiService, PDFService $pdfService)
    {
        $this->pkiService = $pkiService;
        $this->pdfService = $pdfService;
    }

    public function createSession(array $data, $file, TenantUser $currentUser): SigningSession
    {
        return DB::transaction(function() use ($data, $file, $currentUser) {
            $tenantId = tenant('id');
            $originalPath = $file->store("tenants/{$tenantId}/documents", 'public');
            $originalHash = hash_file('sha256', Storage::disk('public')->path($originalPath));
            $metadata = [
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];

            $doc = Document::create([
                'user_id' => $currentUser->id,
                'title' => $data['title'],
                'filename' => $file->getClientOriginalName(),
                'original_file_path' => $originalPath,
                'original_hash' => $originalHash,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

            $session = SigningSession::create([
                'document_id' => $doc->id,
                'title' => $data['title'],
                'mode' => $data['mode'],
                'status' => 'in_progress',
                'created_by' => $currentUser->id,
                'current_step_order' => 1,
            ]);

            foreach ($data['signers'] as $signer) {
                Signature::create([
                    'signing_session_id' => $session->id,
                    'user_id' => $signer['user_id'],
                    'document_id' => $doc->id,
                    'role' => $signer['role'],
                    'step_order' => $signer['step_order'],
                    'is_required' => $signer['is_required'] ?? true,
                    'status' => 'pending',
                ]);
            }

            return $session->load(['document', 'signatures.user']);
        });
    }

    public function signDocument(int $signatureId, int $certificateId, TenantUser $currentUser): array
    {
        $signature = Signature::with(['signingSession', 'document'])->findOrFail($signatureId);

        if ($signature->status === 'signed') {
            throw new Exception('This document has already been signed.');
        }

        if ($signature->user_id != $currentUser->id) {
            throw new Exception('You are not authorized to sign this document.');
        }

        $cert = UserCertificate::where('user_id', $currentUser->id)
            ->where('id', $certificateId)
            ->where('is_revoked', false)
            ->where('is_active', true)
            ->first();

        if (!$cert) {
            throw new Exception('Certificate not found or does not belong to you.');
        }

        if (!$cert->isValid()) {
            throw new Exception('Your certificate has expired or is not yet valid. Please create a new certificate.');
        }

        $passphraseHash = $cert->passphrase_hash;
        if (!$passphraseHash) {
            Log::error("signDocument: Certificate missing passphrase_hash", [
                'cert_id' => $cert->id,
                'user_id' => $currentUser->id,
            ]);
            throw new Exception('Certificate data is incomplete. Please recreate your certificate.');
        }

        $session = $signature->signingSession;
        if ($session->mode === 'sequential' && $signature->step_order !== $session->current_step_order) {
            throw new Exception('It is not your turn to sign yet. Please wait for previous signers.');
        }

        $doc = $signature->document;
        $filePath = $doc->signed_file_path ?? $doc->original_file_path;
        
        // Try public disk first, then local (tenancy might have inconsistent roots)
        $fullPath = Storage::disk('public')->path($filePath);
        if (!file_exists($fullPath)) {
            $localPath = Storage::disk('local')->path($filePath);
            if (file_exists($localPath)) {
                $fullPath = $localPath;
            } else {
                throw new Exception("Document file not found at: {$fullPath} (or local fallback)");
            }
        }
        
        $fileContent = file_get_contents($fullPath);
        if ($fileContent === false) {
            throw new Exception("Failed to read document file.");
        }
        
        $hash = hash('sha256', $fileContent);

        $keyPath = $cert->private_key_path;
        $keyContent = Storage::get($keyPath);

        try {
            $sigString = $this->pkiService->signData($hash, $keyContent, $passphraseHash);
            if (!$sigString) {
                throw new Exception("Signing failed. Check certificate.");
            }
        } catch (Exception $e) {
            Log::error("signDocument: PKI signing failed", [
                'error' => $e->getMessage(),
                'cert_id' => $cert->id,
            ]);
            throw new Exception("Failed to sign document. Certificate may be invalid.");
        }

        return DB::transaction(function() use ($signature, $cert, $doc, $session, $currentUser, $filePath, $sigString, $hash) {
            $sigFilePath = "tenants/" . tenant('id') . "/signatures/{$signature->id}.sig";
            Storage::put($sigFilePath, $sigString);

            $signature->update([
                'certificate_id' => $cert->id,
                'status' => 'signed',
                'signature_file_path' => $sigFilePath,
                'signed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $allSignatures = Signature::where('signing_session_id', $session->id)
                ->where('is_required', true)
                ->get();
            $allSigned = $allSignatures->every(fn($s) => $s->status === 'signed');

            $signedFilePath = $this->addWatermarkAndQR($doc, $signature, $cert, $currentUser);

            $newFileSize = Storage::disk('public')->size($signedFilePath);
            $doc->update([
                'signed_file_path' => $signedFilePath,
                'current_hash' => hash_file('sha256', Storage::disk('public')->path($signedFilePath)),
                'status' => $allSigned ? 'signed' : 'partially_signed',
                'metadata' => array_merge($doc->metadata ?? [], [
                    'size' => $newFileSize,
                    'original_size' => $doc->metadata['size'] ?? 0,
                    'size_difference' => $newFileSize - ($doc->metadata['size'] ?? 0),
                ]),
            ]);

            if ($session->mode === 'sequential') {
                $nextSignature = Signature::where('signing_session_id', $session->id)
                    ->where('status', 'pending')
                    ->orderBy('step_order')
                    ->first();
                
                if ($nextSignature) {
                    $session->update(['current_step_order' => $nextSignature->step_order]);
                }
            }

            if ($allSigned) {
                $session->update(['status' => 'completed']);
            }

            return [
                'signature_id' => $signature->id,
                'document_id' => $doc->id,
                'status' => 'signed',
                'signed_at' => $signature->signed_at->toDateTimeString(),
            ];
        });
    }

    protected function addWatermarkAndQR(Document $doc, Signature $signature, UserCertificate $cert, TenantUser $user): string
    {
        $session = $signature->signingSession;
        $allSignatures = Signature::where('document_id', $doc->id)
            ->where('status', 'signed')
            ->with('user', 'userCertificate')
            ->orderBy('signed_at')
            ->get();

        $filePath = $doc->signed_file_path ?? $doc->original_file_path;
        $inputPath = Storage::disk('public')->path($filePath);
        
        if (!file_exists($inputPath)) {
            $localPath = Storage::disk('local')->path($filePath);
            if (file_exists($localPath)) {
                $inputPath = $localPath;
            } else {
                throw new Exception("Input document file not found at: {$inputPath} (or local fallback)");
            }
        }
        
        $outputFilename = pathinfo($doc->filename, PATHINFO_FILENAME) . '_signed_' . time() . '.pdf';
        $outputPath = "tenants/" . tenant('id') . "/signed/{$outputFilename}";
        $fullOutputPath = Storage::disk('public')->path($outputPath);
        
        $outputDir = dirname($fullOutputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $signaturesData = $allSignatures->map(function($sig) use ($doc) {
            return [
                'signer_name' => $sig->user->name ?? 'Unknown',
                'signer_email' => $sig->user->email ?? '',
                'date' => $sig->signed_at->format('Y-m-d H:i:s'),
                'cert_serial' => $sig->userCertificate->serial_number ?? '-',
                'document_title' => $doc->title,
                'document_filename' => $doc->filename,
                'document_hash' => $doc->current_hash ?? $doc->original_hash,
                'position' => 'bottom-right',
                'page' => 'last',
            ];
        })->toArray();

        $this->pdfService->addWatermarks($inputPath, $fullOutputPath, $signaturesData);

        return $outputPath;
    }
}
