<?php

namespace Tests\Feature\Tenant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\TenantTestCase;

/**
 * Comprehensive test suite untuk ListFeedbackController
 * Target: 100% coverage untuk caching dan external API integration
 */
class ListFeedbackControllerTest extends TenantTestCase
{
    /**
     * Test: Index returns feedback from API on first call
     */
    public function test_index_returns_feedback_from_api_on_first_call(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        // Clear any existing cache
        Cache::flush();

        // Mock HTTP response from Google Sheets API
        Http::fake();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $response->assertOk();
        
        // Verify HTTP was called
        Http::assertSent(function ($request) use ($user) {
            return str_contains($request->url(), 'script.google.com') &&
                   $request['q'] === $user->email &&
                   $request['tenant_name'] === $this->tenant->name;
        });
    }

    /**
     * Test: Index returns cached feedback on subsequent calls
     */
    public function test_index_returns_cached_feedback_on_subsequent_calls(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        Cache::flush();

        // Mock HTTP for first call
        Http::fake([
            '*script.google.com/*' => Http::response([
                'status' => 'success',
                'data' => [
                    ['feedback' => 'Cached feedback'],
                ],
            ], 200)
        ]);

        // First call to populate cache
        $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // Reset HTTP fake to track second call
        Http::fake();

        // ACT - Second call should use cache
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('cache_info.from_cache', true);
        $response->assertJsonPath('data.0.feedback', 'Cached feedback');
        
        // Verify HTTP was NOT called for second request
        Http::assertNothingSent();
        
        // Verify Cache-Control header is set
        $response->assertHeader('Cache-Control');
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=', $cacheControl);
    }

    /**
     * Test: Index refreshes cache when refresh parameter is true
     */
    public function test_index_refreshes_cache_when_refresh_parameter_is_true(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        Cache::flush();

        // Setup HTTP fake once for both calls
        $callCount = 0;
        Http::fake(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return Http::response([
                    'status' => 'success',
                    'data' => [['feedback' => 'Old data']],
                ], 200);
            } else {
                return Http::response([
                    'status' => 'success',
                    'data' => [['feedback' => 'Fresh data']],
                ], 200);
            }
        });

        // First call to populate cache
        $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ACT - Call with refresh=true
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback?refresh=true");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('cache_info.from_cache', false);
        $response->assertJsonPath('data.0.feedback', 'Fresh data');
        
        // Verify HTTP was called twice (initial + refresh)
        $this->assertEquals(2, $callCount);
    }

    /**
     * Test: Index handles API failure gracefully
     */
    public function test_index_handles_api_failure_gracefully(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        Cache::flush();

        // Mock HTTP failure
        Http::fake([
            '*script.google.com/*' => Http::response([
                'error' => 'Service unavailable'
            ], 503)
        ]);

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $response->assertOk(); // Controller returns 200 even on API failure
        $response->assertJsonStructure([
            'status',
            'message',
        ]);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('message', 'Gagal mengambil feedback dari Google Sheets.');
    }

    /**
     * Test: Index handles network exception gracefully
     */
    public function test_index_handles_network_exception_gracefully(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        Cache::flush();

        // Mock HTTP to throw exception
        Http::fake(function () {
            throw new \Exception('Network timeout');
        });

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $response->assertOk(); // Controller catches exception and returns error response
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('message', 'Terjadi kesalahan saat mengambil feedback.');
    }

    /**
     * Test: Index sets correct cache control headers for cached response
     */
    public function test_index_sets_cache_control_headers_for_cached_response(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        Cache::flush();

        // First call to populate cache
        Http::fake([
            '*script.google.com/*' => Http::response([
                'status' => 'success',
                'data' => [],
            ], 200)
        ]);
        $this->getJson("/{$this->tenant->id}/api/my-feedback");

        Http::fake();

        // ACT - Second call from cache
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=', $cacheControl);
    }

    /**
     * Test: Index sets no-store cache control for fresh API response
     */
    public function test_index_sets_no_store_cache_control_for_fresh_response(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        Cache::flush();

        Http::fake([
            '*script.google.com/*' => Http::response([
                'status' => 'success',
                'data' => [],
            ], 200)
        ]);

        // ACT - First call (fresh from API)
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    /**
     * Test: Index returns empty array when user is not authenticated
     */
    public function test_index_returns_empty_when_user_not_authenticated(): void
    {
        // ARRANGE - No authentication
        Cache::flush();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: Index caches data per user and tenant
     */
    public function test_index_caches_data_per_user_and_tenant(): void
    {
        // ARRANGE
        $user1 = $this->actingAsTenantOwner();
        
        Cache::flush();

        Http::fake([
            '*script.google.com/*' => Http::response([
                'status' => 'success',
                'data' => [['feedback' => 'User 1 feedback']],
            ], 200)
        ]);

        // ACT - User 1 first call
        $response1 = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT - Verify cache key is specific to user and tenant
        $cacheKey = "feedback_data_{$user1->id}_{$this->tenant->id}";
        $this->assertTrue(Cache::has($cacheKey));
        
        $cachedData = Cache::get($cacheKey);
        $this->assertEquals('User 1 feedback', $cachedData['data'][0]['feedback']);
    }

    /**
     * Test: Index includes cache expiration info
     */
    public function test_index_includes_cache_expiration_info(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        Cache::flush();

        Http::fake([
            '*script.google.com/*' => Http::response([
                'status' => 'success',
                'data' => [],
            ], 200)
        ]);

        // First call
        $this->getJson("/{$this->tenant->id}/api/my-feedback");

        Http::fake();

        // ACT - Second call from cache
        $response = $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT
        $response->assertJsonStructure([
            'cache_info' => [
                'from_cache',
                'cached_at',
                'expires_in',
            ]
        ]);
        
        $cacheInfo = $response->json('cache_info');
        $this->assertTrue($cacheInfo['from_cache']);
        $this->assertIsInt($cacheInfo['cached_at']);
        $this->assertIsInt($cacheInfo['expires_in']);
        $this->assertGreaterThan(0, $cacheInfo['expires_in']);
        $this->assertLessThanOrEqual(600, $cacheInfo['expires_in']); // Max 10 minutes (600 seconds)
    }

    /**
     * Test: Index sends correct query parameters to Google Sheets API
     */
    public function test_index_sends_correct_query_parameters(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        Cache::flush();

        Http::fake([
            '*script.google.com/*' => Http::response(['status' => 'success', 'data' => []], 200)
        ]);

        // ACT
        $this->getJson("/{$this->tenant->id}/api/my-feedback");

        // ASSERT - Verify query parameters
        Http::assertSent(function ($request) use ($user) {
            return $request->hasHeader('User-Agent') &&
                   $request['q'] === $user->email &&
                   $request['tenant_name'] === $this->tenant->name;
        });
    }
}
