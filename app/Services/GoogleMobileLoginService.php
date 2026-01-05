<?php

namespace App\Services;

use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Tenant;
use App\Services\ProxyTokenService;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Service untuk menangani Google Mobile Login
 *
 * Service ini menangani validasi Google ID Token dari mobile app
 * dan generate access token Laravel untuk mobile client.
 *
 * @codeCoverageIgnore - OAuth service dengan external dependency (Google API), sulit di-test tanpa mock kompleks
 */
class GoogleMobileLoginService
{
    use Loggable;

    private GoogleLoginService $googleLoginService;

    public function __construct(GoogleLoginService $googleLoginService)
    {
        $this->googleLoginService = $googleLoginService;
    }

    /**
     * Handle Google Mobile Login dengan ID Token
     *
     * @param string $idToken
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function handleMobileLogin(string $idToken, Request $request): array
    {
        try {
            // Validasi dan parse ID Token
            $payload = $this->verifyIdToken($idToken);

            if (!$payload) {
                throw new \Exception(__('Invalid Google ID token'), 400);
            }

            // Extract data dari payload
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? null;
            $googleId = $payload['sub'] ?? null;
            $emailVerified = $payload['email_verified'] ?? false;
            $avatar = $payload['picture'] ?? null;

            // Validasi field wajib
            if (!$email || !$googleId || !$emailVerified) {
                throw new \Exception(__('Google ID token verification failed'), 401);
            }

            // Handle user (create atau update)
            $user = $this->handleMobileUser($email, $name, $googleId, $avatar, $request);

            // Load tenants user
            $user->load('tenants');

            // Handle tenant selection (reuse logic dari GoogleLoginService)
            $this->handleTenant($user, $request);

            // Generate token dan response
            return $this->generateMobileResponse($user);

        } catch (\Exception $e) {
            $this->logError('Google mobile login error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Verifikasi Google ID Token menggunakan Google API Client
     *
     * @param string $idToken
     * @return array|false
     */
    private function verifyIdToken(string $idToken)
    {
        try {
            $client = new \Google_Client(['client_id' => config('services.google.mobile_client_id')]);
            $payload = $client->verifyIdToken($idToken);

            if ($payload) {
                // Validasi audience (client ID harus match)
                $audience = $payload['aud'] ?? null;
                $expectedClientId = config('services.google.mobile_client_id');

                if ($audience !== $expectedClientId) {
                    $this->logError('Google ID Token audience mismatch', [
                        'expected' => $expectedClientId,
                        'received' => $audience,
                    ]);
                    return false;
                }

                // Validasi issuer
                $issuer = $payload['iss'] ?? null;
                if (!in_array($issuer, ['https://accounts.google.com', 'accounts.google.com'])) {
                    $this->logError('Google ID Token issuer invalid', [
                        'issuer' => $issuer,
                    ]);
                    return false;
                }

                return $payload;
            }

            return false;
        } catch (\Exception $e) {
            $this->logError('Error verifying Google ID Token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle user creation atau update untuk mobile login
     *
     * @param string $email
     * @param string|null $name
     * @param string $googleId
     * @param string|null $avatar
     * @param Request $request
     * @return User
     */
    private function handleMobileUser(
        string $email,
        ?string $name,
        string $googleId,
        ?string $avatar,
        Request $request
    ): User {
        $now = now();

        // Cari atau buat user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name ?? $email,
                'password' => Hash::make(uniqid()),
                'email_verified_at' => $now,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'last_login_ip' => $request->ip(),
                'last_login_at' => $now,
                'last_login_user_agent' => $request->userAgent(),
            ]
        );

        // Update data jika user sudah ada
        $user->update([
            'google_id' => $googleId,
            'avatar' => $avatar,
            'last_login_ip' => $request->ip(),
            'last_login_at' => $now,
            'last_login_user_agent' => $request->userAgent(),
        ]);

        return $user;
    }

    /**
     * Handle tenant selection untuk user mobile
     * Reuse logic dari GoogleLoginService
     *
     * @param User $user
     * @param Request $request
     * @return void
     */
    private function handleTenant(User $user, Request $request): void
    {
        // Jika user belum terkait dengan tenant manapun, tidak perlu melakukan apa-apa
        if ($user->tenants()->count() === 0) {
            return;
        }

        // Cari tenant di mana user adalah owner
        $ownedTenant = Tenant::where('owner_id', $user->id)->first();

        // Jika user memiliki tenant, pastikan data tenant user ter-update
        if ($ownedTenant) {
            // Call method private dari GoogleLoginService menggunakan reflection
            // karena kita perlu reuse logic yang sama
            $reflector = new \ReflectionClass($this->googleLoginService);
            
            // Update tenant user in context
            $updateMethod = $reflector->getMethod('updateTenantUserInContextForGoogle');
            $updateMethod->setAccessible(true);
            $updateMethod->invoke($this->googleLoginService, $ownedTenant, $user, $request);

            // Attach relation jika belum ada
            if (!$user->tenants()->where('tenant_id', $ownedTenant->id)->exists()) {
                $user->tenants()->attach($ownedTenant->id);
            }

            // Update central tenant_user record
            $updateRecordMethod = $reflector->getMethod('updateTenantUserRecordForGoogle');
            $updateRecordMethod->setAccessible(true);
            $updateRecordMethod->invoke($this->googleLoginService, $user, $ownedTenant->id);
        }
    }

    /**
     * Generate response untuk mobile login
     *
     * @param User $user
     * @return array
     */
    private function generateMobileResponse(User $user): array
    {
        // Generate token
        $token = $user->createToken('mobile_auth_token')->accessToken;

        // Generate Proxy identifier untuk cookie (opsional untuk mobile)
        $proxyDuration = 60 * 24 * 7; // 7 hari
        $identifier = Str::uuid()->toString();

        // Simpan mapping identifier → token di storage
        ProxyTokenService::put($identifier, $token, $proxyDuration);

        // Data response untuk mobile
        return [
            'status' => 'success',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => UserResource::make($user),
            'portal' => TenantResource::collection($user->tenants),
            'select_tenant' => $user->tenants()->count() > 0,
        ];
    }
}
