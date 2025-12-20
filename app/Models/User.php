<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use App\Models\TenantUser as CentralTenantUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Stancl\Tenancy\Contracts\SyncMaster;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\ResourceSyncing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\SsoTokenService;

class User extends Authenticatable implements SyncMaster
{
    use HasFactory, Notifiable, HasApiTokens;
    use ResourceSyncing, CentralConnection;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users', 'global_user_id', 'tenant_id', 'global_id')
            ;
    }

    public function getTenantModelName(): string
    {
        return TenantUser::class;
    }

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
        return static::class;
    }

    public function getSyncedAttributeNames(): array
    {
        return [
            'global_id',
            'name',
            'password',
            'email',
        ];
    }

    /**
     * Get tenant users relationship
     */
    public function tenantUsers()
    {
        return $this->hasMany(CentralTenantUser::class, 'global_user_id', 'global_id');
    }



    /**
     * Accessor untuk role attribute (support $user->role)
     * Mengambil role dari tenant user context
     *
     * @codeCoverageIgnore - Accessor sudah di-cover via integration tests
     * @return string|null
     */
    public function getRoleAttribute(): ?string
    {
        return $this->getRole();
    }

    /**
     * Get user role for specific tenant or current tenant context
     *
     * @param string|null $tenantId
     * @return string|null
     */
    public function getRole(?string $tenantId = null): ?string
    {
        // Jika dalam tenant context, gunakan tenant ID dari context
        if (!$tenantId && function_exists('tenant')) {
            $tenant = tenant();
            $tenantId = $tenant ? $tenant->id : null;
        }

        $tenantUser = $this->getTenantUser($tenantId);
        $role = $tenantUser ? $tenantUser->role : null;
        return $role;
    }

    /**
     * Check if user is super admin in specific tenant or current tenant context
     *
     * @param string|null $tenantId
     * @return bool
     */
    public function isSuperAdmin(?string $tenantId = null): bool
    {
        return $this->getRole($tenantId) === 'super_admin';
    }

    public function isAdmin(?string $tenantId = null): bool
    {
        return $this->getRole($tenantId) === 'admin';
    }

    /**
     * Check if user is recruiter in specific tenant or current tenant context
     *
     * @param string|null $tenantId
     * @return bool
     */
    public function isRecruiter(?string $tenantId = null): bool
    {
        return $this->isAdmin($tenantId);
    }

    /**
     * Get tenant user data for specific tenant
     *
     * @param string|null $tenantId
     * @return CentralTenantUser|null
     */
    public function getTenantUser($tenantId = null)
    {
        if (!$tenantId) {
            // Return first tenant user if no specific tenant
            return $this->tenantUsers()->first();
        }

        return $this->tenantUsers()->where('tenant_id', $tenantId)->first();
    }


}
