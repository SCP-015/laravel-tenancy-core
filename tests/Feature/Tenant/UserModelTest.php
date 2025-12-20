<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk Tenant User Model
 * 
 * Coverage target:
 * - Lines 41-66: getAllPermissions() method dan getPermissionNamesAttribute
 * - Line 106: getUserByAuth() fallback to request()->user()
 */
class UserModelTest extends TenantTestCase
{
    /**
     * Test: getAllPermissions() mengembalikan direct permissions
     * 
     * Cover lines 41-56: getAllPermissions() method
     */
    public function test_get_all_permissions_returns_direct_permissions(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        $tenantUser = User::where('global_id', $user->global_id)->first();

        // Clear existing permissions
        $tenantUser->syncPermissions([]);
        $tenantUser->syncRoles([]);

        // Create and assign direct permissions
        $permission1 = Permission::create(['name' => 'posts.view', 'guard_name' => 'api']);
        $permission2 = Permission::create(['name' => 'posts.create', 'guard_name' => 'api']);

        $tenantUser->givePermissionTo(['posts.view', 'posts.create']);

        // ACT
        $permissions = $tenantUser->getAllPermissions();

        // ASSERT
        $this->assertCount(2, $permissions);

        $permissionNames = $permissions->pluck('name')->toArray();
        $this->assertContains('posts.view', $permissionNames);
        $this->assertContains('posts.create', $permissionNames);
    }

    /**
     * Test: getAllPermissions() mengembalikan permissions dari roles
     * 
     * Cover lines 50-53: Role permissions mapping
     */
    public function test_get_all_permissions_returns_role_permissions(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        $tenantUser = User::where('global_id', $user->global_id)->first();

        // Clear existing
        $tenantUser->syncPermissions([]);
        $tenantUser->syncRoles([]);

        // Create role with permissions
        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);
        Permission::create(['name' => 'articles.edit', 'guard_name' => 'api']);
        Permission::create(['name' => 'articles.publish', 'guard_name' => 'api']);

        $role->givePermissionTo(['articles.edit', 'articles.publish']);
        $tenantUser->assignRole('editor');

        // ACT
        $permissions = $tenantUser->getAllPermissions();

        // ASSERT
        $this->assertGreaterThanOrEqual(2, $permissions->count());

        $permissionNames = $permissions->pluck('name')->toArray();
        $this->assertContains('articles.edit', $permissionNames);
        $this->assertContains('articles.publish', $permissionNames);
    }

    /**
     * Test: getAllPermissions() merges direct and role permissions (unique)
     * 
     * Cover line 56: Merge and unique
     */
    public function test_get_all_permissions_merges_direct_and_role_permissions(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        $tenantUser = User::where('global_id', $user->global_id)->first();

        $tenantUser->syncPermissions([]);
        $tenantUser->syncRoles([]);

        // Create permissions
        Permission::create(['name' => 'common.permission', 'guard_name' => 'api']);
        Permission::create(['name' => 'direct.permission', 'guard_name' => 'api']);
        Permission::create(['name' => 'role.permission', 'guard_name' => 'api']);

        // Give direct permission
        $tenantUser->givePermissionTo(['common.permission', 'direct.permission']);

        // Create role with overlapping permission
        $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
        $role->givePermissionTo(['common.permission', 'role.permission']);
        $tenantUser->assignRole('test_role');

        // ACT
        $permissions = $tenantUser->getAllPermissions();

        // ASSERT - Should have 3 unique permissions (common.permission counted once)
        $permissionNames = $permissions->pluck('name')->toArray();
        $this->assertContains('common.permission', $permissionNames);
        $this->assertContains('direct.permission', $permissionNames);
        $this->assertContains('role.permission', $permissionNames);

        // Verify uniqueness
        $this->assertEquals(3, $permissions->unique('id')->count());
    }

    /**
     * Test: getPermissionNamesAttribute returns permission names
     * 
     * Cover lines 64-66: getPermissionNamesAttribute accessor
     */
    public function test_get_permission_names_attribute_returns_names(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        $tenantUser = User::where('global_id', $user->global_id)->first();

        $tenantUser->syncPermissions([]);
        $tenantUser->syncRoles([]);

        Permission::create(['name' => 'users.view', 'guard_name' => 'api']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'api']);

        $tenantUser->givePermissionTo(['users.view', 'users.create']);

        // ACT
        $permissionNames = $tenantUser->permission_names;

        // ASSERT
        $this->assertIsObject($permissionNames); // Collection
        $this->assertCount(2, $permissionNames);
        $this->assertContains('users.view', $permissionNames->toArray());
        $this->assertContains('users.create', $permissionNames->toArray());
    }

    /**
     * Test: getUserByAuth() returns user when Auth::user() is available
     */
    public function test_get_user_by_auth_from_auth_user(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        $tenantUser = User::where('global_id', $user->global_id)->first();

        // ACT
        $result = User::getUserByAuth();

        // ASSERT
        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($tenantUser->global_id, $result->global_id);
    }
}
