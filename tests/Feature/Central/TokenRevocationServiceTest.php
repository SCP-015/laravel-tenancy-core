<?php

namespace Tests\Feature\Central;

use App\Models\User;
use App\Services\TokenRevocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ======================================================================
 * Test untuk: TokenRevocationService
 * ======================================================================
 *
 * Service untuk revoke OAuth access tokens.
 * 
 * Methods yang di-test:
 * - revokeAllActiveTokens()       - Revoke semua token aktif
 * - revokeTokensForUsers($userIds) - Revoke token untuk user tertentu
 *
 * Coverage Target: 100%
 *
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/TokenRevocationServiceTest.php
 * ======================================================================
 */
class TokenRevocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TokenRevocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new TokenRevocationService();
    }

    /**
     * Helper: Buat access token secara manual di database
     */
    private function createAccessToken(int $userId, bool $revoked = false): string
    {
        $tokenId = 'test_token_' . uniqid();
        
        DB::table('oauth_access_tokens')->insert([
            'id' => $tokenId,
            'user_id' => $userId,
            'client_id' => 1,
            'name' => 'Test Token',
            'scopes' => '[]',
            'revoked' => $revoked,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        return $tokenId;
    }

    // ===================================================================================
    // TEST: revokeAllActiveTokens()
    // ===================================================================================

    /**
     * Test (Happy Path): Revoke semua token aktif
     */
    public function test_can_revoke_all_active_tokens(): void
    {
        // ARRANGE
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Buat access tokens untuk kedua user
        $this->createAccessToken($user1->id, false);
        $this->createAccessToken($user2->id, false);

        // Verifikasi token dibuat dan aktif
        $activeTokensBefore = DB::table('oauth_access_tokens')
            ->where('revoked', false)
            ->count();
        $this->assertEquals(2, $activeTokensBefore);

        // ACT
        $revokedCount = $this->service->revokeAllActiveTokens();

        // ASSERT
        $this->assertEquals(2, $revokedCount);

        // Verifikasi semua token sekarang revoked
        $activeTokensAfter = DB::table('oauth_access_tokens')
            ->where('revoked', false)
            ->count();
        $this->assertEquals(0, $activeTokensAfter);

        $revokedTokensAfter = DB::table('oauth_access_tokens')
            ->where('revoked', true)
            ->count();
        $this->assertEquals(2, $revokedTokensAfter);
    }

    /**
     * Test (Edge Case): Revoke ketika tidak ada token aktif
     */
    public function test_revoke_all_active_tokens_returns_zero_when_no_active_tokens(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        
        // Buat token yang sudah revoked
        $this->createAccessToken($user->id, true);

        // ACT
        $revokedCount = $this->service->revokeAllActiveTokens();

        // ASSERT
        $this->assertEquals(0, $revokedCount);
    }

    /**
     * Test (Edge Case): Revoke ketika tidak ada token sama sekali
     */
    public function test_revoke_all_active_tokens_returns_zero_when_no_tokens_exist(): void
    {
        // ARRANGE - Tidak ada user dan token

        // ACT
        $revokedCount = $this->service->revokeAllActiveTokens();

        // ASSERT
        $this->assertEquals(0, $revokedCount);
    }

    // ===================================================================================
    // TEST: revokeTokensForUsers()
    // ===================================================================================

    /**
     * Test (Happy Path): Revoke token untuk user tertentu
     */
    public function test_can_revoke_tokens_for_specific_users(): void
    {
        // ARRANGE
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Buat tokens untuk semua user
        $this->createAccessToken($user1->id, false);
        $this->createAccessToken($user2->id, false);
        $this->createAccessToken($user3->id, false);

        // ACT - Revoke hanya untuk user1 dan user2
        $revokedCount = $this->service->revokeTokensForUsers([$user1->id, $user2->id]);

        // ASSERT
        $this->assertEquals(2, $revokedCount);

        // Verifikasi user1 dan user2 tokens revoked
        $user1Revoked = DB::table('oauth_access_tokens')
            ->where('user_id', $user1->id)
            ->where('revoked', true)
            ->count();
        $this->assertEquals(1, $user1Revoked);

        $user2Revoked = DB::table('oauth_access_tokens')
            ->where('user_id', $user2->id)
            ->where('revoked', true)
            ->count();
        $this->assertEquals(1, $user2Revoked);

        // Verifikasi user3 token masih aktif
        $user3Active = DB::table('oauth_access_tokens')
            ->where('user_id', $user3->id)
            ->where('revoked', false)
            ->count();
        $this->assertEquals(1, $user3Active);
    }

    /**
     * Test (Edge Case): Revoke dengan array kosong
     */
    public function test_revoke_tokens_for_users_returns_zero_for_empty_array(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $this->createAccessToken($user->id, false);

        // ACT
        $revokedCount = $this->service->revokeTokensForUsers([]);

        // ASSERT
        $this->assertEquals(0, $revokedCount);

        // Verifikasi token masih aktif
        $activeTokens = DB::table('oauth_access_tokens')
            ->where('revoked', false)
            ->count();
        $this->assertEquals(1, $activeTokens);
    }

    /**
     * Test (Edge Case): Revoke untuk user yang tidak punya token
     */
    public function test_revoke_tokens_for_users_with_no_tokens(): void
    {
        // ARRANGE
        $user1 = User::factory()->create(); // User dengan token
        $user2 = User::factory()->create(); // User tanpa token
        
        $this->createAccessToken($user1->id, false);

        // ACT - Revoke untuk user2 yang tidak punya token
        $revokedCount = $this->service->revokeTokensForUsers([$user2->id]);

        // ASSERT
        $this->assertEquals(0, $revokedCount);
    }

    /**
     * Test (Edge Case): Revoke untuk user yang tokennya sudah revoked
     */
    public function test_revoke_tokens_for_users_with_already_revoked_tokens(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        
        // Buat token yang sudah revoked
        $this->createAccessToken($user->id, true);

        // ACT
        $revokedCount = $this->service->revokeTokensForUsers([$user->id]);

        // ASSERT
        $this->assertEquals(0, $revokedCount);
    }

    /**
     * Test (Edge Case): Revoke untuk user dengan multiple tokens
     */
    public function test_can_revoke_multiple_tokens_for_single_user(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // Buat 3 tokens untuk user yang sama
        $this->createAccessToken($user->id, false);
        $this->createAccessToken($user->id, false);
        $this->createAccessToken($user->id, false);

        // ACT
        $revokedCount = $this->service->revokeTokensForUsers([$user->id]);

        // ASSERT
        $this->assertEquals(3, $revokedCount);

        // Verifikasi semua token user ini revoked
        $userRevokedTokens = DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->where('revoked', true)
            ->count();
        $this->assertEquals(3, $userRevokedTokens);
    }

    /**
     * Test (Integration): Revoke untuk beberapa user dengan mixed scenarios
     */
    public function test_revoke_tokens_for_users_mixed_scenarios(): void
    {
        // ARRANGE
        $user1 = User::factory()->create(); // 2 tokens aktif
        $user2 = User::factory()->create(); // 1 token aktif
        $user3 = User::factory()->create(); // Token sudah revoked
        $user4 = User::factory()->create(); // Tidak punya token

        // User1: 2 tokens
        $this->createAccessToken($user1->id, false);
        $this->createAccessToken($user1->id, false);

        // User2: 1 token
        $this->createAccessToken($user2->id, false);

        // User3: 1 token (sudah revoked)
        $this->createAccessToken($user3->id, true);

        // User4: tidak ada token

        // ACT - Revoke untuk semua user
        $revokedCount = $this->service->revokeTokensForUsers([
            $user1->id,
            $user2->id,
            $user3->id,
            $user4->id
        ]);

        // ASSERT
        // Hanya user1 (2) + user2 (1) = 3 tokens yang di-revoke
        $this->assertEquals(3, $revokedCount);
    }
}
