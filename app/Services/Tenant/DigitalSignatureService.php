<?php

namespace App\Services\Tenant;

use App\Models\CentralRootCA;
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
        $ca = CertificateAuthority::where('is_central', true)->where('is_revoked', false)->first();
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
        throw new Exception('Creating Root CA is not allowed. Central Root CA is managed globally.');
    }

    public function ensureCentralRootCA(): CertificateAuthority
    {
        $ca = CertificateAuthority::where('is_central', true)->where('is_revoked', false)->first();
        if ($ca) {
            return $ca;
        }

        // Get active Central Root CA from central database
        $centralCA = CentralRootCA::getActive();
        if (!$centralCA) {
            throw new Exception('Central Root CA is not available.');
        }

        $tenantId = tenant('id');
        $tenantCertPath = "tenants/{$tenantId}/ca/root-ca.crt";
        $tenantKeyPath = "tenants/{$tenantId}/ca/root-ca.key";

        // Read from central storage using absolute path (not affected by tenancy)
        $basePath = base_path();
        $caCertFile = $basePath . '/storage/app/public/' . $centralCA->certificate_path;
        $caKeyFile = $basePath . '/storage/app/public/' . $centralCA->private_key_path;

        $caCert = file_get_contents($caCertFile);
        $caKey = file_get_contents($caKeyFile);

        if (!$caCert || !$caKey) {
            throw new Exception('Failed to read Central Root CA files from storage.');
        }

        // Write to tenant storage using absolute path
        $basePath = base_path();
        $tenantCertFile = $basePath . '/storage/app/public/' . $tenantCertPath;
        $tenantKeyFile = $basePath . '/storage/app/public/' . $tenantKeyPath;
        
        @mkdir(dirname($tenantCertFile), 0755, true);
        file_put_contents($tenantCertFile, $caCert);
        file_put_contents($tenantKeyFile, $caKey);

        $tenantCreatedAt = tenant('created_at') ?? now();
        $validityDaysRemaining = $centralCA->getValidityDaysRemaining();

        return CertificateAuthority::create([
            'central_root_ca_id' => $centralCA->id,
            'is_central' => true,
            'name' => 'Nusawork Root CA',
            'common_name' => $centralCA->common_name,
            'serial_number' => $centralCA->serial_number,
            'certificate_path' => $tenantCertPath,
            'private_key_path' => $tenantKeyPath,
            'valid_from' => $tenantCreatedAt,
            'valid_to' => $centralCA->valid_to,
        ]);
    }

    public function issueCertificate(array $data, int $userId): UserCertificate
    {
        $user = TenantUser::findOrFail($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $existingCert = UserCertificate::where('user_id', $userId)
            ->where('is_revoked', false)
            ->first();
        if ($existingCert) {
            throw new Exception('User already has an active certificate. Only 1 certificate per user is allowed.');
        }

        $ca = $this->ensureCentralRootCA();

        $passphraseHash = hash_hmac('sha256', 'user-cert-' . $user->id . '-' . tenant('id'), config('app.key'));

        // Read CA certificate and key using absolute path
        $basePath = base_path();
        $caCertFile = $basePath . '/storage/app/public/' . $ca->certificate_path;
        $caKeyFile = $basePath . '/storage/app/public/' . $ca->private_key_path;

        $caCert = file_get_contents($caCertFile);
        $caKey = file_get_contents($caKeyFile);

        if (!$caCert || !$caKey) {
            throw new Exception('Failed to read CA certificate or key from storage.');
        }

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
