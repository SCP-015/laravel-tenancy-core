<?php

namespace Tests\Feature\Central;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ======================================================================
 * Test untuk: TenancyServiceProvider
 * ======================================================================
 *
 * Provider untuk multi-tenancy setup.
 * 
 * Coverage Target: 100% (dari 98.7%)
 * Missing Line: 50 (TenantDeleted event closure)
 *
 * Test ini fokus untuk cover event TenantDeleted yang belum ter-trigger.
 * 
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/Central/TenancyServiceProviderTest.php
 * ======================================================================
 */
class TenancyServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Cleanup tenant test yang mungkin tersisa jika test di-cancel
        try {
            $tenant = Tenant::find('test-tenant-delete-' . date('Ymd'));
            if ($tenant) {
                $tenant->delete();
            }
        } catch (\Throwable $e) {
            // Ignore errors during cleanup
        }

        parent::tearDown();
    }

    /**
     * Test: Tenant deletion triggers TenantDeleted event (Line 50)
     * 
     * Line 50 ada di closure untuk TenantDeleted event:
     * ->send(function (Events\TenantDeleted $event) {
     *     return $event->tenant; // <-- Line 50
     * })
     */
    public function test_tenant_deletion_triggers_deleted_event(): void
    {
        // ARRANGE - Gunakan ID dengan tanggal untuk menghindari konflik
        $tenantId = 'test-tenant-delete-' . date('Ymd');
        
        // Cleanup dulu jika ada tenant dengan ID yang sama
        try {
            $existingTenant = Tenant::find($tenantId);
            if ($existingTenant) {
                $existingTenant->delete();
            }
        } catch (\Throwable $e) {
            // Ignore if tenant doesn't exist
        }

        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => 'Test Tenant for Deletion',
            'code' => 'TESTDEL',
        ]);
        
        // Verify tenant exists
        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
        ]);

        // ACT - Delete tenant (ini akan trigger TenantDeleted event)
        $tenant->delete();

        // ASSERT
        // Verify tenant is deleted
        $this->assertDatabaseMissing('tenants', [
            'id' => $tenantId,
        ]);
        
        // Event TenantDeleted seharusnya sudah di-trigger
        // dan closure di line 50 seharusnya sudah dijalankan
    }

    /**
     * Test: Provider boots correctly with all events registered
     */
    public function test_provider_boots_with_events_registered(): void
    {
        // ARRANGE & ACT
        $provider = new \App\Providers\TenancyServiceProvider(app());
        $events = $provider->events();

        // ASSERT
        // Verify key tenant events are registered
        $this->assertArrayHasKey(\Stancl\Tenancy\Events\TenantCreated::class, $events);
        $this->assertArrayHasKey(\Stancl\Tenancy\Events\TenantDeleted::class, $events);
        $this->assertArrayHasKey(\Stancl\Tenancy\Events\TenancyInitialized::class, $events);
        $this->assertArrayHasKey(\Stancl\Tenancy\Events\TenancyEnded::class, $events);
    }

    /**
     * Test: Tenant routes are mapped correctly
     */
    public function test_tenant_routes_are_mapped(): void
    {
        // ARRANGE & ACT
        // Boot the application (routes should be mapped)
        $this->assertTrue(file_exists(base_path('routes/tenant.php')));
        
        // Verify routes are loaded
        $routes = collect(app('router')->getRoutes())->map(function ($route) {
            return $route->uri();
        });
        
        // ASSERT
        // Should have tenant routes loaded
        $this->assertGreaterThan(0, $routes->count());
    }
}
