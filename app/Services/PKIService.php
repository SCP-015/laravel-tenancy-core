<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;

class PKIService
{
    protected $config;

    public function __construct()
    {
        $this->config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
    }

    /**
     * Create a Root CA Certificate and Private Key
     */
    /**
     * Create a Root CA Certificate and Private Key
     */
    public function createRootCA(
        string $commonName, 
        string $organization, 
        string $country = 'ID',
        string $province = 'Jakarta',
        string $locality = 'Jakarta',
        string $email = null,
        int $validDays = 3650
    ): array
    {
        // 1. Generate Private Key
        $privKey = openssl_pkey_new($this->config);
        if (!$privKey) {
            throw new Exception("Failed to generate private key: " . openssl_error_string());
        }

        // Generate default email if not provided
        if (!$email) {
            $email = "admin@" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $organization)) . ".com";
        }

        // 2. Create CSR for Self-Signed Cert
        $dn = [
            "countryName" => $country,
            "stateOrProvinceName" => $province,
            "localityName" => $locality,
            "organizationName" => $organization,
            "organizationalUnitName" => "Digital Signature Root CA",
            "commonName" => $commonName,
            "emailAddress" => $email
        ];

        $csr = openssl_csr_new($dn, $privKey, $this->config);
        if (!$csr) {
            throw new Exception("Failed to generate CSR: " . openssl_error_string());
        }

        // 3. Sign CSR (Self-signed)
        $sscert = openssl_csr_sign($csr, null, $privKey, $validDays, $this->config, 0); // 0 = serial
        if (!$sscert) {
            throw new Exception("Failed to sign CSR: " . openssl_error_string());
        }

        // 4. Export
        openssl_x509_export($sscert, $certout);
        openssl_pkey_export($privKey, $pkeyout);

        // Get details
        $parsed = openssl_x509_parse($certout);

        return [
            'certificate' => $certout,
            'private_key' => $pkeyout,
            'serial_number' => $parsed['serialNumberHex'] ?? bin2hex(random_bytes(10)),
            'valid_from' => date('Y-m-d H:i:s', $parsed['validFrom_time_t']),
            'valid_to' => date('Y-m-d H:i:s', $parsed['validTo_time_t']),
        ];
    }

    /**
     * Create a User Certificate signed by Root CA
     */
    public function createUserCertificate(
        string $caCertPem, 
        string $caKeyPem, 
        string $commonName, 
        string $email, 
        string $passphrase
    ): array
    {
        // 1. Generate User Private Key
        $privKey = openssl_pkey_new($this->config);
        
        // 2. Create CSR
        $dn = [
            "countryName" => "ID",
            "commonName" => $commonName,
            "emailAddress" => $email
        ];
        
        $csr = openssl_csr_new($dn, $privKey, $this->config);

        // 3. Load CA Cert and Key
        $caCert = openssl_x509_read($caCertPem);
        $caKey = openssl_pkey_get_private($caKeyPem);

        if (!$caCert || !$caKey) {
            throw new Exception("Invalid CA Certificate or Key");
        }

        // 4. Sign with CA
        // Use random serial number
        $serial = (int) hexdec(bin2hex(random_bytes(4))); // Use 4 bytes to avoid overflow
        $userCert = openssl_csr_sign($csr, $caCert, $caKey, 365, $this->config, $serial);

        if (!$userCert) {
            throw new Exception("Failed to sign user certificate: " . openssl_error_string());
        }

        // 5. Export
        openssl_x509_export($userCert, $certout);
        // Encrypt private key with passphrase
        openssl_pkey_export($privKey, $pkeyout, $passphrase);

         $parsed = openssl_x509_parse($certout);

        return [
            'certificate' => $certout,
            'private_key' => $pkeyout,
            'serial_number' => $parsed['serialNumberHex'] ?? dechex($serial),
            'valid_from' => date('Y-m-d H:i:s', $parsed['validFrom_time_t']),
            'valid_to' => date('Y-m-d H:i:s', $parsed['validTo_time_t']),
        ];
    }
    
    /**
     * Sign Data (Hash then Encrypt)
     */
    public function signData(string $data, string $privateKeyPem, string $passphrase): ?string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem, $passphrase);
        if (!$privateKey) {
            throw new Exception("Invalid private key or passphrase");
        }

        if (openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
             return base64_encode($signature);
        }
        
        return null;
    }

    /**
     * Verify Signature
     */
    public function verifySignature(string $data, string $signatureBase64, string $publicKeyPem): bool
    {
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if (!$publicKey) { 
            // Try to extract public key if cert is passed
            $publicKey = openssl_pkey_get_public($publicKeyPem); 
            if (!$publicKey) throw new Exception("Invalid public key or certificate");
        }
        
        $signature = base64_decode($signatureBase64);
        
        $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        
        return $result === 1;
    }
    
    /**
     * Get Public Key from Certificate
     */
    public function getPublicKeyFromCert(string $certPem): string
    {
         $pubKey = openssl_pkey_get_public($certPem);
         $keyData = openssl_pkey_get_details($pubKey);
         return $keyData['key'];
    }
}
