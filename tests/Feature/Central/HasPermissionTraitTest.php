<?php

namespace Tests\Feature\Central;

use App\Models\TenantUser as CentralTenantUser;
use App\Models\User;
use App\Models\Tenant\User as TenantUser;
use App\Traits\HasPermissionTrait;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\Feature\TenantTestCase;

/**
 * ======================================================================
 * Test untuk: HasPermissionTrait
 * ======================================================================
 *
 * Trait ini digunakan di banyak controller untuk check permission user.
 * 
 * Methods yang di-test:
 * - checkPermission($permission)       - Check apakah user punya permission
 * - checkIsNotRecruiter()              - Check apakah user bukan recruiter
 * - authorizeUser($permission)         - Authorize user
 *
 * NOTE: Test ini focus pada behavior trait dengan super_admin user
 * yang sudah ada dari setup. Testing dengan berbagai role types
 * sudah tercovered oleh integration tests di controller.
 *
 * Coverage Target: 100%
 * 
 * Test ini menggunakan Spatie Permission untuk membuat role dan permission,
 * kemudian assign ke user untuk test berbagai skenario permission checks
 *
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/Tenant/HasPermissionTraitTest.php
 * ======================================================================
 */
class HasPermissionTraitTest extends TenantTestCase
{
    use WithFaker;

    /**
     * Dummy class untuk test trait.
     * Trait tidak bisa di-test langsung, perlu class yang menggunakan trait.
     */
    private function getTraitUser()
    {
        return new class {
            use HasPermissionTrait;

            // Expose protected methods untuk testing
            public function testCheckPermission($permission)
            {
                return $this->checkPermission($permission);
            }

            public function testCheckIsNotRecruiter()
            {
                return $this->checkIsNotRecruiter();
            }

            public function testAuthorizeUser($permission = null)
            {
                return $this->authorizeUser($permission);
            }

            public function testCheckIsOwner(string $tenantId): bool
            {
                return $this->checkIsOwner($tenantId);
            }
        };
    }

    /**
     * Helper: Get current tenant user
     */
    private function getTenantUser()
    {
        return TenantUser::where('global_id', $this->centralUser->global_id)->first();
    }

    /**
     * Helper: Create recruiter user in tenant context
     */
    private function createRecruiterUser()
    {
        $user = $this->getTenantUser();
        $user->syncRoles([]); // Hapus semua role
        $user->role = 'admin';
        $user->save();

        CentralTenantUser::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('global_user_id', $this->centralUser->global_id)
            ->update(['role' => 'admin']);
        
        $this->actingAs($this->centralUser, 'api');
        
        return $user;
    }

    /**
     * Helper: Create user dengan permission tertentu (non-super_admin)
     */
    private function createUserWithPermission($permissionName)
    {
        $user = $this->getTenantUser();
        
        // Hapus role super_admin
        $user->syncRoles([]);
        
        // Buat role baru dengan permission
        $role = Role::create(['name' => 'test_role_' . uniqid()]);
        $permission = Permission::create(['name' => $permissionName]);
        $role->givePermissionTo($permission);
        
        // Assign role ke user
        $user->assignRole($role);
        $user->role = 'admin'; // Set ke admin (bukan super_admin)
        $user->save();

        CentralTenantUser::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('global_user_id', $this->centralUser->global_id)
            ->update(['role' => 'admin']);
        
        $this->actingAs($this->centralUser, 'api');
        
        return $user;
    }

    // ===================================================================================
    // TEST: checkPermission() - Single Permission
    // ===================================================================================

    /**
     * Test (Happy Path): Super admin selalu punya akses ke semua permission
     */
    public function test_super_admin_always_has_permission(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner(); // Owner adalah super_admin
        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        // Super admin harus bisa akses permission apapun
        $this->assertTrue($traitUser->testCheckPermission('any.permission'));
        $this->assertTrue($traitUser->testCheckPermission('another.permission'));
    }

    /**
     * Test (Happy Path): Super admin bisa akses dengan single permission string
     */
    public function test_super_admin_can_check_single_permission(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner(); // Owner = super_admin

        // Buat permission (meski super admin tidak perlu permission ini)
        Permission::create(['name' => 'test.single.permission']);

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        // Super admin selalu punya akses, return true
        $this->assertTrue($traitUser->testCheckPermission('test.single.permission'));
    }

