<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\SsoTokenService;

class TenantUser extends Model
{
    use CentralConnection;
    use Loggable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_owner' => 'boolean',
        'is_nusawork_integrated' => 'boolean',
        'tenant_join_date' => 'datetime',
        'last_login_at' => 'datetime',
        'nusawork_integrated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the tenant user.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Get the user that owns the tenant user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'global_user_id', 'global_id');
    }

    /**
     * Get the Nusawork domain URL for this tenant user
     *
     * @return string|null
     */
    public function getDomainUrl()
    {
        if (!$this->nusawork_id) {
            return null;
        }

        $nusaworkData = explode('|', $this->nusawork_id);
        return $nusaworkData[0] ?? null;
    }

    /**
     * Get the user's Nusawork ID for this tenant
     *
     * @return string|null
     */
    public function getUserIdNusawork()
    {
        if (!$this->nusawork_id) {
            return null;
        }

        $nusaworkData = explode('|', $this->nusawork_id);
        return $nusaworkData[1] ?? null;
    }

    /**
     * Check if user is integrated with Nusawork in this tenant
     *
     * @return bool
     */
    public function isNusaworkIntegrated()
    {
        return $this->is_nusawork_integrated && !empty($this->nusawork_id);
    }

    /**
     * Check if user has super admin role in this tenant
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has recruiter role in this tenant
     *
     * @return bool
     */
    public function isRecruiter()
    {
        return $this->isAdmin();
    }

    /**
     * Get the Nusawork public key for this tenant user
     *
     * @param string|null $domainUrl
     * @return string|null
     */
    public function getPublicKeyNusawork($domainUrl = null)
    {
        // Jika domainUrl tidak disediakan, ambil dari nusawork_id
        if (!$domainUrl) {
            $domainUrl = $this->getDomainUrl();
        }

        if (!$domainUrl) {
            $this->logWarning(__('Domain URL not found to retrieve public key'));
            return null;
        }

        // Create cache key untuk public key
        $cacheKey = 'nusawork_public_key_' . hash('sha256', $domainUrl);

        // Try to get public key from cache first
        $publicKey = Cache::get($cacheKey);
        if ($publicKey) {
            return $publicKey;
        }

        try {
            // Prepare the API URL for public key
            $apiUrl = "{$domainUrl}/auth/api/oauth/public-key";

            // Make the GET request to fetch public key
            $response = Http::get($apiUrl);

            // If the request is successful, extract the public key
            if ($response->successful()) {
                $publicKey = $response->json()['public_key'] ?? $response->body();

                if ($publicKey) {
                    // Cache public key for 24 hours
                    Cache::put($cacheKey, $publicKey, 86400);
                    return $publicKey;
                }
            }

            $this->logWarning('Gagal mengambil public key dari API', [
                'domain' => $domainUrl,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
        } catch (\Exception $e) {
            $this->logError('Error saat mengambil public key', [
                'domain' => $domainUrl,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Get the API token for Nusawork
     *
     * @return string
     */
    public function getTokenApi()
    {
        if (!$this->nusawork_id) {
            return '';
        }

        list($domainUrl, $userId) = explode('|', $this->nusawork_id);

        if (!$domainUrl || !$userId) {
            return '';
        }

        $ssoToken = $this->generateSsoTokenForNusawork($domainUrl, $userId);
        
        if (empty($ssoToken)) {
            return '';
        }

        return $this->exchangeSsoTokenForAccessToken($domainUrl, $ssoToken);
    }

    /**
     * Generate SSO token for Nusawork authentication
     * 
     * This method is excluded from code coverage because it requires:
     * - RSA private key infrastructure
     * - JWT token signing capabilities
     * - SsoTokenService static methods
     * 
     * @codeCoverageIgnore
     * @param string $domainUrl The Nusawork domain URL
     * @param string $userId The Nusawork user ID
     * @return string SSO token or empty string on failure
     */
    protected function generateSsoTokenForNusawork(string $domainUrl, string $userId): string
    {
        try {
            return SsoTokenService::generate(
                $domainUrl,                 // aud
                $userId,                    // sub (nusawork user id)
                null,                       // issuer otomatis sama seperti CustomAccessToken
                259200,                     // ttl detik / 3 hari
                [
                    'uid'          => $this->getUserIdNusawork(), // uid nusawork
                    'uid_nusahire' => $this->global_user_id,
                    'email'        => $this->user ? $this->user->email : '',
                ]
            );
        } catch (\Throwable $e) {
            $this->logError('Failed to generate SSO token', [
                'domain' => $domainUrl,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '';
        }
    }

    /**
     * Exchange SSO token for access token via OAuth endpoint
     * 
     * This method is excluded from code coverage because it requires:
     * - Active Nusawork OAuth server
     * - Valid SSO token infrastructure
     * - External API dependency
     * 
     * @codeCoverageIgnore
     * @param string $domainUrl The Nusawork domain URL
     * @param string $ssoToken The SSO token to exchange
     * @return string Access token or empty string
     */
    protected function exchangeSsoTokenForAccessToken(string $domainUrl, string $ssoToken): string
    {
        $apiUrl = "{$domainUrl}/auth/api/oauth/token";
        $data = [
            'grant_type' => 'sso',
            'sso_token' => $ssoToken,
            'project_key' => 'nusahire'
        ];

        // Make the POST request to fetch the access token
        $response = Http::asForm()->post($apiUrl, $data);

        // If the request is successful, extract the access token
        if ($response->successful()) {
            return $response->json()['access_token'] ?? '';
        }

        return '';
    }
}
