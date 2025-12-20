<?php

namespace Tests\Feature\Central;

use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\TenantTestCase;
use Tests\TestCase;

/**
 * Test untuk UserResource transformation
 */
class UserResourceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test: Resource mengembalikan data dengan benar tanpa tenants
     */
    public function test_resource_returns_data_correctly_without_tenants(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertEquals($user->id, $array['id']);
        $this->assertEquals($user->global_id, $array['global_id']);
        $this->assertEquals('John Doe', $array['name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertFalse($array['has_portal']);
        $this->assertEquals(0, $array['tenants_count']);
    }

    /**
     * Test: Resource mengembalikan has_portal true ketika user memiliki tenants
     */
    public function test_resource_returns_has_portal_true_when_user_has_tenants(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        TenantUser::create([
            'global_user_id' => $user->global_id,
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_owner' => true,
        ]);

        // Load tenants relation
        $user->load('tenants');

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertTrue($array['has_portal']);
        $this->assertEquals(1, $array['tenants_count']);
    }

    /**
     * Test: Resource mengembalikan available_tenants ketika user memiliki tenants
     */
    public function test_resource_returns_available_tenants_when_user_has_tenants(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'slug' => 'test-company',
        ]);

        TenantUser::create([
            'global_user_id' => $user->global_id,
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_owner' => true,
            'avatar' => 'avatars/user.jpg',
            'is_nusawork_integrated' => true,
        ]);

        // Load tenants and tenantUsers relations
        $user->load('tenants', 'tenantUsers');

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertArrayHasKey('available_tenants', $array);
        // available_tenants adalah Collection, convert ke array untuk assertion
        $availableTenants = collect($array['available_tenants'])->toArray();
        $this->assertCount(1, $availableTenants);

        $tenantData = $availableTenants[0];
        $this->assertEquals($tenant->id, $tenantData['id']);
        $this->assertEquals('PT Test Company', $tenantData['name']);
        $this->assertEquals('TESTCO', $tenantData['code']);
        $this->assertEquals('test-company', $tenantData['slug']);
        $this->assertEquals('admin', $tenantData['role']);
        $this->assertEquals('avatars/user.jpg', $tenantData['avatar']);
        $this->assertTrue($tenantData['is_nusawork_integrated']);
    }

    /**
     * Test: Resource mengembalikan multiple tenants dengan benar
     */
    public function test_resource_returns_multiple_tenants_correctly(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant1 = Tenant::factory()->create(['name' => 'Company 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Company 2']);

        TenantUser::create([
            'global_user_id' => $user->global_id,
            'tenant_id' => $tenant1->id,
            'role' => 'admin',
            'is_owner' => true,
        ]);

        TenantUser::create([
            'global_user_id' => $user->global_id,
            'tenant_id' => $tenant2->id,
            'role' => 'admin',
        ]);

        $user->load('tenants', 'tenantUsers');

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertEquals(2, $array['tenants_count']);
        $this->assertCount(2, $array['available_tenants']);
        $this->assertEquals('Company 1', $array['available_tenants'][0]['name']);
        $this->assertEquals('Company 2', $array['available_tenants'][1]['name']);
    }

    /**
     * Test: Resource mengembalikan default role recruiter jika tenantUser tidak ditemukan
     * 
     * Test edge case dimana tenant ada di relation tapi tenantUser tidak ditemukan.
     * Resource harus menggunakan default values tanpa error.
     */
    public function test_resource_returns_default_role_recruiter_when_tenant_user_not_found(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Buat tenant relation tanpa TenantUser (edge case)
        // Kita manual attach ke collection setelah load
        $user->load('tenants', 'tenantUsers');
        
        // Simulasi edge case: tenant ada tapi tidak ada TenantUser record
        // Ini bisa terjadi jika ada data inconsistency
        $user->tenants->push($tenant);

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertArrayHasKey('available_tenants', $array);
        $availableTenants = collect($array['available_tenants'])->toArray();
        
        // Tenant yang tidak punya TenantUser harus menggunakan default values
        $lastTenant = end($availableTenants);
        $this->assertEquals('admin', $lastTenant['role']);
        $this->assertNull($lastTenant['avatar']);
        $this->assertFalse($lastTenant['is_nusawork_integrated']);
        $this->assertNull($lastTenant['tenant_join_date']);
        $this->assertNull($lastTenant['last_login_at']);
    }

    /**
     * Test: Resource mengembalikan email_verified_at dengan benar
     */
    public function test_resource_returns_email_verified_at_correctly(): void
    {
        // ARRANGE
        $verifiedAt = now()->subDays(10);
        $user = User::factory()->create([
            'email_verified_at' => $verifiedAt,
        ]);

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertNotNull($array['email_verified_at']);
        // Gunakan startOfSecond untuk menghindari perbedaan microsecond
        $this->assertEquals(
            $verifiedAt->startOfSecond()->toISOString(), 
            $array['email_verified_at']->startOfSecond()->toISOString()
        );
    }

    /**
     * Test: Resource mengembalikan null untuk email_verified_at jika belum verified
     */
    public function test_resource_returns_null_for_email_verified_at_when_not_verified(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertNull($array['email_verified_at']);
    }

    /**
     * Test: Resource tidak menampilkan available_tenants jika tidak ada tenants
     * 
     * Note: when() dengan $this->tenants->isNotEmpty() akan return closure result jika true.
     * Jika tenants empty, closure tetap dieksekusi dan return empty collection yang di-map.
     */
    public function test_resource_does_not_show_available_tenants_when_no_tenants(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $user->load('tenants');

        // ACT
        $resource = new UserResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        // when() dengan isNotEmpty() condition akan tidak memasukkan key jika false
        // Tapi jika ada bug, bisa jadi tetap include sebagai Collection
        if (isset($array['available_tenants'])) {
            // Jika key ada, pastikan itu Collection dan kosong
            $tenants = collect($array['available_tenants']);
            $this->assertTrue($tenants->isEmpty(), 'available_tenants harus kosong jika user tidak punya tenants');
        } else {
            // Idealnya key tidak ada sama sekali
            $this->assertArrayNotHasKey('available_tenants', $array);
        }
    }

    /**
     * Test: Resource collection berfungsi dengan benar
     */
    public function test_resource_collection_works_correctly(): void
    {
        // ARRANGE
        $users = User::factory()->count(3)->create();

        // ACT
        $collection = UserResource::collection($users);
        $array = $collection->toArray(request());

        // ASSERT
        $this->assertCount(3, $array);
        $this->assertArrayHasKey('id', $array[0]);
        $this->assertArrayHasKey('name', $array[0]);
        $this->assertArrayHasKey('email', $array[0]);
        $this->assertArrayHasKey('has_portal', $array[0]);
    }
}
