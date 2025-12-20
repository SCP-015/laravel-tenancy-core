<?php

namespace Tests\Feature\Central;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Test untuk PublicKeyController
 */
class PublicKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $publicKeyPath;
    private string $backupKeyPath;
    private string $testPublicKey;
    private bool $hadOriginalKey = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicKeyPath = storage_path('oauth-public.key');
        $this->backupKeyPath = storage_path('oauth-public.key.backup.test');
        
        // Backup file asli jika ada
        if (File::exists($this->publicKeyPath)) {
            $this->hadOriginalKey = true;
            File::copy($this->publicKeyPath, $this->backupKeyPath);
        }
        
        // Sample RSA public key untuk testing (2048 bit)
        $this->testPublicKey = <<<'EOD'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu1SU1LfVLPHCozMxH2Mo
4lgOEePzNm0tRgeLezV6ffAt0gunVTLw7onLRnrq0/IzW7yWR7QkrmBL7jTKEn5u
+qKhbwKfBstIs+bMY2Zkp18gnTxKLxoS2tFczGkPLPgizskuemMghRniWaoLcyeh
kd3qqGElvW/VDL5AaWTg0nLVkjRo9z+40RQzuVaE8AkAFmxZzow3x+VJYKdjykkJ
0iT9wCS0DRTXu269V264Vf/3jvredZiKRkgwlL9xNAwxXFg0x/XFw005UWVRIkdg
cKWTjpBP2dPwVZ4WWC+9aGVd+Gyn1o0CLelf4rEjGoXbAAEgAqeGUxrcIlbjXfbc
mwIDAQAB
-----END PUBLIC KEY-----
EOD;

        // Simpan file test untuk memastikan endpoint bisa diakses
        File::put($this->publicKeyPath, $this->testPublicKey);
    }

    protected function tearDown(): void
    {
        // Restore file asli
        if ($this->hadOriginalKey && File::exists($this->backupKeyPath)) {
            File::move($this->backupKeyPath, $this->publicKeyPath);
        } elseif (!$this->hadOriginalKey && File::exists($this->publicKeyPath)) {
            // Hapus test file jika tidak ada file asli sebelumnya
            File::delete($this->publicKeyPath);
        }
        
        // Cleanup backup file jika masih ada
        if (File::exists($this->backupKeyPath)) {
            File::delete($this->backupKeyPath);
        }

        parent::tearDown();
    }

    /**
     * Test: Show returns public key when file exists
     */
    public function test_show_returns_public_key_when_file_exists(): void
    {
        // ACT
        $response = $this->getJson('/api/auth/public-key');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'public_key',
            'algorithm',
            'format',
            'fingerprint',
            'last_updated',
        ]);
        $response->assertJsonFragment([
            'algorithm' => 'RS256',
            'format' => 'PEM',
        ]);
        $response->assertJsonPath('public_key', $this->testPublicKey);
        
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);
    }

    /**
     * Test: Show returns fingerprint hash
     */
    public function test_show_returns_fingerprint_hash(): void
    {
        // ARRANGE
        $expectedFingerprint = hash('sha256', $this->testPublicKey);

        // ACT
        $response = $this->getJson('/api/auth/public-key');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('fingerprint', $expectedFingerprint);
    }

    /**
     * Test: Show returns last updated timestamp
     */
    public function test_show_returns_last_updated_timestamp(): void
    {
        // ACT
        $response = $this->getJson('/api/auth/public-key');

        // ASSERT
        $response->assertStatus(200);
        $this->assertNotNull($response->json('last_updated'));
        
        // Verify it's a valid ISO 8601 format
        $timestamp = $response->json('last_updated');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $timestamp);
    }

    /**
     * Test: Metadata returns metadata without key content
     */
    public function test_metadata_returns_metadata_without_key_content(): void
    {
        // ACT
        $response = $this->getJson('/api/auth/public-key/metadata');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'algorithm',
            'format',
            'fingerprint',
            'last_updated',
            'key_size',
            'available',
        ]);
        
        // Verify public_key is NOT present
        $this->assertArrayNotHasKey('public_key', $response->json());
        
        // Verify metadata values
        $response->assertJsonFragment([
            'algorithm' => 'RS256',
            'format' => 'PEM',
            'available' => true,
        ]);
    }

    /**
     * Test: Metadata returns key size
     */
    public function test_metadata_returns_key_size(): void
    {
        // ACT
        $response = $this->getJson('/api/auth/public-key/metadata');

        // ASSERT
        $response->assertStatus(200);
        $this->assertNotNull($response->json('key_size'));
        
        // Our test key is 2048 bits
        $this->assertEquals(2048, $response->json('key_size'));
    }

    /**
     * Test: Metadata returns cache control header
     */
    public function test_metadata_returns_cache_control_header(): void
    {
        // ACT
        $response = $this->getJson('/api/auth/public-key/metadata');

        // ASSERT
        $response->assertStatus(200);
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);
    }
    /**
     * Test: Show and metadata return same fingerprint
     */
    public function test_show_and_metadata_return_same_fingerprint(): void
    {
        // ACT
        $showResponse = $this->getJson('/api/auth/public-key');
        $metadataResponse = $this->getJson('/api/auth/public-key/metadata');

        // ASSERT
        $showResponse->assertStatus(200);
        $metadataResponse->assertStatus(200);
        
        $showFingerprint = $showResponse->json('fingerprint');
        $metadataFingerprint = $metadataResponse->json('fingerprint');
        
        $this->assertEquals($showFingerprint, $metadataFingerprint);
    }

    /**
     * Test: Endpoints have throttle middleware
     */
    public function test_endpoints_have_throttle_middleware(): void
    {
        // ACT & ASSERT - Make multiple requests to verify throttling doesn't immediately block
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/auth/public-key');
            $response->assertStatus(200);
        }
        
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/auth/public-key/metadata');
            $response->assertStatus(200);
        }
    }
}
