<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Tenant\User as TenantUser;
use App\Models\TenantUser as CentralTenantUser;

trait HasPermissionTrait
{
    /**
     * Check if the authenticated user has the required permission.
     *
     * @param string|array $permission
     * @return bool
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function checkPermission($permission)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            throw new AccessDeniedHttpException(__('Unauthenticated.'));
        }

        $user = TenantUser::getUserByAuth();

        // Super admin always has access
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check if user has any of the given permissions
        if (is_array($permission)) {
            if (!$user->hasAnyPermission($permission)) {
                throw new AccessDeniedHttpException(__('You do not have the required permission.'));
            }
        } else {
            if (!$user->hasPermissionTo($permission)) {
                throw new AccessDeniedHttpException(__('You do not have the required permission.'));
            }
        }

        return true;
    }

    protected function checkIsOwner(string $tenantId): bool
    {
        $user = Auth::user();

        if (!$user || !$user instanceof User) {
            throw new AccessDeniedHttpException(__('Unauthenticated.'));
        }

        $tenant = Tenant::find($tenantId);
        if ($tenant && (string) $tenant->owner_id === (string) $user->id) {
            return true;
        }

        if (empty($user->global_id)) {
            throw new AccessDeniedHttpException(__('Only portal owner is allowed to perform this action.'));
        }

        $centralTenantUser = CentralTenantUser::query()
            ->where('tenant_id', $tenantId)
            ->where('global_user_id', $user->global_id)
            ->first();

        if (!$centralTenantUser || !$centralTenantUser->is_owner) {
            throw new AccessDeniedHttpException(__('Only portal owner is allowed to perform this action.'));
        }

        return true;
    }

    /**
     * Check if the authenticated user is a recruiter.
     * 
     * @return bool
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function checkIsNotRecruiter()
    {
        $user = Auth::user();
        
        if (!$user || !$user instanceof User) {
            throw new AccessDeniedHttpException(__('Unauthenticated.'));
        }

        $tenantId = null;
        if (function_exists('tenant')) {
            $tenant = tenant();
            $tenantId = $tenant ? $tenant->id : null;
        }

        $tenantUser = $user->getTenantUser($tenantId);
        if ($tenantUser && $tenantUser->role === 'admin') {
            throw new AccessDeniedHttpException(__('Recruiter is not allowed to perform this action.'));
        }

        return true;
    }

    /**
     * Authorize a given action for the current user.
     *
     * @param string|array $permission
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeUser($permission = null)
    {
        if ($permission) {
            return $this->checkPermission($permission);
        }

        // Get the current route name and convert to permission format
        $routeName = request()->route()->getName();
        $permission = str_replace('.', '_', $routeName);

        return $this->checkPermission($permission);
    }
}
