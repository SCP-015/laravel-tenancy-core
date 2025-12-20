<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CompanyCategory extends Model
{
    use HasFactory;

    protected $table = 'company_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     * Secara otomatis membuat slug dari nama saat menyimpan.
     */
    protected static function booted(): void
    {
        static::saving(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    /**
     * Get the tenants for the company category.
     */
    public function tenants(): HasMany
    {
        // Asumsi model Tenant Anda ada di App\Models\Central\Tenant
        return $this->hasMany(Tenant::class, 'company_category_id');
    }
}
