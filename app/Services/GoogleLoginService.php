<?php

namespace App\Services;

use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Tenant\RecruiterInvitation;
use App\Services\ProxyTokenService;
use App\Services\TenantUserAuditService;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Service untuk menangani login Google
 * 
 * Service ini menangani semua logika bisnis terkait login melalui Google,
 * dengan memanfaatkan beberapa method dari NusaworkLoginService untuk
 * menghindari duplikasi kode.
 * 
 * @codeCoverageIgnore - OAuth service dengan external dependency (Google API), sulit di-test tanpa mock kompleks
 */
class GoogleLoginService
{
    use Loggable;
    
    /**
     * Kode error untuk exception yang perlu ditampilkan ke user
     * Menggunakan integer karena Exception::__construct() memerlukan int untuk parameter code
     * Menggunakan nilai yang sama dengan NusaworkLoginService untuk konsistensi
     */
    public const ERROR_USER_FRIENDLY = 100;
    
    private NusaworkLoginService $nusaworkLoginService;

    public function __construct(NusaworkLoginService $nusaworkLoginService)
    {
        $this->nusaworkLoginService = $nusaworkLoginService;
    }

    /**
     * Handle Google login callback
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function handleCallback(Request $request): array
    {
        try {
            // Ambil data user dari Google
            /** @var SocialiteUser $googleUser */
            /** @var \Laravel\Socialite\Two\AbstractProvider $provider */
            $provider = Socialite::driver('google');
            $googleUser = $provider->user();

            // Handle user (create atau update)
            $user = $this->handleGoogleUser($googleUser, $request);

            // Load tenants user
            $user->load('tenants');

            // Handle invitation jika ada
            $this->handleInvitation($user, $request);
            
            // Handle tenant selection
            $this->handleTenant($user);

