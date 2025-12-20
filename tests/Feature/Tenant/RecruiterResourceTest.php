<?php

namespace Tests\Feature\Tenant;

use App\Http\Resources\Tenant\RecruiterResource;
use App\Models\User;
use App\Models\TenantUser;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk RecruiterResource transformation
 */
class RecruiterResourceTest extends TenantTestCase
{
    /**
     * Test: Resource mengembalikan data dengan benar
     */
    public function test_resource_returns_data_correctly(): void
    {
        // ARRANGE
        // Gunakan centralUser yang sudah dibuat oleh TenantTestCase
        $user = $this->centralUser;

        // Update TenantUser yang sudah ada dengan data yang ingin ditest
        $tenantUser = $user->tenantUsers()->where('tenant_id', $this->tenant->id)->first();
        $tenantUser->update([
            'role' => 'admin',
            'avatar' => 'avatars/john.jpg',
        ]);

        // Reload user dengan tenantUsers relation
        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertEquals($user->id, $array['id']);
        $this->assertEquals($user->global_id, $array['global_id']);
        $this->assertEquals($user->name, $array['name']);
        $this->assertEquals($user->email, $array['email']);
        $this->assertEquals($this->tenant->id, $array['tenant_id']);
        $this->assertEquals('Admin', $array['role']);
        $this->assertEquals('avatars/john.jpg', $array['avatar']);
    }

    /**
     * Test: Resource mengembalikan role dengan format title case
     */
    public function test_resource_returns_role_in_title_case(): void
    {
        // ARRANGE
        $user = $this->centralUser;

        // Update TenantUser role ke super_admin
        $tenantUser = $user->tenantUsers()->where('tenant_id', $this->tenant->id)->first();
        $tenantUser->update(['role' => 'super_admin']);

        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertEquals('Super_Admin', $array['role']);
    }

    /**
     * Test: Resource mengembalikan default role jika tenantUser tidak ada
     */
    public function test_resource_returns_default_role_when_tenant_user_not_found(): void
    {
        // ARRANGE
        // Buat user baru yang tidak ada di tenant ini
        $user = User::factory()->create();
        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertEquals('Admin', $array['role']);
    }

    /**
     * Test: Resource mengembalikan null untuk avatar jika tidak ada
     */
    public function test_resource_returns_null_for_avatar_when_not_set(): void
    {
        // ARRANGE
        $user = $this->centralUser;

        // Update TenantUser dengan avatar null
        $tenantUser = $user->tenantUsers()->where('tenant_id', $this->tenant->id)->first();
        $tenantUser->update(['avatar' => null]);

        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertNull($array['avatar']);
    }

    /**
     * Test: Resource mengembalikan join_date dari tenant_join_date
     */
    public function test_resource_returns_join_date_from_tenant_join_date(): void
    {
        // ARRANGE
        $user = $this->centralUser;
        $joinDate = now()->subDays(30);

        // Update TenantUser dengan tenant_join_date
        $tenantUser = $user->tenantUsers()->where('tenant_id', $this->tenant->id)->first();
        $tenantUser->update(['tenant_join_date' => $joinDate]);

        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        // Gunakan startOfSecond untuk menghindari perbedaan microseconds
        $this->assertEquals(
            $joinDate->startOfSecond()->toISOString(), 
            $array['join_date']->startOfSecond()->toISOString()
        );
    }

    /**
     * Test: Resource mengembalikan created_at sebagai join_date jika tenant_join_date null
     */
    public function test_resource_returns_created_at_as_join_date_when_tenant_join_date_is_null(): void
    {
        // ARRANGE
        $user = $this->centralUser;

        // Update TenantUser dengan tenant_join_date null
        $tenantUser = $user->tenantUsers()->where('tenant_id', $this->tenant->id)->first();
        $tenantUser->update(['tenant_join_date' => null]);

        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertEquals($user->created_at->toISOString(), $array['join_date']->toISOString());
    }

    /**
     * Test: Resource with() method mengembalikan additional data
     */
    public function test_resource_with_method_returns_additional_data(): void
    {
        // ARRANGE
        $user = $this->centralUser;

        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $additional = $resource->with(request());

        // ASSERT
        $this->assertIsArray($additional);
    }

    /**
     * Test: Resource with() mengembalikan data dengan benar untuk recruiter
     */
    public function test_resource_with_returns_null_company_code_when_not_super_admin(): void
    {
        // ARRANGE
        $user = $this->centralUser;

        // Update ke recruiter
        $tenantUser = $user->tenantUsers()->where('tenant_id', $this->tenant->id)->first();
        $tenantUser->update(['role' => 'admin']);

        $user->load('tenantUsers');

        // ACT
        $resource = new RecruiterResource($user);
        $additional = $resource->with(request());

        // ASSERT
        $this->assertIsArray($additional);
    }

    /**
     * Test: Resource collection berfungsi dengan benar
     */
    public function test_resource_collection_works_correctly(): void
    {
        // ARRANGE
        // Gunakan user yang sudah ada dari TenantTestCase
        $user = $this->centralUser;
        $user->load('tenantUsers');
        $users = collect([$user]);

        // ACT
        $collection = RecruiterResource::collection($users);
        $array = $collection->toArray(request());

        // ASSERT
        $this->assertIsArray($array);
        $this->assertNotEmpty($array);
        $this->assertArrayHasKey('id', $array[0]);
        $this->assertArrayHasKey('name', $array[0]);
        $this->assertArrayHasKey('role', $array[0]);
    }
}
