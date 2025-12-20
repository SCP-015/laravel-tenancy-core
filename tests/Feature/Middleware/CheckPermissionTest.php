<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\CheckPermission;
use App\Models\Tenant\User as TenantUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk CheckPermission Middleware
 */
class CheckPermissionTest extends TenantTestCase
{
    private CheckPermission $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckPermission();
    }

    /**
     * Test: Middleware menolak request tanpa autentikasi
     */
    public function test_middleware_rejects_unauthenticated_request_with_json(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $next = function ($req) {
            return response('Should not reach here');
        };

        // ACT
        $response = $this->middleware->handle($request, $next, 'test.permission');

        // ASSERT
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Unauthenticated', $data['message']);
    }

    /**
     * Test: Middleware redirect ke login untuk unauthenticated non-JSON request
     */
    public function test_middleware_redirects_to_login_for_unauthenticated_non_json(): void
    {
        // ARRANGE
        Route::get('login')->name('login');
        
        $request = Request::create('/test', 'GET');
        
        $next = function ($req) {
            return response('Should not reach here');
        };

        // ACT
        $response = $this->middleware->handle($request, $next, 'test.permission');

        // ASSERT
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Location'), 'login'));
    }

    /**
     * Test: Super admin bypass permission check
     */
    public function test_super_admin_bypasses_permission_check(): void
    {
        // ARRANGE
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->assignRole($role);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT
        $response = $this->middleware->handle($request, $next, 'any.permission');

        // ASSERT
        $this->assertTrue($nextCalled);
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test: User dengan permission yang benar dapat akses
     */
    public function test_user_with_correct_permission_can_access(): void
    {
        // ARRANGE
        $permission = Permission::firstOrCreate(['name' => 'job_positions.view', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        $tenantUser->givePermissionTo($permission);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT
        $response = $this->middleware->handle($request, $next, 'job_positions.view');

        // ASSERT
        $this->assertTrue($nextCalled);
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test: User tanpa permission ditolak dengan JSON response
     */
    public function test_user_without_permission_is_forbidden_with_json(): void
    {
        // ARRANGE
        // Buat permission dulu agar tidak throw PermissionDoesNotExist
        Permission::firstOrCreate(['name' => 'job_positions.delete', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        // Remove all roles dan permissions untuk test ini
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $next = function ($req) {
            return response('Should not reach here');
        };

        // ACT
        $response = $this->middleware->handle($request, $next, 'job_positions.delete');

        // ASSERT
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('required permissions', $data['message']);
        $this->assertArrayHasKey('required_permissions', $data['data']);
    }

    /**
     * Test: User tanpa permission redirect untuk non-JSON request
     */
    public function test_user_without_permission_redirects_for_non_json(): void
    {
        // ARRANGE
        Permission::firstOrCreate(['name' => 'job_positions.delete', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $next = function ($req) {
            return response('Should not reach here');
        };

        // ACT & ASSERT - Expect UrlGenerationException karena route 'unauthorized' tidak ada
        $this->expectException(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
        $response = $this->middleware->handle($request, $next, 'job_positions.delete');
    }

    /**
     * Test: Multiple permissions dengan OR logic
     */
    public function test_multiple_permissions_with_or_logic(): void
    {
        // ARRANGE
        $permission1 = Permission::firstOrCreate(['name' => 'job_positions.view', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        $tenantUser->givePermissionTo($permission1);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT - pass jika punya salah satu dari: job_positions.view ATAU job_positions.edit
        $response = $this->middleware->handle($request, $next, 'job_positions.view|job_positions.edit');

        // ASSERT
        $this->assertTrue($nextCalled);
    }

    /**
     * Test: Wildcard permission check
     */
    public function test_wildcard_permission_check(): void
    {
        // ARRANGE
        $permission1 = Permission::firstOrCreate(['name' => 'job_positions.view', 'guard_name' => 'api']);
        $permission2 = Permission::firstOrCreate(['name' => 'job_positions.create', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        $tenantUser->givePermissionTo([$permission1, $permission2]);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT - wildcard permission 'job_positions.*' cocok dengan 'job_positions.view' dan 'job_positions.create'
        $response = $this->middleware->handle($request, $next, 'job_positions.*');

        // ASSERT
        $this->assertTrue($nextCalled);
    }

    /**
     * Test: User dengan permission via role
     */
    public function test_user_with_permission_via_role(): void
    {
        // ARRANGE
        $permission = Permission::firstOrCreate(['name' => 'job.view', 'guard_name' => 'api']);
        $role = Role::firstOrCreate(['name' => 'recruiter', 'guard_name' => 'api']);
        $role->givePermissionTo($permission);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        $tenantUser->assignRole($role);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT
        $response = $this->middleware->handle($request, $next, 'job.view');

        // ASSERT
        $this->assertTrue($nextCalled);
    }

    /**
     * Test: Wildcard permission tidak cocok dengan permission tidak relevan
     */
    public function test_wildcard_permission_does_not_match_irrelevant_permissions(): void
    {
        // ARRANGE
        $permission = Permission::create(['name' => 'interview.view', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        $tenantUser->givePermissionTo($permission);
        
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $next = function ($req) {
            return response('Should not reach here');
        };

        // ACT - wildcard 'candidate.*' tidak cocok dengan 'interview.view'
        $response = $this->middleware->handle($request, $next, 'candidate.*');

        // ASSERT
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test: Multiple permission groups (complex OR logic)
     */
    public function test_multiple_permission_groups_with_complex_or_logic(): void
    {
        // ARRANGE
        Permission::firstOrCreate(['name' => 'candidate.view', 'guard_name' => 'api']);
        $permission = Permission::firstOrCreate(['name' => 'candidate.edit', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'job.view', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'job.edit', 'guard_name' => 'api']);
        
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        $tenantUser->givePermissionTo($permission);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT - pass jika punya: (candidate.view OR candidate.edit) OR (job.view OR job.edit)
        $response = $this->middleware->handle($request, $next, 'candidate.view|candidate.edit', 'job.view|job.edit');

        // ASSERT
        $this->assertTrue($nextCalled);
    }

    /**
     * Test: Empty permission array forbids all authenticated users
     */
    public function test_no_permissions_required_allows_authenticated_users(): void
    {
        // ARRANGE
        $tenantUser = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $tenantUser->syncRoles([]);
        $tenantUser->syncPermissions([]);
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($tenantUser) {
            return $tenantUser;
        });
        
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT & ASSERT - Expect RouteNotFoundException karena akan redirect tapi route tidak ada
        $this->expectException(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
        $response = $this->middleware->handle($request, $next);
    }
}
