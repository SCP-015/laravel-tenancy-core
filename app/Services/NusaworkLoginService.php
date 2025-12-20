<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Tenant\RecruiterInvitation;
use App\Services\DataConversionService;
use App\Services\ProxyTokenService;
use App\Services\TenantService;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

/**
 * Service untuk menangani login Nusawork
 * 
 * Service ini menangani semua logika bisnis terkait login melalui Nusawork,
 * termasuk validasi token, manajemen user, dan manajemen tenant.
 * 
 * @codeCoverageIgnore - SSO service dengan external dependency (Nusawork API), sulit di-test tanpa mock kompleks
 */
class NusaworkLoginService
{
    use Loggable;
    
    /**
     * Kode error untuk exception yang perlu ditampilkan ke user
     * Menggunakan integer karena Exception::__construct() memerlukan int untuk parameter code
     */
    private const ERROR_USER_FRIENDLY = 100;

    private const NUSAWORK_ACCESS_PROFILE_PATH = '/emp/api/nusahire/integration/get_access_profile';

    /**
     * Selected tenant during login process
     *
     * @var Tenant|null
     */
    private ?Tenant $selectedTenant = null;

    /**
     * Handle Nusawork login callback
     *
     * @param array $input
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function handleCallback(array $input, Request $request): array
    {
        try {
            // Reset selected tenant
            $this->selectedTenant = null;

            // Parse dan validasi token
            $tokenData = $this->parseAndValidateToken($input['token']);

            // Konversi boolean parameters
            $forceCreateUser = DataConversionService::arrayToBool($input, 'force_create_user', true);
            $useSessionFlow = DataConversionService::arrayToBool($input, 'use_session_flow', false);

            // Handle user (create atau update)
            $user = $this->handleUser($input, $tokenData, $forceCreateUser, $request);

            // Load tenants user
            $user->load('tenants');

            // Handle tenant management
            $this->handleTenantManagement($user, $input, $tokenData, $request);

            // Generate token dan response
            return $this->generateTokenResponse($user, $useSessionFlow, $request);
        } catch (\Throwable $th) {
            $this->logError('Nusawork callback error: ', [
                'request' => $input,
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'trace' => $th->getTraceAsString(),
            ]);

            // Jika exception memiliki kode ERROR_USER_FRIENDLY, tampilkan pesan asli ke user
            if ($th->getCode() === self::ERROR_USER_FRIENDLY) {
                throw new \Exception($th->getMessage(), self::ERROR_USER_FRIENDLY);
            }

            // Fallback ke pesan generic
            throw new \Exception(__('Something went wrong. Please try again later.'));
        }
    }

    /**
     * Parse dan validasi token Nusawork
     *
     * @param string $token
     * @return array
     * @throws \Exception
     */
    private function parseAndValidateToken(string $token): array
    {
        $tokenParse = explode('.', $token);
        $payload = json_decode(base64_decode($tokenParse[1]), true);

        $nusaworkDomain = $payload['iss'] ?? null;
        $uid = $payload['uid'] ?? null;
        $nusaworkId = $nusaworkDomain . '|' . $uid;
        $isSuperAdmin = in_array("Super Admin", $payload['role']['role_name'] ?? []);

        if (!$uid || !$nusaworkDomain) {
            throw new \Exception(__('Invalid token'));
        }

        return [
            'nusawork_domain' => $nusaworkDomain,
            'uid' => $uid,
            'nusawork_id' => $nusaworkId,
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    /**
     * Handle user creation atau update
     *
     * @param array $input
     * @param array $tokenData
     * @param bool $forceCreateUser
     * @param Request $request
     * @return User
     * @throws \Exception
     */
    private function handleUser(array $input, array $tokenData, bool $forceCreateUser, Request $request): User
    {
        $user = User::where('email', $input['email'])->first();

        if (!$user) {
            if (!$forceCreateUser) {
                throw new \Exception(__('User account not found. Please contact your administrator to create an account or register first.'));
            }

            // Buat user baru
            $user = User::create([
                'name' => $input['first_name'] . ' ' . $input['last_name'],
                'email' => $input['email'],
                'password' => Hash::make(uniqid()),
                'email_verified_at' => now(),
                'nusawork_id' => $tokenData['nusawork_id'],
                'avatar' => $input['photo'] ?? null,
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
                'last_login_user_agent' => $request->userAgent(),
            ]);
        } else {
            // Update data user
            $user->update([
                'name' => $input['first_name'] . ' ' . $input['last_name'],
                'email_verified_at' => $user->email_verified_at ?? now(),
                'nusawork_id' => $tokenData['nusawork_id'],
                'avatar' => $input['photo'] ?? null,
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
                'last_login_user_agent' => $request->userAgent(),
            ]);
        }

        return $user;
    }

    /**
     * Handle tenant management berdasarkan kondisi user
     *
     * @param User $user
     * @param array $input
     * @param array $tokenData
     * @param Request $request
     * @return void
     */
    private function handleTenantManagement(User $user, array $input, array $tokenData, Request $request): void
    {
        if ($user->tenants->count() === 0) {
            $this->handleUserWithoutTenant($user, $input, $tokenData, $request);
        } else {
            $this->handleUserWithTenant($user, $input, $tokenData, $request);
        }
    }

    /**
     * Handle user yang belum memiliki tenant
     *
     * @param User $user
     * @param array $input
     * @param array $tokenData
     * @param Request $request
     * @return void
     */
    private function handleUserWithoutTenant(User $user, array $input, array $tokenData, Request $request): void
    {
        Passport::actingAs($user);

        if (!empty($input['join_code'])) {
            $this->handleJoinWithCode($user, $input, $tokenData, $request);
        } else {
            $this->guardNusaworkAccessForNewTenantFlow($user, $input);
            $this->handleJoinWithCompany($user, $input, $tokenData, $request);
        }
    }

    private function guardNusaworkAccessForNewTenantFlow(User $user, array $input): void
    {
        $token = $input['token'] ?? null;
        if (!$token) {
            throw new \Exception(__('Authentication token is required.'), self::ERROR_USER_FRIENDLY);
        }

        $companyName = $input['company']['name'] ?? null;
        if (!$companyName) {
            throw new \Exception(__('Company name is required.'), self::ERROR_USER_FRIENDLY);
        }

        $accessData = $this->getNusaworkAccessProfile($token);
        $hasAccess = (bool) ($accessData['has_access'] ?? false);

        if (!$hasAccess) {
            throw new \Exception(
                __('Akun Nusawork Anda tidak memiliki akses ke Nusahire. Silakan hubungi administrator Nusawork untuk meminta akses.'),
                self::ERROR_USER_FRIENDLY
            );
        }
    }

    private function getNusaworkAccessProfile(string $token): array
    {
        try {
            $tokenParse = explode('.', $token);
            $payload = json_decode(base64_decode($tokenParse[1] ?? ''), true);
            $nusaworkDomain = $payload['iss'] ?? null;

            if (!$nusaworkDomain) {
                throw new \Exception(__('Invalid token'));
            }

            $response = Http::withToken($token)
                ->get(rtrim($nusaworkDomain, '/') . self::NUSAWORK_ACCESS_PROFILE_PATH);

            if ($response->failed()) {
                $this->logError('Gagal memeriksa akses Nusawork (get_access_profile).', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception(
                    __('Gagal memeriksa akses Nusawork. Silakan coba lagi nanti.'),
                    self::ERROR_USER_FRIENDLY
                );
            }

            return (array) $response->json();
        } catch (\Exception $e) {
            if ($e->getCode() === self::ERROR_USER_FRIENDLY) {
                throw $e;
            }

            $this->logError('Error saat memeriksa akses Nusawork (get_access_profile).', [
                'message' => $e->getMessage(),
            ]);

            throw new \Exception(
                __('Gagal memeriksa akses Nusawork. Silakan coba lagi nanti.'),
                self::ERROR_USER_FRIENDLY
            );
        }
    }

    /**
     * Handle user yang sudah memiliki tenant
     *
     * @param User $user
     * @param array $input
     * @param array $tokenData
     * @param Request $request
     * @return void
     */
    private function handleUserWithTenant(User $user, array $input, array $tokenData, Request $request): void
    {
        if (!empty($input['join_code'])) {
            $this->handleJoinWithCode($user, $input, $tokenData, $request);
        } else {
            $this->handleExistingTenant($user, $input, $tokenData, $request);
        }
    }

    /**
     * Handle join dengan invitation code
     *
     * @param User $user
     * @param array $input
     * @param array $tokenData
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    private function handleJoinWithCode(User $user, array $input, array $tokenData, Request $request): void
    {
        $targetTenant = Tenant::where('code', $input['join_code'])->first();

        if (!$targetTenant) {
            throw new \Exception(__('Portal with invitation code not found.'), self::ERROR_USER_FRIENDLY);
        }

        // Set selected tenant
        $this->selectedTenant = $targetTenant;

        // Validasi domain email user dengan pemilik tenant
        $this->validateUserDomainWithTenantOwner($user, $targetTenant);

        // Validasi invitation
        $this->validateInvitation($targetTenant, $input, $user);

        // 1. Update tenant user dalam konteks tenant dulu (untuk memastikan user ada di tenant DB)
        $this->updateTenantUserInContext($targetTenant, $user, $tokenData, $input, $request);

        // 2. Attach relation jika belum ada (dengan pengecekan)
        if (!$user->tenants()->where('tenant_id', $targetTenant->id)->exists()) {
            $user->tenants()->attach($targetTenant->id);
        }

        // 3. Update central tenant_user record
        $this->updateTenantUserRecord($user, $targetTenant->id, $tokenData, $input);
    }

    /**
     * Validasi domain email user dengan pemilik tenant
     *
     * @param User $user
     * @param Tenant $tenant
     * @return void
     * @throws \Exception
     */
    private function validateUserDomainWithTenantOwner(User $user, Tenant $tenant): void
    {
        // Dapatkan owner tenant
        $owner = $tenant->owner;

        if (!$owner) {
            throw new \Exception(__('Portal owner not found. Please contact administrator.'), self::ERROR_USER_FRIENDLY);
        }

        // Ekstrak domain dari nusawork_id user dan owner
        $userNusaworkId = $user->nusawork_id;
        $ownerNusaworkId = $owner->nusawork_id;
        
        // Jika salah satu tidak memiliki nusawork_id, skip validasi
        if (empty($userNusaworkId) || empty($ownerNusaworkId)) {
            return;
        }
        
        // Format nusawork_id: "https://domain.app.nusa.work|id"
        // Ekstrak domain dari nusawork_id
        $userDomainParts = explode('|', $userNusaworkId);
        $ownerDomainParts = explode('|', $ownerNusaworkId);
        
        if (count($userDomainParts) < 1 || count($ownerDomainParts) < 1) {
            return;
        }
        
        $userUrl = $userDomainParts[0];
        $ownerUrl = $ownerDomainParts[0];
        
        // Parse URL untuk mendapatkan host
        $userUrlParts = parse_url($userUrl);
        $ownerUrlParts = parse_url($ownerUrl);
        
        if (!isset($userUrlParts['host']) || !isset($ownerUrlParts['host'])) {
            return;
        }
        
        $userDomain = $userUrlParts['host'];
        $ownerDomain = $ownerUrlParts['host'];
        
        // Validasi domain
        if ($userDomain !== $ownerDomain) {
            throw new \Exception(__(
                'Domain Nusawork Anda (:userDomain) tidak sama dengan domain pemilik portal (:ownerDomain). Hanya pengguna dengan domain Nusawork yang sama dengan pemilik portal yang dapat bergabung dengan portal ini.',
                ['userDomain' => $userDomain, 'ownerDomain' => $ownerDomain]
            ), self::ERROR_USER_FRIENDLY);
        }
    }

    /**
     * Handle join dengan company name (tanpa invitation code)
     *
     * @param User $user
     * @param array $input
     * @param array $tokenData
     * @param Request $request
     * @return void
     */
    private function handleJoinWithCompany(User $user, array $input, array $tokenData, Request $request): void
    {
        $tenant = Tenant::where('name', $input['company']['name'])->first();

        // Jika tenant belum ada dan user adalah super admin, buat tenant baru
        if (!$tenant && $tokenData['is_super_admin']) {
            $this->createNewTenant($user, $input);
            $tenant = Tenant::where('name', $input['company']['name'])->first();
        }

        if ($tenant) {
            // Set selected tenant
            $this->selectedTenant = $tenant;

            // 1. Update tenant user dalam konteks tenant dulu (untuk memastikan user ada di tenant DB)
            $this->updateTenantUserInContext($tenant, $user, $tokenData, $input, $request);

            // 2. Attach relation jika belum ada (dengan pengecekan)
            if (!$user->tenants()->where('tenant_id', $tenant->id)->exists()) {
                $user->tenants()->attach($tenant->id);
            }

            // 3. Update central tenant_user record
            $this->updateTenantUserRecord($user, $tenant->id, $tokenData, $input);
        }
    }

    /**
     * Handle existing tenant (user sudah punya tenant, login ke tenant yang sama)
     *
     * @param User $user
     * @param array $input
     * @param array $tokenData
     * @param Request $request
     * @return void
     */
    private function handleExistingTenant(User $user, array $input, array $tokenData, Request $request): void
    {
        $tenantName = $input['company']['name'];
        $tenant = Tenant::where('name', $tenantName)->first();

        // Jika user sudah pernah login menggunakan Google, cari tenant yang dimilikinya
        if (!empty($user->google_id) && !$tenant) {
            // Cari tenant di mana user adalah owner
            $ownedTenant = Tenant::where('owner_id', $user->id)->first();

            // Jika user memiliki tenant, gunakan tenant tersebut
            if ($ownedTenant) {
                $tenant = $ownedTenant;
            }
        }

        if ($tenant) {
            // Set selected tenant
            $this->selectedTenant = $tenant;

            // 1. Update tenant user dalam konteks tenant dulu
            $this->updateTenantUserInContext($tenant, $user, $tokenData, $input, $request);

            // 2. Attach relation jika belum ada (dengan pengecekan)
            if (!$user->tenants()->where('tenant_id', $tenant->id)->exists()) {
                $user->tenants()->attach($tenant->id);
            }

            // 3. Update central tenant_user record
            $this->updateTenantUserRecord($user, $tenant->id, $tokenData, $input);
        }

        if (!$tenant && $tokenData['is_super_admin'] && empty($user->google_id)) {
            // Support multiple tenant
            Passport::actingAs($user);
            $this->createNewTenant($user, $input);
            $tenant = Tenant::where('name', $input['company']['name'])->first();

            if ($tenant) {
                // Set selected tenant
                $this->selectedTenant = $tenant;

                // 1. Update tenant user dalam konteks tenant dulu
                $this->updateTenantUserInContext($tenant, $user, $tokenData, $input, $request);

                // 2. Attach relation jika belum ada (dengan pengecekan)
                if (!$user->tenants()->where('tenant_id', $tenant->id)->exists()) {
                    $user->tenants()->attach($tenant->id);
                }

                // 3. Update central tenant_user record
                $this->updateTenantUserRecord($user, $tenant->id, $tokenData, $input);
            }
        }
    }

    /**
     * Validasi invitation code
     *
     * @param Tenant $targetTenant
     * @param array $input
     * @param User $user
     * @return void
     * @throws \Exception
     */
    private function validateInvitation(Tenant $targetTenant, array $input, User $user): void
    {
        $invitationValid = false;

        $targetTenant->run(function () use ($input, $user, &$invitationValid) {
            $invitation = RecruiterInvitation::where('code', $input['join_code'])
                ->where('email', $user->email)
                ->first();

            if ($invitation && $invitation->isValid()) {
                $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);
                $invitationValid = true;
            }
        });

        if (!$invitationValid) {
            throw new \Exception(__('Your email does not match the invitation or the invitation has expired.'), self::ERROR_USER_FRIENDLY);
        }
    }

    /**
     * Update tenant_user record di central database
     *
     * @param User $user
     * @param string $tenantId
     * @param array $tokenData
     * @param array $input
     * @return void
     */
    private function updateTenantUserRecord(User $user, string $tenantId, array $tokenData, array $input): void
    {
        $tenantUserCentral = $user->tenantUsers()->where('tenant_id', $tenantId)->first();
        $tenant = Tenant::find($tenantId);

        if ($tenantUserCentral) {
            // Tentukan role berdasarkan kondisi
            $role = $this->determineUserRole($user, $tenant, $tokenData, $input);

            $tenantUserCentral->update([
                'google_id' => $user->google_id,
                'nusawork_id' => $tokenData['nusawork_id'],
                'avatar' => $input['photo'] ?? $tenantUserCentral->avatar,
                'role' => $role,
                'tenant_join_date' => is_null($tenantUserCentral->tenant_join_date) ? now() : $tenantUserCentral->tenant_join_date,
                'created_at' => is_null($tenantUserCentral->created_at) ? now() : $tenantUserCentral->created_at,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Update tenant user dalam konteks tenant
     *
     * @param Tenant $tenant
     * @param User $user
     * @param array $tokenData
     * @param array $input
     * @param Request $request
     * @return void
     */
    private function updateTenantUserInContext(Tenant $tenant, User $user, array $tokenData, array $input, Request $request): void
    {
        // Tentukan role terlebih dahulu
        $role = $this->determineUserRole($user, $tenant, $tokenData, $input);

        $tenant->run(function () use ($user, $tenant, $tokenData, $input, $request, $role) {
            $tenantUser = \App\Models\Tenant\User::where('global_id', $user->global_id)->first();

            if ($tenantUser) {
                // Jika user sudah memiliki role super_admin atau admin di tenant context, pertahankan
                $existingRole = $tenantUser->role;
                $finalRole = ($existingRole === 'super_admin' || $existingRole === 'admin') ? $existingRole : $role;

                // Disable auditing untuk update ini karena kita akan create manual audit log dengan event 'login'
                $tenantUser->disableAuditing();

                $tenantUser->update([
                    'tenant_id' => $tenant->id,
                    'google_id' => $user->google_id,
                    'nusawork_id' => $tokenData['nusawork_id'],
                    'avatar' => $input['photo'] ?? $tenantUser->avatar,
                    'role' => $finalRole,
                    'tenant_join_date' => is_null($tenantUser->tenant_join_date) ? now() : $tenantUser->tenant_join_date,
                    'last_login_ip' => $request->ip(),
                    'last_login_at' => now(),
                    'last_login_user_agent' => $request->userAgent(),
                ]);

                $tenantUser->enableAuditing();
                $tenantUser->syncRoles([$finalRole]);

                // Create manual audit log dengan event 'login'
                TenantUserAuditService::logLogin($tenantUser, [
                    'role' => $tenantUser->role,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                    'last_login_ip' => $tenantUser->last_login_ip,
                    'last_login_at' => $tenantUser->last_login_at,
                ], $request, $user);
            } else {
                // Buat user baru di tenant context jika belum ada
                // Tidak menggunakan ID yang sama untuk menghindari conflict
                // Disable auditing untuk create ini karena kita akan create manual audit log dengan event 'login'
                \App\Models\Tenant\User::disableAuditing();

                $tenantUser = \App\Models\Tenant\User::create([
                    'global_id' => $user->global_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'password' => $user->password,
                    'tenant_id' => $tenant->id,
                    'google_id' => $user->google_id,
                    'nusawork_id' => $tokenData['nusawork_id'],
                    'avatar' => $input['photo'] ?? $user->avatar,
                    'role' => $role,
                    'tenant_join_date' => now(),
                    'last_login_ip' => $request->ip(),
                    'last_login_at' => now(),
                    'last_login_user_agent' => $request->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \App\Models\Tenant\User::enableAuditing();
                $tenantUser->syncRoles([$role]);

                // Create manual audit log dengan event 'login'
                TenantUserAuditService::logLogin($tenantUser, [
                    'role' => $tenantUser->role,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                    'last_login_ip' => $tenantUser->last_login_ip,
                    'last_login_at' => $tenantUser->last_login_at,
                ], $request, $user);
            }
        });
    }

    /**
     * Buat tenant baru
     *
     * @param User $user
     * @param array $input
     * @return void
     */
    private function createNewTenant(User $user, array $input): void
    {
        TenantService::store([
            'code' => Tenant::generateCode(),
            'name' => $input['company']['name'],
        ]);
    }

    /**
     * Generate token dan response
     *
     * @param User $user
     * @param bool $useSessionFlow
     * @param Request $request
     * @return array
     */
    private function generateTokenResponse(User $user, bool $useSessionFlow, Request $request): array
    {
        // Generate token
        $token = $user->createToken('auth_token')->accessToken;

        // Generate Proxy identifier
        $proxyDuration = 60 * 24 * 7;
        $proxyCookieName = config('custom.proxy_key');
        $identifier = Str::uuid()->toString();

        // Simpan mapping identifier → token di storage
        ProxyTokenService::put($identifier, $token, $proxyDuration);

        // Set cookie
        $cookie = cookie($proxyCookieName, $identifier, $proxyDuration, null, null, false, true);

        // Jika menggunakan session flow
        if ($useSessionFlow) {
            return $this->generateSessionResponse($user, $token, $identifier);
        }

        // Flow normal
        return [
            'status' => 'success',
            'token' => $token,
            'user' => $user,
            'cookie' => $cookie,
            'select_tenant' => $this->selectedTenant,
        ];
    }

    /**
     * Menentukan role user berdasarkan kondisi
     * 
     * @param User $user
     * @param Tenant|null $tenant
     * @param array $tokenData
     * @param array $input
     * @return string
     */
    private function determineUserRole(User $user, ?Tenant $tenant, array $tokenData, array $input): string
    {
        // Cek role yang sudah ada di tenant_user jika ada
        $existingRole = null;
        if ($tenant) {
            $tenantUserCentral = $user->tenantUsers()->where('tenant_id', $tenant->id)->first();
            if ($tenantUserCentral) {
                $existingRole = $tenantUserCentral->role;
            }
        }

        // Jika user sudah memiliki role super_admin, pertahankan
        if ($existingRole === 'super_admin') {
            return $existingRole;
        }

        // Default role adalah recruiter
        $role = 'admin';

        // Kondisi 1: User adalah super admin dari Nusawork
        if ($tokenData['is_super_admin']) {
            $role = 'super_admin';
        }

        // Kondisi 2: User adalah owner dari tenant
        if ($tenant && $tenant->owner_id === $user->id) {
            $role = 'super_admin';
        }

        // Kondisi 3: Jika tidak ada tenant dan masuk dengan join_code, tetap recruiter
        if ($user->tenants->count() === 0 && !empty($input['join_code'])) {
            $role = 'admin';
        }

        // Kondisi 4: Jika ada tenant dan ada join_code, tetap recruiter
        if ($user->tenants->count() > 0 && !empty($input['join_code'])) {
            $role = 'admin';
        }

        return $role;
    }

    /**
     * Generate session response untuk session flow
     *
     * @param User $user
     * @param string $token
     * @param string $identifier
     * @return array
     */
    private function generateSessionResponse(User $user, string $token, string $identifier): array
    {
        $sessionId = Str::uuid()->toString();
        $tempToken = Str::random(32);

        // Simpan data session sementara (TTL 10 menit)
        $sessionData = [
            'user_id' => $user->id,
            'access_token' => $token,
            'temp_token' => $tempToken,
            'proxy_identifier' => $identifier,
            'select_tenant' => $this->selectedTenant,
            'created_at' => now()->timestamp,
        ];

        cache()->put("session_login:{$sessionId}", $sessionData, 600); // 10 menit

        $redirectUrl = url("/session/{$sessionId}?t={$tempToken}");

        return [
            'status' => 'success',
            'session_id' => $sessionId,
            'redirect_url' => $redirectUrl,
            'message' => __('Session created successfully'),
            'select_tenant' => $this->selectedTenant,
        ];
    }
}
