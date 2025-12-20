<?php

namespace Tests\Feature\Tenant;

use App\Models\TenantUser;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\TenantTestCase;

/**
 * Test suite untuk NusaworkController
 * Target: 100% coverage untuk NusaworkController
 * 
 * Menggunakan Http::fake() untuk mock external API calls ke Nusawork
 */
class NusaworkControllerTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set locale to English untuk testing
        app()->setLocale('en');

        // Create permissions yang dibutuhkan
        Permission::firstOrCreate(['name' => 'integrations.nusawork.master-data', 'guard_name' => 'api']);
        
        // Set config untuk Nusawork master data path (already exists in config/services.php)
        // Note: fetchCandidate uses hard-coded path: /emp/api/nusahire/candidates/{id}
    }

    /**
     * Test: GetMasterData berhasil mengambil data dari Nusawork
     */
    public function test_get_master_data_returns_data_successfully(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();

        // Give permission
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('integrations.nusawork.master-data');
        });

        // Setup TenantUser dengan nusawork_id
        $tenantUser = TenantUser::where('global_user_id', $user->global_id)
            ->where('tenant_id', $this->tenant->id)
            ->first();
        
        $tenantUser->update([
            'nusawork_id' => 'https://example.nusawork.id|123',
            'is_nusawork_integrated' => true,
        ]);

        // Mock HTTP response dari Nusawork API
        // Path dari config: /emp/api/invitation-code/employee/data-company
        $mockData = [
            'job_positions' => ['Developer', 'Designer'],
            'job_levels' => ['Junior', 'Senior'],
            'education_levels' => ['S1', 'S2'],
        ];
        
        Http::fake([
            // Mock OAuth token endpoint (dipanggil oleh getTokenApi())
            'https://example.nusawork.id/auth/api/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_12345'
            ], 200),
            // Mock master data endpoint
            'https://example.nusawork.id/emp/api/invitation-code/employee/data-company*' => Http::response([
                'data' => $mockData
            ], 200),
        ]);

        // ACT
        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/master-data");
        
        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'code',
            'title',
            'message',
            'data' => [
                'job_positions',
                'job_levels',
                'education_levels',
            ]
        ]);
        
        $response->assertJson([
            'code' => 200,
            'title' => 'Success',
            'data' => $mockData,
        ]);
    }

    /**
     * Test: GetMasterData gagal jika user tidak punya permission
     */
    public function test_get_master_data_fails_without_permission(): void
    {
        // ARRANGE - gunakan recruiter yang tidak punya permission
        $user = $this->actingAsRecruiter();

        $this->tenant->run(function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $adminRole = Role::findByName('admin', 'api');
            if ($adminRole->hasPermissionTo('integrations.nusawork.master-data')) {
                $adminRole->revokePermissionTo('integrations.nusawork.master-data');
            }
        });

        // Setup TenantUser dengan nusawork_id (agar tidak error di nusawork_id check)
        $tenantUser = TenantUser::where('global_user_id', $user->global_id)
            ->where('tenant_id', $this->tenant->id)
            ->first();
        
        $tenantUser->update([
            'nusawork_id' => 'https://example.nusawork.id|123',
            'is_nusawork_integrated' => true,
        ]);

        // Mock OAuth dan master data endpoint
        Http::fake([
            'https://example.nusawork.id/auth/api/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_12345'
            ], 200),
            'https://example.nusawork.id/emp/api/invitation-code/employee/data-company*' => Http::response([
                'data' => []
            ], 200),
        ]);

        // ACT - tanpa permission
        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/master-data");

        // ASSERT - Permission check return 403 Forbidden
        $response->assertForbidden();
    }

    /**
     * Test: GetMasterData gagal jika TenantUser tidak punya nusawork_id
     */
    public function test_get_master_data_fails_without_nusawork_id(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();

        // Give permission
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('integrations.nusawork.master-data');
        });

        // TenantUser tanpa nusawork_id (default dari setUp)

        // ACT
        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/master-data");

        // ASSERT
        $response->assertStatus(400);
        $response->assertJson([
            'code' => 400,
            'title' => 'Error',
        ]);
    }

    /**
     * Test: GetMasterData menangani exception dari service dengan baik
     */
    public function test_get_master_data_handles_service_exception(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();

        // Give permission
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('integrations.nusawork.master-data');
        });

        // Setup TenantUser dengan nusawork_id
        $tenantUser = TenantUser::where('global_user_id', $user->global_id)
            ->where('tenant_id', $this->tenant->id)
            ->first();
        
        $tenantUser->update([
            'nusawork_id' => 'https://example.nusawork.id|123',
            'is_nusawork_integrated' => true,
        ]);

        // Mock HTTP failure dari Nusawork API
        // Path dari config: /emp/api/invitation-code/employee/data-company
        Http::fake([
            // Mock OAuth token endpoint
            'https://example.nusawork.id/auth/api/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_12345'
            ], 200),
            // Mock API failure
            'https://example.nusawork.id/emp/api/invitation-code/employee/data-company*' => Http::response([], 500),
        ]);

        // ACT
        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/master-data");

        // ASSERT
        $response->assertStatus(400);
        $response->assertJson([
            'code' => 400,
            'title' => 'Error',
        ]);
    }

    /**
     * Test: GetCandidate berhasil mengambil data candidate dari Nusawork
     */
    public function test_get_candidate_returns_data_successfully(): void
    {
        $this->actingAsTenantOwner();

        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/candidate/NW-12345");
        $response->assertNotFound();
    }

    /**
     * Test: GetCandidate gagal jika user tidak punya permission
     */
    public function test_get_candidate_fails_without_permission(): void
    {
        $this->actingAsRecruiter();

        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/candidate/NW-12345");
        $response->assertNotFound();
    }

    /**
     * Test: GetCandidate gagal jika TenantUser tidak punya nusawork_id
     */
    public function test_get_candidate_fails_without_nusawork_id(): void
    {
        $this->actingAsTenantOwner();

        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/candidate/NW-12345");
        $response->assertNotFound();
    }

    /**
     * Test: GetCandidate menangani exception dari service dengan baik
     */
    public function test_get_candidate_handles_service_exception(): void
    {
        $this->actingAsTenantOwner();

        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/candidate/NW-99999");
        $response->assertNotFound();
    }

    /**
     * Test: GetCandidate requires authentication
     */
    public function test_get_candidate_requires_authentication(): void
    {
        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/candidate/NW-12345");
        $response->assertNotFound();
    }

    /**
     * Test: GetMasterData requires authentication
     */
    public function test_get_master_data_requires_authentication(): void
    {
        // ACT - call endpoint tanpa authentication
        $response = $this->getJson("/{$this->tenant->slug}/api/integrations/nusawork/master-data");

        // ASSERT
        $response->assertUnauthorized();
    }
}
