<?php

namespace Tests\Feature\Central;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ======================================================================
 * Test untuk: User Model
 * ======================================================================
 *
 * Model untuk central users.
 * 
 * Coverage Target: 100% (dari 88.9%)
 * Missing Lines: 86, 135, 159
 *
 * Test ini fokus untuk cover methods yang belum ter-cover.
 * 
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/Central/UserModelTest.php
 * ======================================================================
 */
class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: getCentralModelName() returns correct class name (Line 86)
     */
    public function test_get_central_model_name_returns_correct_class(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // ACT
        $centralModelName = $user->getCentralModelName();

        // ASSERT - Line 86: return static::class;
        $this->assertEquals(User::class, $centralModelName);
    }

    /**
     * Test: isSuperAdmin() returns true when user is super_admin (Line 135)
     */
    public function test_is_super_admin_returns_true_for_super_admin(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        
        // Create tenant user with super_admin role
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'global_user_id' => $user->global_id,
            'role' => 'super_admin',
        ]);

        // ACT
        $isSuperAdmin = $user->isSuperAdmin($tenant->id);

        // ASSERT - Line 135
        $this->assertTrue($isSuperAdmin);
    }

    /**
     * Test: isSuperAdmin() returns false when user is not super_admin
     */
    public function test_is_super_admin_returns_false_for_recruiter(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        
        // Create tenant user with recruiter role (not super_admin)
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'global_user_id' => $user->global_id,
            'role' => 'admin',
        ]);

        // ACT
        $isSuperAdmin = $user->isSuperAdmin($tenant->id);

        // ASSERT
        $this->assertFalse($isSuperAdmin);
    }

    /**
     * Test: getTenantUser() returns first tenant user when tenantId is null (Line 159)
     */
    public function test_get_tenant_user_returns_first_when_tenant_id_null(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        // Create multiple tenant users
        $tenantUser1 = TenantUser::create([
            'tenant_id' => $tenant1->id,
            'global_user_id' => $user->global_id,
            'role' => 'super_admin',
        ]);
        
        TenantUser::create([
            'tenant_id' => $tenant2->id,
            'global_user_id' => $user->global_id,
            'role' => 'admin',
        ]);

        // ACT - Call without tenantId (Line 159)
        $result = $user->getTenantUser(null);

        // ASSERT - Should return first tenant user
        $this->assertNotNull($result);
        $this->assertEquals($tenantUser1->id, $result->id);
    }

    /**
     * Test: getTenantUser() returns specific tenant user when tenantId provided
     */
    public function test_get_tenant_user_returns_specific_tenant_user(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        TenantUser::create([
            'tenant_id' => $tenant1->id,
            'global_user_id' => $user->global_id,
            'role' => 'super_admin',
        ]);
        
        $tenantUser2 = TenantUser::create([
            'tenant_id' => $tenant2->id,
            'global_user_id' => $user->global_id,
            'role' => 'admin',
        ]);

        // ACT - Get specific tenant user
        $result = $user->getTenantUser($tenant2->id);

        // ASSERT
        $this->assertNotNull($result);
        $this->assertEquals($tenantUser2->id, $result->id);
        $this->assertEquals('admin', $result->role);
    }

    /**
     * Test: getRole() returns correct role for tenant
     */
    public function test_get_role_returns_correct_role(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'global_user_id' => $user->global_id,
            'role' => 'super_admin',
        ]);

        // ACT
        $role = $user->getRole($tenant->id);

        // ASSERT
        $this->assertEquals('super_admin', $role);
    }

    /**
     * Test: isRecruiter() returns true when user is recruiter
     */
    public function test_is_recruiter_returns_true_for_recruiter(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'global_user_id' => $user->global_id,
            'role' => 'admin',
        ]);

        // ACT
        $isRecruiter = $user->isRecruiter($tenant->id);

        // ASSERT
        $this->assertTrue($isRecruiter);
    }

    /**
     * Test: isRecruiter() returns false when user is super_admin
     */
    public function test_is_recruiter_returns_false_for_super_admin(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'global_user_id' => $user->global_id,
            'role' => 'super_admin',
        ]);

        // ACT
        $isRecruiter = $user->isRecruiter($tenant->id);

        // ASSERT
        $this->assertFalse($isRecruiter);
    }

    /**
     * Test: Synced attribute names includes required fields
     */
    public function test_synced_attribute_names_includes_required_fields(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // ACT
        $syncedAttributes = $user->getSyncedAttributeNames();

        // ASSERT
        $this->assertContains('global_id', $syncedAttributes);
        $this->assertContains('name', $syncedAttributes);
        $this->assertContains('password', $syncedAttributes);
        $this->assertContains('email', $syncedAttributes);
    }

    /**
     * Test: Global identifier key name is correct
     */
    public function test_global_identifier_key_name_is_correct(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // ACT
        $keyName = $user->getGlobalIdentifierKeyName();

        // ASSERT
        $this->assertEquals('global_id', $keyName);
    }

    /**
     * Test: Tenant model name is correct
     */
    public function test_tenant_model_name_is_correct(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // ACT
        $tenantModelName = $user->getTenantModelName();

        // ASSERT
        $this->assertEquals(\App\Models\Tenant\User::class, $tenantModelName);
    }
}
