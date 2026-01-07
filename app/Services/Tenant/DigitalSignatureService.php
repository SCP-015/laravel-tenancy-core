<?php

namespace App\Services\Tenant;

use App\Models\Tenant\CertificateAuthority;
use App\Models\Tenant\Document;
use App\Models\Tenant\Signature;
use App\Models\Tenant\SigningSession;
use App\Models\Tenant\UserCertificate;
use App\Models\Tenant\User as TenantUser;
use App\Services\PKIService;
use App\Services\PDFService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DigitalSignatureService
{
    protected PKIService $pkiService;
    protected PDFService $pdfService;

    public function __construct(PKIService $pkiService, PDFService $pdfService)
    {
        $this->pkiService = $pkiService;
        $this->pdfService = $pdfService;
    }

    public function getDashboard(?TenantUser $currentUser = null)
    {
        $ca = CertificateAuthority::where('is_revoked', false)->first();
        $isAdmin = $currentUser ? ($currentUser->isSuperAdmin() || $currentUser->isAdmin()) : false;

        $myCerts = $currentUser ? UserCertificate::where('user_id', $currentUser->id)
            ->where('is_revoked', false)
            ->get() : collect([]);

        $pendingSignatures = $currentUser ? Signature::where('user_id', $currentUser->id)
            ->where('status', 'pending')
            ->with(['document', 'signingSession'])
            ->get()
            ->filter(function($sig) {
                $session = $sig->signingSession;
                if ($session && $session->mode === 'sequential') {
                    return $sig->step_order === $session->current_step_order;
                }
                return true;
            })
            ->values() : collect([]);

        $allCertificatesRaw = $isAdmin ? UserCertificate::with('user')->get() : collect([]);

        $userIdsWithCerts = UserCertificate::where('is_revoked', false)->pluck('user_id')->unique()->toArray();
        $usersWithCerts = TenantUser::all()->map(function($user) use ($userIdsWithCerts) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_certificate' => in_array($user->id, $userIdsWithCerts),
            ];
        })->values();

        $signedDocs = $currentUser ? Document::whereHas('signatures', function($sq) use ($currentUser) {
            $sq->where('user_id', $currentUser->id)->where('status', 'signed');
        })
            ->where('status', 'signed')
            ->orderBy('updated_at', 'desc')
            ->get() : collect([]);

        return [
            'has_ca' => (bool)$ca,
            'ca_info' => $ca ? [
                'name' => $ca->name,
                'common_name' => $ca->common_name,
                'valid_from' => $ca->valid_from->format('Y-m-d H:i:s'),
                'valid_to' => $ca->valid_to->format('Y-m-d H:i:s'),
            ] : null,
            'user' => $currentUser ? [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
                'is_admin' => $isAdmin
            ] : null,
            'certificates' => $myCerts,
            'all_certificates_raw' => $allCertificatesRaw,
            'pending_signatures' => $pendingSignatures,
            'signed_documents' => $signedDocs,
            'available_signers' => $usersWithCerts,
        ];
    }


    public function createCA(array $data): CertificateAuthority
    {
        $existingCA = CertificateAuthority::first();
        if ($existingCA) {
            throw new Exception('Certificate Authority already exists.');
        }

        $validityDays = $data['validity_days'] ?? 3650;

        $caData = $this->pkiService->createRootCA(
            $data['common_name'],
            $data['organization'],
            $data['country'],
            $data['state'] ?? 'Jakarta',
            $data['city'] ?? 'Jakarta',
            null,
            $validityDays
        );

        $tenantId = tenant('id');
        $certPath = "tenants/{$tenantId}/ca/root-ca.crt";
        $keyPath = "tenants/{$tenantId}/ca/root-ca.key";

        Storage::put($certPath, $caData['certificate']);
        Storage::put($keyPath, $caData['private_key']);

        return CertificateAuthority::create([
            'name' => $data['organization'],
            'common_name' => $data['common_name'],
            'serial_number' => $caData['serial_number'],
            'certificate_path' => $certPath,
            'private_key_path' => $keyPath,
            'valid_from' => $caData['valid_from'],
            'valid_to' => $caData['valid_to'],
        ]);
    }

    public function issueCertificate(array $data, int $userId): UserCertificate
    {
        $ca = CertificateAuthority::first();
        if (!$ca) {
            throw new Exception('Root CA does not exist. Please create it first.');
        }

        $user = TenantUser::findOrFail($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $passphraseHash = hash_hmac('sha256', 'user-cert-' . $user->id . '-' . tenant('id'), config('app.key'));

        $caCert = Storage::get($ca->certificate_path);
        $caKey = Storage::get($ca->private_key_path);

        $certData = $this->pkiService->createUserCertificate(
            $caCert,
            $caKey,
            $user->name,
            $user->email,
            $passphraseHash,
            365
        );

        $tenantId = tenant('id');
        $certPath = "tenants/{$tenantId}/certificates/{$user->id}_{$certData['serial_number']}.crt";
        $keyPath = "tenants/{$tenantId}/certificates/{$user->id}_{$certData['serial_number']}.key";

        Storage::put($certPath, $certData['certificate']);
        Storage::put($keyPath, $certData['private_key']);

        return UserCertificate::create([
            'user_id' => $user->id,
            'certificate_authority_id' => $ca->id,
            'label' => $data['label'],
            'common_name' => $user->name,
            'email' => $user->email,
            'serial_number' => $certData['serial_number'],
            'certificate_path' => $certPath,
            'private_key_path' => $keyPath,
            'passphrase' => bcrypt($passphraseHash),
            'passphrase_hash' => $passphraseHash,
            'valid_from' => $certData['valid_from'],
            'valid_to' => $certData['valid_to'],
            'is_active' => true,
        ]);
    }
}
