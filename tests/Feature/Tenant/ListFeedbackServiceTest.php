<?php

namespace Tests\Feature\Tenant;

use App\Services\Tenant\ListFeedbackService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk ListFeedbackService
 * 
 * Coverage target:
 * - Line 32-34: User/Tenant null check
 * - Line 40-53: Cache hit flow dengan cache_info
 * - Line 44: is_array() defensive check dalam cache hit
 * - Line 64-81: API success flow dengan cache_info
 * - Line 72: is_array() defensive check dalam API success
 * - Line 83-90: API failure flow
 * - Line 92-101: Exception handling
 */
class ListFeedbackServiceTest extends TenantTestCase
{
    protected ListFeedbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ListFeedbackService::class);
    }


    /**
     * Test: getPersonalFeedback returns cached data with cache_info when cache exists
     * Coverage: Line 40-53, Line 44
     */
    public function test_get_personal_feedback_returns_cached_data_with_cache_info(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            // Populate cache first
            Http::fake([
                '*script.google.com/*' => Http::response([
                    'status' => 'success',
                    'data' => [['feedback' => 'Test feedback']],
                ], 200)
            ]);

            // First call to populate cache
            $this->service->getPersonalFeedback();

            // Reset HTTP fake
            Http::fake();

            // ACT - Second call should return from cache
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertArrayHasKey('cache_info', $result);
            $this->assertTrue($result['cache_info']['from_cache']);
            $this->assertIsInt($result['cache_info']['cached_at']);
            $this->assertIsInt($result['cache_info']['expires_in']);
            $this->assertGreaterThan(0, $result['cache_info']['expires_in']);
        });
    }

    /**
     * Test: getPersonalFeedback returns cached data when cache_info already exists
     * Coverage: Line 44 (is_array check when cache_info already set)
     */
    public function test_get_personal_feedback_returns_cached_data_when_cache_info_exists(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            $cacheKey = "feedback_data_{$user->id}_{$this->tenant->id}";
            $cachedData = [
                'status' => 'success',
                'data' => [['feedback' => 'Cached']],
                'cache_info' => [
                    'from_cache' => true,
                    'cached_at' => time(),
                    'expires_in' => 600
                ]
            ];

            // Manually set cache
            Cache::put($cacheKey, $cachedData, now()->addMinutes(10));
            Cache::put("{$cacheKey}_timestamp", time(), now()->addMinutes(10));

            // ACT
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertArrayHasKey('cache_info', $result);
            // cache_info should not be duplicated
            $this->assertEquals($cachedData['cache_info'], $result['cache_info']);
        });
    }

    /**
     * Test: getPersonalFeedback ignores cache when forceRefresh is true
     * Coverage: Line 40 (forceRefresh condition)
     */
    public function test_get_personal_feedback_ignores_cache_when_force_refresh_true(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            $callCount = 0;
            Http::fake(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    return Http::response([
                        'status' => 'success',
                        'data' => [['feedback' => 'First call']],
                    ], 200);
                } else {
                    return Http::response([
                        'status' => 'success',
                        'data' => [['feedback' => 'Second call']],
                    ], 200);
                }
            });

            // First call to populate cache
            $this->service->getPersonalFeedback();

            // ACT - Force refresh
            $result = $this->service->getPersonalFeedback(forceRefresh: true);

            // ASSERT
            $this->assertIsArray($result);
            $this->assertFalse($result['cache_info']['from_cache']);
            $this->assertEquals('Second call', $result['data'][0]['feedback']);
            $this->assertEquals(2, $callCount);
        });
    }

    /**
     * Test: getPersonalFeedback returns API response with cache_info on success
     * Coverage: Line 64-81, Line 72
     */
    public function test_get_personal_feedback_returns_api_response_with_cache_info_on_success(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake([
                '*script.google.com/*' => Http::response([
                    'status' => 'success',
                    'data' => [
                        ['feedback' => 'Great product!'],
                        ['feedback' => 'Needs improvement'],
                    ],
                ], 200)
            ]);

            // ACT
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertArrayHasKey('cache_info', $result);
            $this->assertFalse($result['cache_info']['from_cache']);
            $this->assertIsInt($result['cache_info']['cached_at']);
            $this->assertEquals(600, $result['cache_info']['expires_in']); // 10 minutes * 60 seconds
            $this->assertCount(2, $result['data']);
        });
    }

    /**
     * Test: getPersonalFeedback caches data after successful API call
     * Coverage: Line 68-69 (Cache::put calls)
     */
    public function test_get_personal_feedback_caches_data_after_successful_api_call(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake([
                '*script.google.com/*' => Http::response([
                    'status' => 'success',
                    'data' => [['feedback' => 'Test']],
                ], 200)
            ]);

            // ACT
            $this->service->getPersonalFeedback();

            // ASSERT
            $cacheKey = "feedback_data_{$user->id}_{$this->tenant->id}";
            $this->assertTrue(Cache::has($cacheKey));
            $this->assertTrue(Cache::has("{$cacheKey}_timestamp"));
            
            $cachedData = Cache::get($cacheKey);
            $this->assertEquals('Test', $cachedData['data'][0]['feedback']);
        });
    }

    /**
     * Test: getPersonalFeedback handles API failure gracefully
     * Coverage: Line 83-90
     */
    public function test_get_personal_feedback_handles_api_failure_gracefully(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake([
                '*script.google.com/*' => Http::response([
                    'error' => 'Service unavailable'
                ], 503)
            ]);

            // ACT
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertEquals('error', $result['status']);
            $this->assertEquals('Gagal mengambil feedback dari Google Sheets.', $result['message']);
        });
    }

    /**
     * Test: getPersonalFeedback handles API 404 error
     * Coverage: Line 83-90 (different status code)
     */
    public function test_get_personal_feedback_handles_api_404_error(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake([
                '*script.google.com/*' => Http::response(['error' => 'Not found'], 404)
            ]);

            // ACT
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertEquals('error', $result['status']);
        });
    }

    /**
     * Test: getPersonalFeedback handles network exception gracefully
     * Coverage: Line 92-101
     */
    public function test_get_personal_feedback_handles_network_exception(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake(function () {
                throw new \Exception('Network timeout');
            });

            // ACT
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertEquals('error', $result['status']);
            $this->assertEquals('Terjadi kesalahan saat mengambil feedback.', $result['message']);
        });
    }

    /**
     * Test: getPersonalFeedback handles connection exception
     * Coverage: Line 92-101 (different exception)
     */
    public function test_get_personal_feedback_handles_connection_exception(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake(function () {
                throw new \RuntimeException('Connection refused');
            });

            // ACT
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertEquals('error', $result['status']);
        });
    }

    /**
     * Test: getPersonalFeedback sends correct parameters to API
     * Coverage: Line 59-62 (Http::get parameters)
     */
    public function test_get_personal_feedback_sends_correct_parameters_to_api(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake([
                '*script.google.com/*' => Http::response(['status' => 'success', 'data' => []], 200)
            ]);

            // ACT
            $this->service->getPersonalFeedback();

            // ASSERT
            Http::assertSent(function ($request) use ($user) {
                return str_contains($request->url(), 'script.google.com') &&
                       $request['q'] === $user->email &&
                       $request['tenant_name'] === $this->tenant->name;
            });
        });
    }

    /**
     * Test: getPersonalFeedback returns API response with cache_info already set
     * Coverage: Line 72 (is_array check when cache_info already in response)
     */
    public function test_get_personal_feedback_handles_api_response_with_cache_info(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $this->actingAsTenantOwner();
            Cache::flush();

            // API returns response with cache_info already set (edge case)
            Http::fake([
                '*script.google.com/*' => Http::response([
                    'status' => 'success',
                    'data' => [['feedback' => 'Test']],
                    'cache_info' => ['existing' => 'info'] // Already has cache_info
                ], 200)
            ]);

            // ACT
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $this->assertIsArray($result);
            $this->assertArrayHasKey('cache_info', $result);
            // Should not override existing cache_info
            $this->assertEquals(['existing' => 'info'], $result['cache_info']);
        });
    }

    /**
     * Test: getPersonalFeedback cache key is unique per user and tenant
     * Coverage: Line 37 (cache key generation)
     */
    public function test_get_personal_feedback_cache_key_is_unique_per_user_and_tenant(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake([
                '*script.google.com/*' => Http::response([
                    'status' => 'success',
                    'data' => [['feedback' => 'User specific']],
                ], 200)
            ]);

            // ACT
            $this->service->getPersonalFeedback();

            // ASSERT
            $expectedCacheKey = "feedback_data_{$user->id}_{$this->tenant->id}";
            $this->assertTrue(Cache::has($expectedCacheKey));
            
            $cachedData = Cache::get($expectedCacheKey);
            $this->assertEquals('User specific', $cachedData['data'][0]['feedback']);
        });
    }

    /**
     * Test: getPersonalFeedback cache expiration info is correct
     * Coverage: Line 48 (expires_in calculation)
     */
    public function test_get_personal_feedback_cache_expiration_info_is_correct(): void
    {
        $this->tenant->run(function () {
            // ARRANGE
            $user = $this->actingAsTenantOwner();
            Cache::flush();

            Http::fake([
                '*script.google.com/*' => Http::response([
                    'status' => 'success',
                    'data' => [],
                ], 200)
            ]);

            $beforeTime = time();
            $this->service->getPersonalFeedback();
            $afterTime = time();

            // ACT - Get from cache
            $result = $this->service->getPersonalFeedback();

            // ASSERT
            $expiresIn = $result['cache_info']['expires_in'];
            // Should be close to 600 seconds (10 minutes)
            $this->assertGreaterThan(595, $expiresIn);
            $this->assertLessThanOrEqual(600, $expiresIn);
        });
    }
}
