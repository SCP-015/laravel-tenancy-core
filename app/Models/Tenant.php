<?php

namespace App\Models;

use App\Services\UIDGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasFactory, HasDatabase, HasDomains;

    /**
     * Get the custom columns for the model.
     *
     * @return array
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'code',
            'slug',
            'slug_changed_at',
            'enable_slug_history_redirect',
            'plan',
            'owner_id',
            'theme_color',
            'header_image',
            'profile_image',
            'company_values',
            'employee_range_start',
            'employee_range_end',
            'company_category_id',
            'linkedin',
            'instagram',
            'website',
        ];
    }

    public function slugHistories(): HasMany
    {
        return $this->hasMany(TenantSlugHistory::class, 'tenant_id', 'id');
    }

    /**
     * Set the id attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setIdAttribute($value)
    {
        $this->attributes['id'] = $value ?? UIDGenerator::generate($this);
    }

    /**
     * Set the plan attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setPlanAttribute($value)
    {
        $this->attributes['plan'] = $value ?? 'free';
    }

    /**
     * Get the plan attribute.
     *
     * @return string
     */
    public function getPlanAttribute()
    {
        return $this->attributes['plan'] ?? 'free';
    }

    /**
     * Get the users for the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_users', 'tenant_id', 'global_user_id', 'id', 'global_id')
            ;
    }

    /**
     * Get tenant users relationship
     */
    public function tenantUsers()
    {
        return $this->hasMany(TenantUser::class, 'tenant_id', 'id');
    }

    /**
     * Get owner user
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    /**
     * Check if user is owner of this tenant
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->global_id;
    }

    /**
     * Get super admin users for this tenant
     */
    public function getSuperAdmins()
    {
        return $this->tenantUsers()->where('role', 'super_admin')->with('user')->get();
    }

    /**
     * Get recruiter users for this tenant
     */
    public function getRecruiters()
    {
        return $this->tenantUsers()->where('role', 'admin')->with('user')->get();
    }

    public function getFullImageUrl(string $imageField): ?string
    {
        // Cek apakah ada path untuk gambar, jika ada kembalikan URL-nya
        if ($this->$imageField) {
            return asset('storage/' . ltrim((string) $this->$imageField, '/'));
        }

        return null;  // Kembalikan null jika image tidak ada
    }

    public static function generateCode()
    {
        return Str::random(10);
    }

    public function companyCategory(): BelongsTo
    {
        return $this->belongsTo(CompanyCategory::class, 'company_category_id');
    }

    /**
     * Generate Unique slug from name
     * 
     * @codeCoverageIgnore - Slug generation memerlukan database setup yang kompleks
     */
    public static function generateSlug($name)
    {
        $baseSlug = static::prepareSlugFromName($name);
        return static::ensureUniqueSlug($baseSlug);
    }

    /**
     * Prepare slug from company name by removing common prefixes
     * 
     * @param string $name Company name
     * @return string Prepared slug (without uniqueness check)
     */
    public static function prepareSlugFromName(string $name): string
    {
        // List prefix perusahaan yang akan dihapus (case insensitive)
        $prefixes = [
            'PT. ',    // PT dengan titik dan spasi
            'PT.',     // PT dengan titik tanpa spasi
            'PT ',     // PT dengan spasi
            'CV. ',    // CV dengan titik dan spasi
            'CV.',     // CV dengan titik tanpa spasi
            'CV ',     // CV dengan spasi
            'UD. ',    // UD dengan titik dan spasi
            'UD.',     // UD dengan titik tanpa spasi
            'UD ',     // UD dengan spasi
            'PD. ',    // PD dengan titik dan spasi
            'PD.',     // PD dengan titik tanpa spasi
            'PD ',     // PD dengan spasi
            'FIRMA ',  // FIRMA dengan spasi
            'FIRMA. ', // FIRMA dengan titik dan spasi
            'FIRMA.',  // FIRMA dengan titik tanpa spasi
            'Tbk. ',   // Tbk dengan titik dan spasi
            'Tbk.',    // Tbk dengan titik tanpa spasi
            'Tbk ',    // Tbk dengan spasi
        ];

        // Trim spasi ekstra
        $cleanName = trim($name);
        $lowerName = strtolower($cleanName);

        // Hapus prefix yang cocok (case insensitive)
        foreach ($prefixes as $prefix) {
            if (str_starts_with($lowerName, strtolower($prefix))) {
                $cleanName = trim(substr($cleanName, strlen($prefix)));
                break;
            }
        }

        // Jika setelah dibersihkan nama menjadi kosong, gunakan nama asli
        if (empty($cleanName)) {
            $cleanName = $name;
        }

        // Buat slug dari nama yang sudah dibersihkan
        return Str::slug($cleanName);
    }

    /**
     * Ensure slug is unique by appending random string if needed
     * 
     * This method is excluded from code coverage because it requires
     * database access to check slug uniqueness.
     * 
     * @codeCoverageIgnore
     * @param string $baseSlug Base slug to make unique
     * @return string Unique slug
     */
    protected static function ensureUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;

        // Pastikan slug unik
        while (
            static::where('slug', $slug)->exists() ||
            TenantSlugHistory::where('slug', $slug)->exists()
        ) {
            $slug = $baseSlug . '-' . Str::random(5);
        }

        return $slug;
    }
}
