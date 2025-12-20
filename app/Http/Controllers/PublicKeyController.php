<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicKeyController extends Controller
{
    /**
     * Get OAuth public key for cross-project SSO verification
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $publicKeyPath = storage_path('oauth-public.key');
        $publicKey = file_get_contents($publicKeyPath);

        return response()->json([
            'public_key' => $publicKey,
            'algorithm' => 'RS256',
            'format' => 'PEM',
            'fingerprint' => hash('sha256', $publicKey),
            'last_updated' => date('c', filemtime($publicKeyPath))
        ])->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Get public key metadata (without the key content)
     *
     * @return JsonResponse
     */
    public function metadata(): JsonResponse
    {
        $publicKeyPath = storage_path('oauth-public.key');
        $publicKey = file_get_contents($publicKeyPath);

        return response()->json([
            'algorithm' => 'RS256',
            'format' => 'PEM',
            'fingerprint' => hash('sha256', $publicKey),
            'last_updated' => date('c', filemtime($publicKeyPath)),
            'key_size' => $this->getKeySize($publicKeyPath),
            'available' => true
        ])->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Get RSA key size from public key file
     *
     * @param string $keyPath
     * @return int|null
     */
    private function getKeySize(string $keyPath): ?int
    {
        $key = openssl_pkey_get_public(file_get_contents($keyPath));
        $details = openssl_pkey_get_details($key);
        return $details['bits'] ?? null;
    }
}
