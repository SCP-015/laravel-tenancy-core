<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant\User as TenantUser;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->unauthorizedResponse($request);
        }

        $authUser = $this->resolveAuthUser($user);

        // If super admin, bypass permission check
        if ($authUser->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$this->userHasPermissions($authUser, $permissions)) {
            return $this->forbiddenResponse($request, $permissions);
        }

        return $next($request);
    }

    /**
     * Resolve the authenticated user (tenant or central)
     *
     * @param  mixed  $user
     * @return mixed
     */
    private function resolveAuthUser($user)
    {
        // @codeCoverageIgnoreStart
        // Non-tenant context hanya terjadi di central domain (sulit di-test dalam tenant context)
        if (!tenant()) {
            return $user;
        }
        // @codeCoverageIgnoreEnd

        $tenantUser = TenantUser::where([
            'global_id' => $user->global_id,
            'tenant_id' => tenant()->getTenantKey()
        ])->first();

        return $tenantUser ?: $user;
    }

    /**
     * Check if user has any of the required permissions
     *
     * @param  mixed  $authUser
     * @param  array  $permissions
     * @return bool
     */
    private function userHasPermissions($authUser, array $permissions)
    {
        foreach ($permissions as $permissionGroup) {
            $permissionList = explode('|', $permissionGroup);
            
            foreach ($permissionList as $permission) {
                if ($this->checkSinglePermission($authUser, $permission)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check a single permission (including wildcards)
     *
     * @param  mixed  $authUser
     * @param  string  $permission
     * @return bool
     */
    private function checkSinglePermission($authUser, string $permission)
    {
        if (str_ends_with($permission, '.*')) {
            return $this->checkWildcardPermission($authUser, $permission);
        }

        return $authUser->hasPermissionTo($permission, 'api');
    }

    /**
     * Check wildcard permission
     *
     * @param  mixed  $authUser
     * @param  string  $permission
     * @return bool
     */
    private function checkWildcardPermission($authUser, string $permission)
    {
        $permissionPrefix = str_replace('.*', '.', $permission);
        $userPermissions = $authUser->getAllPermissions()->pluck('name')->toArray();

        foreach ($userPermissions as $userPermission) {
            if (str_starts_with($userPermission, $permissionPrefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return unauthorized response
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    private function unauthorizedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null
            ], 401);
        }

        // @codeCoverageIgnoreStart
        // Redirect response hanya terjadi di web context, tidak di API tests
        return redirect()->route('login');
        // @codeCoverageIgnoreEnd
    }

    /**
     * Return forbidden response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $permissions
     * @return mixed
     */
    private function forbiddenResponse(Request $request, array $permissions)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have the required permissions to access this resource.',
                'data' => [
                    'required_permissions' => $permissions
                ]
            ], 403);
        }

        // @codeCoverageIgnoreStart
        // Redirect response hanya terjadi di web context, tidak di API tests
        return redirect()->route('unauthorized')
            ->with('error', 'Anda tidak memiliki izin untuk mengakses halaman tersebut.');
        // @codeCoverageIgnoreEnd
    }
}
