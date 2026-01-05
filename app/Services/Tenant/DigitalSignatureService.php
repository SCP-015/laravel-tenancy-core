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
            'ca_info' => $ca ? ['name' => $ca->name, 'common_name' => $ca->common_name] : null,
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
        $distinguishedName = [
            'countryName' => $data['country'],
            'stateOrProvinceName' => $data['state'] ?? '',
            'localityName' => $data['city'] ?? '',
            'organizationName' => $data['organization'],
            'commonName' => $data['common_name'],
        ];

        $caData = $this->pkiService->createRootCA($distinguishedName, $validityDays);

        $tenantId = tenant('id');
        $certPath = "tenants/{$tenantId}/ca/root-ca.crt";
        $keyPath = "tenants/{$tenantId}/ca/root-ca.key";

        Storage::put($certPath, $caData['certificate']);
        Storage::put($keyPath, $caData['private_key']);

        return CertificateAuthority::create([
            'name' => $data['organization'],
            'common_name' => $data['common_name'],
            'certificate_path' => $certPath,
            'private_key_path' => $keyPath,
            'valid_from' => now(),
            'valid_to' => now()->addDays($validityDays),
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

        $distinguishedName = [
            'countryName' => 'ID',
            'stateOrProvinceName' => '',
            'localityName' => '',
            'organizationName' => $ca->name,
            'commonName' => $user->name,
            'emailAddress' => $user->email,
        ];

        $caCert = Storage::get($ca->certificate_path);
        $caKey = Storage::get($ca->private_key_path);

        $certData = $this->pkiService->createUserCertificate(
            $distinguishedName,
            $caCert,
            $caKey,
            $passphraseHash,
            365
        );

        $tenantId = tenant('id');
        $certPath = "tenants/{$tenantId}/certificates/{$user->id}_{$certData['serial']}.crt";
        $keyPath = "tenants/{$tenantId}/certificates/{$user->id}_{$certData['serial']}.key";

        Storage::put($certPath, $certData['certificate']);
        Storage::put($keyPath, $certData['private_key']);

        return UserCertificate::create([
            'user_id' => $user->id,
            'certificate_authority_id' => $ca->id,
            'label' => $data['label'],
            'common_name' => $user->name,
            'email' => $user->email,
            'serial_number' => $certData['serial'],
            'certificate_path' => $certPath,
            'private_key_path' => $keyPath,
            'passphrase' => bcrypt($passphraseHash),
            'passphrase_hash' => $passphraseHash,
            'valid_from' => now(),
            'valid_to' => now()->addDays(365),
            'is_active' => true,
        ]);
    }
}
