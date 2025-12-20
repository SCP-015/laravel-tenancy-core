<?php

namespace App\Models\Tenant;

use App\Models\User as CentralUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Contracts\Syncable;
use Stancl\Tenancy\Database\Concerns\ResourceSyncing;
use Stancl\Tenancy\Database\TenantScope;

class User extends Model implements Syncable, Auditable
{
    use ResourceSyncing, HasRoles, \OwenIt\Auditing\Auditable;

    /**
     * The guard name for the model.
     *
     * @var string
     */
    protected $guard_name = 'api';

    protected $guarded = [];

    /**
     * Attributes to include in audit.
     * Kita hanya perlu melacak perubahan role di tenant context.
     */
    protected $auditInclude = [
        'role',
    ];

    /**
     * Attributes to exclude from audit.
     */
    protected $auditExclude = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['permission_names'];

    /**
     * Get all permissions for the user.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        // Get direct permissions
        $permissions = $this->morphToMany(
            config('permission.models.permission'),
            'model',
            config('permission.table_names.model_has_permissions'),
            'model_id',
            'permission_id'
        )->get();

        // Get permissions via roles
        $rolePermissions = $this->roles()->with('permissions')->get()
            ->flatMap(function ($role) {
                return $role->permissions;
            });

        // Merge and return unique permissions
        return $permissions->merge($rolePermissions)->unique('id');
    }

    /**
     * Get all permission names for the user.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionNamesAttribute()
    {
        return $this->getAllPermissions()->pluck('name');
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted() {}

    public function getGlobalIdentifierKey()
    {
        return $this->getAttribute($this->getGlobalIdentifierKeyName());
    }

    public function getGlobalIdentifierKeyName(): string
    {
        return 'global_id';
    }

    public function getCentralModelName(): string
    {
        return CentralUser::class;
    }

    public function getSyncedAttributeNames(): array
    {
        return [
            'global_id',
            'name',
            'email',
            'password'
        ];
    }

    public static function getUserByAuth()
    {
        $userCentral = Auth::user();

        return static::where([
            'global_id' => $userCentral->global_id,
        ])->first();
    }
}