    /**
     * Test (Happy Path): Non-super_admin user dengan permission bisa akses (single permission)
     */
    public function test_user_with_single_permission_can_access(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $this->createUserWithPermission('posts.view');

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->assertTrue($traitUser->testCheckPermission('posts.view'));
    }

    /**
     * Test (Sad Path): Non-super_admin user tanpa permission tidak bisa akses (single permission)
     */
    public function test_user_without_single_permission_cannot_access(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $this->createUserWithPermission('posts.view');
        
        // Buat permission yang tidak dimiliki user
        Permission::create(['name' => 'posts.delete']);

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You do not have the required permission.');
        
        $traitUser->testCheckPermission('posts.delete'); // User tidak punya permission ini
    }

    /**
     * Test (Sad Path): Unauthenticated user tidak bisa akses
     */
    public function test_unauthenticated_user_cannot_check_permission(): void
    {
        // ARRANGE - Tidak login
        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $traitUser->testCheckPermission('any.permission');
    }

    // ===================================================================================
    // TEST: checkPermission() - Multiple Permissions (Array)
    // ===================================================================================

    /**
     * Test (Happy Path): Super admin dengan array permissions
     */
    public function test_super_admin_can_check_array_permissions(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner(); // Owner = super_admin

        // Buat permissions
        Permission::create(['name' => 'posts.view']);
        Permission::create(['name' => 'posts.create']);

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        // Super admin bisa akses array permissions
        $this->assertTrue($traitUser->testCheckPermission(['posts.view', 'posts.create']));
    }

    /**
     * Test (Happy Path): Non-super_admin user dengan salah satu permission bisa akses (array permissions)
     */
    public function test_user_with_any_array_permission_can_access(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        $user = $this->getTenantUser();
        $user->syncRoles([]);
        
        // Buat 2 permissions, tapi user hanya punya 1
        $perm1 = Permission::create(['name' => 'articles.view']);
        $perm2 = Permission::create(['name' => 'articles.create']);
        
        $role = Role::create(['name' => 'viewer_' . uniqid()]);
        $role->givePermissionTo($perm1); // Hanya permission view
        
        $user->assignRole($role);
        $user->role = 'admin';
        $user->save();
        
        $this->actingAs($this->centralUser, 'api');
        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        // User punya articles.view, jadi bisa akses meski tidak punya articles.create
        $this->assertTrue($traitUser->testCheckPermission(['articles.view', 'articles.create']));
    }

    /**
     * Test (Sad Path): Non-super_admin user tanpa semua permission tidak bisa akses (array permissions)
     */
    public function test_user_without_any_array_permission_cannot_access(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $this->createUserWithPermission('posts.view'); // User hanya punya posts.view

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You do not have the required permission.');
        
        // User tidak punya admin.view atau admin.delete
        $traitUser->testCheckPermission(['admin.view', 'admin.delete']);
    }

    // ===================================================================================
    // TEST: checkIsNotRecruiter()
    // ===================================================================================

    /**
     * Test (Happy Path): Super admin (non-recruiter) bisa akses
     */
    public function test_super_admin_is_not_recruiter(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner(); // Owner = super_admin (bukan recruiter)

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->assertTrue($traitUser->testCheckIsNotRecruiter());
    }

    /**
     * Test (Sad Path): Recruiter user tidak bisa akses
     * 
     * NOTE: checkIsNotRecruiter() menggunakan Auth::user()->isRecruiter()
     * tanpa tenant context check (berbeda dengan checkPermission).
     * Test ini menggunakan central user dengan role recruiter.
     */
    public function test_recruiter_user_is_blocked(): void
    {
        // ARRANGE
        // Set central user role ke admin di tenant context
        $this->centralUser->tenantUsers()->where('tenant_id', $this->tenant->id)->update([
            'role' => 'admin'
        ]);
        
        $this->actingAs($this->centralUser, 'api');

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Recruiter is not allowed to perform this action.');

        $traitUser->testCheckIsNotRecruiter();
    }

