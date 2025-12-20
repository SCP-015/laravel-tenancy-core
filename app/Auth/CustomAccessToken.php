<?php

namespace App\Auth;

use App\Models\User;
use App\Services\SsoTokenService;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;

/**
 * Class CustomAccessToken
 *
 * Meng-override class AccessToken bawaan Laravel Passport untuk
 * menambahkan custom claims ke dalam JWT (JSON Web Token).
 * 
 * This class is excluded from code coverage because it:
 * - Requires OAuth/JWT infrastructure setup
 * - Handles complex token generation with RSA keys
 * - Is better tested through integration/E2E tests
 * 
 * @codeCoverageIgnore
 */
class CustomAccessToken extends \Laravel\Passport\Bridge\AccessToken
{
    /**
     * @var CryptKey Kunci privat yang digunakan untuk menandatangani token.
     */
    private $privateKey;

    /**
     * Menyimpan object kunci privat yang akan digunakan.
     * Method ini dipanggil secara internal oleh Passport.
     */
    public function setPrivateKey(CryptKey $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    /**
     * Mengonversi object token menjadi representasi string (JWT).
     * Method magis ini dipanggil saat object token perlu ditampilkan sebagai string.
     */
    public function __toString()
    {
        try {
            // Panggil method kustom kita untuk membuat JWT.
            return $this->convertToJWT($this->privateKey)->toString();
        } catch (\Exception $e) {
            // Method __toString() tidak boleh melempar Exception.
            // Jika terjadi error, kembalikan string kosong dan catat error jika perlu.
            // error_log('JWT Generation Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Membuat JWT menggunakan library lcobucci/jwt dengan claims kustom.
     *
     * @param CryptKey $privateKey
     * @return \Lcobucci\JWT\Token
     */
    public function convertToJWT(CryptKey $privateKey)
    {
        // CATATAN PENTING (Solusi Final):
        // 1. `$privateKey->getKeyContents()` digunakan untuk membaca isi file kunci privat secara langsung.
        // 2. `(string) $privateKey->getPassPhrase()` adalah kunci utama untuk mengatasi error `SensitiveParameterValue`
        //    di versi PHP/Laravel yang lebih baru. Ini memastikan passphrase selalu berupa string.
        // 3. `InMemory::plainText()` digunakan karena kita sudah memiliki konten kuncinya, bukan lagi path filenya.
        $signingKey = InMemory::plainText(
            $privateKey->getKeyContents(),
            (string) $privateKey->getPassPhrase()
        );

        // Konfigurasi signer untuk algoritma asimetris (RS256).
        // Parameter ketiga (kunci verifikasi) bisa dikosongkan saat kita hanya membuat token.
        // Ambil kunci publik dari konfigurasi/env atau file default Passport untuk menghindari hard-coded credential.
        $publicKeyContent = config('passport.public_key');
        if (empty($publicKeyContent)) {
            $keyPath = config('passport.key_path', 'storage');
            $publicKeyFile = $keyPath === 'storage'
                ? storage_path('oauth-public.key')
                : rtrim($keyPath, '/') . '/oauth-public.key';

            if (is_readable($publicKeyFile)) {
                $publicKeyContent = file_get_contents($publicKeyFile);
            }
        }

        $verificationKey = !empty($publicKeyContent)
            ? InMemory::plainText($publicKeyContent)
            : $signingKey; // fallback aman tanpa kredensial hard-coded

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            $signingKey,
            $verificationKey
        );

        $issuer = SsoTokenService::getIssuer();
        $now = new DateTimeImmutable();

        // Membangun token dengan semua claims yang dibutuhkan.
        $builder = $config->builder()
            // Menambahkan claim 'iss' (Issuer)
            ->issuedBy($issuer)
            // Menambahkan claims standar JWT dari Passport
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($this->getExpiryDateTime())
            ->withClaim('scopes', $this->getScopes());

        // Menambahkan claims kustom jika token ini berasosiasi dengan seorang user.
        if ($this->getUserIdentifier()) {
            $builder = $builder->relatedTo((string) $this->getUserIdentifier());

            if ($user = User::find($this->getUserIdentifier())) {
                // Ambil tenant user untuk mendapatkan nusawork ID
                $tenantUser = \App\Models\TenantUser::where('global_user_id', $user->global_id)
                    ->first();
                    
                $nusaworkId = $tenantUser ? $tenantUser->getUserIdNusawork() : null;

                $builder = $builder->withClaim('uid', $nusaworkId)
                    ->withClaim('uid_nusahire', $user->id)
                    ->withClaim('email', $user->email);
            }
        }

        // Mengembalikan object token yang sudah final dan siap digunakan.
        return $builder->getToken($config->signer(), $config->signingKey());
    }
}
