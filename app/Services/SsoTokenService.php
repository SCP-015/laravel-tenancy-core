<?php

namespace App\Services;

class SsoTokenService
{
    /**
     * Get the issuer URL based on current request
     */
    public static function getIssuer(): string
    {
        $hostApi = config('app.url', 'http://localhost');
        $domain = array_reverse(explode('//', $hostApi));
        $host = $_SERVER['HTTP_HOST'] ?? $domain[0];

        return (isset($_SERVER['HTTPS']) && 'on' === ($_SERVER['HTTPS'] ?? null) ? 'https' : 'http') . "://{$host}";
    }

    /**
     * Generate short-lived JWT RS256 for SSO exchange
     *
     * @param string $aud Audience (target domain URL)
     * @param string|int $subject User identifier to put into `sub`
     * @param string|null $issuer Project issuer; if null, will use current app host like CustomAccessToken
     * @param int $ttl Token lifetime in seconds (default 259200s / 3 hari)
     * @param array $extraClaims Additional claims to merge (e.g., ['uid' => ..., 'uid_nusahire' => ..., 'email' => ...])
     * @return string JWT token
     * @throws \RuntimeException when private key not found or signing fails
     */
    public static function generate(string $aud, $subject, ?string $issuer = null, int $ttl = 259200, array $extraClaims = []): string
    {
        // Force using central/global storage path, not tenant-specific storage
        $privateKeyPath = base_path('storage/oauth-private.key');
        // @codeCoverageIgnoreStart
        // Exception handling - sulit di-test tanpa merusak environment
        if (!file_exists($privateKeyPath)) {
            throw new \RuntimeException('Private key not found at ' . $privateKeyPath);
        }

        $privateKeyPem = file_get_contents($privateKeyPath);
        if ($privateKeyPem === false || trim($privateKeyPem) === '') {
            throw new \RuntimeException('Private key is empty or unreadable');
        }
        // @codeCoverageIgnoreEnd

        // Determine issuer similar to CustomAccessToken
        if ($issuer === null) {
            $issuer = self::getIssuer();
        }

        $now = time();
        $payload = [
            'iss' => $issuer,
            'sub' => (string) $subject,
            'aud' => $aud,
            'iat' => $now,
            'nbf' => $now - 5, // slight clock skew tolerance
            'exp' => $now + $ttl,
            'jti' => bin2hex(random_bytes(16)),
        ];

        // Merge extra claims (override-protected: do not allow overwrite of standard claims)
        foreach ($extraClaims as $k => $v) {
            // @codeCoverageIgnoreStart
            if ($v === null) {
                continue;
            }
            // @codeCoverageIgnoreEnd
            if (!array_key_exists($k, $payload)) {
                $payload[$k] = $v;
            }
        }

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $base64UrlHeader = self::base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $base64UrlPayload = self::base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signingInput = $base64UrlHeader . '.' . $base64UrlPayload;

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        // @codeCoverageIgnoreStart
        // OpenSSL error handling - sulit di-test tanpa corrupt key
        if ($privateKey === false) {
            throw new \RuntimeException('Unable to parse private key');
        }
        // @codeCoverageIgnoreEnd

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        // @codeCoverageIgnoreStart
        if (!$ok) {
            throw new \RuntimeException('Failed to sign JWT');
        }
        // @codeCoverageIgnoreEnd

        $jwt = $signingInput . '.' . self::base64url_encode($signature);
        return $jwt;
    }

    /**
     * Generate JWT RS256 plus metadata mirip respons token OAuth.
     * Tidak memutus kompatibilitas: memanggil generate() lalu menyusun meta.
     *
     * @param string $aud
     * @param string|int $subject
     * @param string|null $issuer
     * @param int $ttl
     * @param array $extraClaims
     * @return array{access_token:string, token_type:string, expires_in:int, issued_at:int, expires_at:int}
     * 
     * @codeCoverageIgnore - Method tidak digunakan (dead code)
     */
    public static function generateWithMeta(string $aud, $subject, ?string $issuer = null, int $ttl = 120, array $extraClaims = []): array
    {
        $now = time();
        $token = self::generate($aud, $subject, $issuer, $ttl, $extraClaims);
        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => $ttl,
            'issued_at'    => $now,
            'expires_at'   => $now + $ttl,
        ];
    }

    private static function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