            // Generate token dan response
            return $this->generateGoogleResponse($user, $request);

        } catch (\Exception $e) {
            $this->logError('Google login error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Jika exception memiliki kode ERROR_USER_FRIENDLY, tampilkan pesan asli ke user
            if ($e->getCode() === self::ERROR_USER_FRIENDLY) {
                throw new \Exception($e->getMessage(), self::ERROR_USER_FRIENDLY);
            }

            throw new \Exception(__('Failed to login with Google: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Handle Google user creation atau update
     *
     * @param SocialiteUser $googleUser
     * @param Request $request
     * @return User
     */
    private function handleGoogleUser(SocialiteUser $googleUser, Request $request): User
    {
        $now = now();

        // Pastikan user terdaftar dengan data Google
        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'password' => Hash::make(uniqid()),
                'email_verified_at' => $now,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'last_login_ip' => $request->ip(),
                'last_login_at' => $now,
                'last_login_user_agent' => $request->userAgent(),
            ]
        );

        // Update Google data jika user sudah ada
        $user->update([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'last_login_ip' => $request->ip(),
            'last_login_at' => $now,
            'last_login_user_agent' => $request->userAgent(),
        ]);

        return $user;
    }

    /**
     * Handle invitation jika ada dalam session
     *
     * @param User $user
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    private function handleInvitation(User $user, Request $request): void
    {
        // Ambil custom data dari session
        $customData = session('google_oauth_custom_data', []);
        
        if (!isset($customData['join_code'])) {
            return;
        }

        $joinCode = $customData['join_code'];
        
        // Hapus data dari session setelah digunakan
        session()->forget('google_oauth_custom_data');
        
        $targetTenant = Tenant::where('code', $joinCode)->first();

        if (!$targetTenant) {
            throw new \Exception(__('Portal with invitation code not found.'), self::ERROR_USER_FRIENDLY);
        }
        
        // Reuse validasi invitation dari NusaworkLoginService
        $this->validateInvitationForGoogle($targetTenant, $joinCode, $user);

        // 1. Update tenant user dalam konteks tenant dulu (untuk memastikan user ada di tenant DB)
        $this->updateTenantUserInContextForGoogle($targetTenant, $user, $request);

        // 2. Attach relation jika belum ada (dengan pengecekan)
        if (!$user->tenants()->where('tenant_id', $targetTenant->id)->exists()) {
            $user->tenants()->attach($targetTenant->id);
        }

        // 3. Update central tenant_user record
        $this->updateTenantUserRecordForGoogle($user, $targetTenant->id);
    }

    /**
     * Validasi invitation code untuk Google login
     * (Reuse dari NusaworkLoginService dengan adaptasi)
     *
     * @param Tenant $targetTenant
     * @param string $joinCode
     * @param User $user
     * @return void
     * @throws \Exception
     */
    private function validateInvitationForGoogle(Tenant $targetTenant, string $joinCode, User $user): void
    {
        $invitationValid = false;
        
        $targetTenant->run(function () use ($joinCode, $user, &$invitationValid) {
            $invitation = RecruiterInvitation::where('code', $joinCode)
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
     * Update tenant_user record di central database untuk Google login
     * (Adaptasi dari NusaworkLoginService)
     *
     * @param User $user
     * @param string $tenantId
     * @return void
     */
    private function updateTenantUserRecordForGoogle(User $user, string $tenantId): void
    {
        $tenantUserCentral = $user->tenantUsers()->where('tenant_id', $tenantId)->first();
        $tenant = Tenant::find($tenantId);
        
        if ($tenantUserCentral) {
            // Tentukan role berdasarkan kondisi
            $role = $this->determineUserRole($user, $tenant);
            
            $tenantUserCentral->update([
                'google_id' => $user->google_id,
                'avatar' => $user->avatar,
                'role' => $role,
                'tenant_join_date' => is_null($tenantUserCentral->tenant_join_date) ? now() : $tenantUserCentral->tenant_join_date,
                'created_at' => is_null($tenantUserCentral->created_at) ? now() : $tenantUserCentral->created_at,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Update tenant user dalam konteks tenant untuk Google login
     * (Adaptasi dari NusaworkLoginService)
     *
     * @param Tenant $tenant
     * @param User $user
     * @param Request $request
     * @return void
     */
    private function updateTenantUserInContextForGoogle(Tenant $tenant, User $user, Request $request): void
    {
        $now = now();
        
        // Tentukan role terlebih dahulu
        $role = $this->determineUserRole($user, $tenant);
        
        $tenant->run(function () use ($user, $tenant, $request, $now, $role) {
            $tenantUser = \App\Models\Tenant\User::where('global_id', $user->global_id)->first();
            $isNewUser = !$tenantUser;
            
            if ($tenantUser) {
                // Jika user sudah memiliki role super_admin atau admin di tenant context, pertahankan
                $existingRole = $tenantUser->role;
                $finalRole = ($existingRole === 'super_admin' || $existingRole === 'admin') ? $existingRole : $role;
                
                // Disable auditing untuk update ini
                $tenantUser->disableAuditing();
                
                $tenantUser->update([
                    'tenant_id' => $tenant->id,
                    'google_id' => $user->google_id,
                    'avatar' => $user->avatar,
                    'role' => $finalRole,
                    'tenant_join_date' => is_null($tenantUser->tenant_join_date) ? now() : $tenantUser->tenant_join_date,
                    'last_login_ip' => $request->ip(),
                    'last_login_at' => $now,
                    'last_login_user_agent' => $request->userAgent(),
                ]);
                
                $tenantUser->enableAuditing();
                $tenantUser->syncRoles([$finalRole]);
            } else {
                // Disable auditing untuk create ini
                \App\Models\Tenant\User::disableAuditing();
                
                // Buat user baru di tenant context jika belum ada
                $tenantUser = \App\Models\Tenant\User::create([
                    'global_id' => $user->global_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'password' => $user->password,
                    'tenant_id' => $tenant->id,
                    'google_id' => $user->google_id,
                    'avatar' => $user->avatar,
                    'role' => $role,
                    'tenant_join_date' => now(),
                    'last_login_ip' => $request->ip(),
                    'last_login_at' => $now,
                    'last_login_user_agent' => $request->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                \App\Models\Tenant\User::enableAuditing();
                $tenantUser->syncRoles([$role]);
            }
            
            // Create manual audit log dengan event 'login'
            TenantUserAuditService::logLogin($tenantUser, [
                'role' => $tenantUser->role,
                'name' => $tenantUser->name,
                'email' => $tenantUser->email,
                'last_login_ip' => $tenantUser->last_login_ip,
                'last_login_at' => $tenantUser->last_login_at,
            ], $request, $user);
        });
    }

    /**
     * Generate response untuk Google login
     * (Adaptasi dari NusaworkLoginService dengan format response khusus Google)
     *
     * @param User $user
     * @param Request $request
     * @return array
     */
    private function generateGoogleResponse(User $user, Request $request): array
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

        // Data response
        $data = [
            'status' => 'success',
            'message' => __('Login with Google successful'),
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => UserResource::make($user),
            'portal' => TenantResource::collection($user->tenants),
        ];

        // Return response berdasarkan environment
        return [
            'data' => $data,
            'cookie' => $cookie,
            'is_local' => config('app.env') === 'local',
        ];
    }

    /**
     * Handle tenant selection untuk user Google
     * 
     * @param User $user
     * @return void
     */
    private function handleTenant(User $user): void
    {
        // Jika user belum terkait dengan tenant manapun, tidak perlu melakukan apa-apa
        if ($user->tenants()->count() === 0) {
            return;
        }
        
        // Cari tenant di mana user adalah owner
        $ownedTenant = Tenant::where('owner_id', $user->id)->first();
        
        // Jika user memiliki tenant, set sebagai selected tenant
        if ($ownedTenant) {
            // 1. Update tenant user dalam konteks tenant dulu
            $this->updateTenantUserInContextForGoogle($ownedTenant, $user, request());

            // 2. Attach relation jika belum ada (dengan pengecekan)
            if (!$user->tenants()->where('tenant_id', $ownedTenant->id)->exists()) {
                $user->tenants()->attach($ownedTenant->id);
            }

            // 3. Update central tenant_user record
            $this->updateTenantUserRecordForGoogle($user, $ownedTenant->id);
        }
    }
    
    private function determineUserRole(User $user, ?Tenant $tenant): string
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
        
        // Kondisi: User adalah owner dari tenant
        if ($tenant && $tenant->owner_id === $user->id) {
            $role = 'super_admin';
        }
        
        return $role;
    }
    
    /**
     * Create manual audit log untuk event login
     *
     * @param  \App\Models\Tenant\User  $tenantUser
     * @param  bool  $isNewUser
     * @param  Request  $request
     * @return void
     */
    private function createLoginAuditLog($tenantUser, bool $isNewUser, Request $request): void
    {
        // Format last_login_at safely (bisa string atau Carbon object)
        $lastLoginAt = $tenantUser->last_login_at;
        if ($lastLoginAt instanceof \Carbon\Carbon) {
            $lastLoginAt = $lastLoginAt->format('Y-m-d H:i:s');
        }
        
        // Prepare new_values untuk audit log
        $newValues = [
            'role' => $tenantUser->role,
            'name' => $tenantUser->name,
            'email' => $tenantUser->email,
            'last_login_ip' => $tenantUser->last_login_ip,
            'last_login_at' => $lastLoginAt,
        ];

        // Create audit log manual dengan event 'login'
        TenantUserAuditService::logLogin($tenantUser, $newValues, $request);
    }

    /**
     * Generate HTML response untuk production environment
     *
     * @param array $data
     * @return string
     */
    public function generateHtmlResponse(array $data): string
    {
        return '
            <html>
            <head><title>Login Sukses</title></head>
            <body>
            <script>
                window.opener.postMessage(' . json_encode($data) . ', "*");
                window.close();
            </script>
            </body>
            </html>
        ';
    }
}