    /**
     * Test (Sad Path): Unauthenticated user tidak bisa check recruiter
     */
    public function test_unauthenticated_user_cannot_check_recruiter(): void
    {
        // ARRANGE - Tidak login
        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $traitUser->testCheckIsNotRecruiter();
    }

    // ===================================================================================
    // TEST: authorizeUser() - With Explicit Permission
    // ===================================================================================

    /**
     * Test (Happy Path): Super admin dapat authorize dengan explicit permission
     */
    public function test_super_admin_can_authorize_with_permission(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner(); // Owner = super_admin

        Permission::create(['name' => 'documents.edit']);

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->assertTrue($traitUser->testAuthorizeUser('documents.edit'));
    }

    /**
     * Test: authorizeUser() tanpa parameter menggunakan route name
     */
    public function test_authorize_user_without_parameter_uses_route_name(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // Buat permission berdasarkan route name (dot replaced with underscore)
        Permission::create(['name' => 'job_positions_view']);

        // Mock route dengan name
        $route = new \Illuminate\Routing\Route(['GET'], '/test', []);
        $route->name('job_positions.view');

        // Set route ke request
        request()->setRouteResolver(function () use ($route) {
            return $route;
        });

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->assertTrue($traitUser->testAuthorizeUser());
    }

    // ===================================================================================
    // TEST: Context Tenant
    // ===================================================================================

    /**
     * Test: Trait bekerja dengan benar dalam konteks tenant
     */
    public function test_trait_works_in_tenant_context(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner(); // Owner = super_admin

        // Pastikan kita dalam konteks tenant
        $this->assertNotNull(tenant());
        $this->assertEquals($this->tenant->id, tenant()->getTenantKey());

        // Buat permission di tenant context
        Permission::create(['name' => 'tenant.test']);

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        $this->assertTrue($traitUser->testCheckPermission('tenant.test'));
    }

    /**
     * Test: Super admin tetap punya akses meski di tenant berbeda
     */
    public function test_super_admin_has_access_in_any_tenant(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner(); // Owner adalah super_admin

        // Pastikan dalam konteks tenant
        $this->assertNotNull(tenant());

        $traitUser = $this->getTraitUser();

        // ACT & ASSERT
        // Super admin harus bisa akses permission apapun di tenant manapun
        $this->assertTrue($traitUser->testCheckPermission('any.tenant.permission'));
        $this->assertTrue($traitUser->testCheckPermission(['permission.one', 'permission.two']));
    }

    public function test_check_is_owner_throws_when_unauthenticated(): void
    {
        $traitUser = $this->getTraitUser();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $traitUser->testCheckIsOwner((string) $this->tenant->id);
    }

    public function test_check_is_owner_returns_true_for_direct_owner(): void
    {
        $this->actingAsTenantOwner();

        $traitUser = $this->getTraitUser();

        $this->assertTrue($traitUser->testCheckIsOwner((string) $this->tenant->id));
    }

    public function test_check_is_owner_throws_when_global_id_is_empty(): void
    {
        $user = User::create([
            'global_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user, 'api');

        $traitUser = $this->getTraitUser();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Only portal owner is allowed to perform this action.');

        $traitUser->testCheckIsOwner((string) $this->tenant->id);
    }

    public function test_check_is_owner_throws_when_user_is_not_owner_in_pivot(): void
    {
        $user = $this->centralUserRecruiter;
        $this->actingAs($user, 'api');

        $pivot = CentralTenantUser::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('global_user_id', $user->global_id)
            ->firstOrFail();

        $pivot->update([
            'role' => 'super_admin',
            'is_owner' => false,
        ]);

        $traitUser = $this->getTraitUser();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Only portal owner is allowed to perform this action.');

        $traitUser->testCheckIsOwner((string) $this->tenant->id);
    }

    public function test_check_is_owner_returns_true_for_owner_in_pivot(): void
    {
        $user = $this->centralUserRecruiter;
        $this->actingAs($user, 'api');

        $pivot = CentralTenantUser::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('global_user_id', $user->global_id)
            ->firstOrFail();

        $pivot->update([
            'role' => 'super_admin',
            'is_owner' => true,
        ]);

        $traitUser = $this->getTraitUser();

        $this->assertTrue($traitUser->testCheckIsOwner((string) $this->tenant->id));
    }
}
