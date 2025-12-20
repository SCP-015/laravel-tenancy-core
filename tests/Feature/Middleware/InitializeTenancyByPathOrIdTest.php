<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\InitializeTenancyByPathOrId;
use App\Models\Tenant;
use App\Models\TenantSlugHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class InitializeTenancyByPathOrIdTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            if (function_exists('tenancy')) {
                tenancy()->end();
            }
        } catch (\Throwable $e) {
        }

        parent::tearDown();
    }

    public function test_request_with_old_slug_redirects_to_current_slug_when_toggle_enabled(): void
    {
        Route::middleware(['web', InitializeTenancyByPathOrId::class])->get('{tenant}/mw-test', function () {
            return response('OK');
        });

        $tenant = Tenant::factory()->create([
            'slug' => 'new-slug-mw',
            'enable_slug_history_redirect' => true,
        ]);

        TenantSlugHistory::create([
            'tenant_id' => $tenant->id,
            'slug' => 'old-slug-mw',
        ]);

        $response = $this->get('/old-slug-mw/mw-test');

        $response->assertStatus(302);
        $response->assertRedirect('/new-slug-mw/mw-test');
    }

    public function test_request_with_old_slug_redirects_to_current_slug_and_preserves_query_string(): void
    {
        Route::middleware(['web', InitializeTenancyByPathOrId::class])->get('{tenant}/mw-test-query', function () {
            return response('OK');
        });

        $tenant = Tenant::factory()->create([
            'slug' => 'new-slug-mw-query',
            'enable_slug_history_redirect' => true,
        ]);

        TenantSlugHistory::create([
            'tenant_id' => $tenant->id,
            'slug' => 'old-slug-mw-query',
        ]);

        $response = $this->get('/old-slug-mw-query/mw-test-query?foo=bar&baz=1');

        $response->assertStatus(302);

        $location = (string) $response->headers->get('Location');
        $this->assertTrue(Str::contains($location, '/new-slug-mw-query/mw-test-query'));

        $parts = parse_url($location);
        $this->assertSame('/new-slug-mw-query/mw-test-query', $parts['path'] ?? null);

        $query = [];
        parse_str($parts['query'] ?? '', $query);
        $this->assertEquals(['foo' => 'bar', 'baz' => '1'], $query);
    }

    public function test_request_with_current_slug_passes_through_and_returns_response(): void
    {
        Route::middleware(['web', InitializeTenancyByPathOrId::class])->get('{tenant}/mw-test-pass', function () {
            return response('PONG');
        });

        $tenant = Tenant::factory()->create([
            'slug' => 'current-slug-mw',
        ]);

        if (function_exists('tenancy')) {
            tenancy()->initialize($tenant);
        }

        $response = $this->get('/' . $tenant->slug . '/mw-test-pass');

        $response->assertStatus(200);
        $response->assertSee('PONG');
    }

    public function test_request_with_old_slug_returns_404_when_toggle_disabled(): void
    {
        Route::middleware(['web', InitializeTenancyByPathOrId::class])->get('{tenant}/mw-test-disabled', function () {
            return response('OK');
        });

        $tenant = Tenant::factory()->create([
            'slug' => 'new-slug-mw-disabled',
            'enable_slug_history_redirect' => false,
        ]);

        TenantSlugHistory::create([
            'tenant_id' => $tenant->id,
            'slug' => 'old-slug-mw-disabled',
        ]);

        $response = $this->get('/old-slug-mw-disabled/mw-test-disabled');

        $response->assertStatus(404);
    }
}
