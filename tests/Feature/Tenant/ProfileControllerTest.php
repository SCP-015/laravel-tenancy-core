<?php

namespace Tests\Feature\Tenant;

use Tests\Feature\TenantTestCase;

/**
 * Test suite untuk ProfileController
 * Target: 100% coverage untuk ProfileController
 */
class ProfileControllerTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set locale to English untuk testing
        app()->setLocale('en');
    }

    /**
     * Test: Index endpoint mengembalikan data user dan portal dengan sukses
     */
    public function test_index_returns_user_and_portal_data_successfully(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->slug}/api/settings/profile");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'user' => [
                'id',
                'global_id',
                'name',
                'email',
            ],
            'portal' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                ]
            ]
        ]);

        // Verify response contains correct user data
        $response->assertJson([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'global_id' => $user->global_id,
                'email' => $user->email,
            ]
        ]);

        // Verify portal data contains current tenant
        $responseData = $response->json();
        $this->assertNotEmpty($responseData['portal']);
        
        // Check if current tenant is in the portal list
        $tenantSlugs = collect($responseData['portal'])->pluck('slug')->toArray();
        $this->assertContains($this->tenant->slug, $tenantSlugs);
    }

    /**
     * Test: Index endpoint requires authentication
     */
    public function test_index_requires_authentication(): void
    {
        // ACT - call endpoint tanpa authentication
        $response = $this->getJson("/{$this->tenant->slug}/api/settings/profile");

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: Index endpoint mengembalikan semua tenants yang dimiliki user
     */
    public function test_index_returns_all_user_tenants(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        // Buat tenant kedua dan assign ke user yang sama
        $tenant2 = \App\Models\Tenant::factory()->create([
            'owner_id' => $user->id,
        ]);

        // Add user ke tenant kedua (gunakan firstOrCreate untuk avoid duplicate)
        \App\Models\TenantUser::firstOrCreate(
            [
                'tenant_id' => $tenant2->id,
                'global_user_id' => $user->global_id,
            ],
            [
                'role' => 'super_admin',
                'is_owner' => true,
            ]
        );

        // ACT
        $response = $this->getJson("/{$this->tenant->slug}/api/settings/profile");

        // ASSERT
        $response->assertOk();
        
        $responseData = $response->json();
        
        // User harus punya 2 tenants
        $this->assertCount(2, $responseData['portal']);
        
        // Verify both tenants are in the response
        $tenantIds = collect($responseData['portal'])->pluck('id')->toArray();
        $this->assertContains($this->tenant->id, $tenantIds);
        $this->assertContains($tenant2->id, $tenantIds);
    }

    /**
     * Test: Index endpoint untuk user recruiter
     */
    public function test_index_works_for_recruiter_user(): void
    {
        // ARRANGE
        $recruiter = $this->actingAsRecruiter();

        // ACT
        $response = $this->getJson("/{$this->tenant->slug}/api/settings/profile");

        // ASSERT
        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'user' => [
                'id' => $recruiter->id,
                'email' => $recruiter->email,
            ]
        ]);
    }
}
