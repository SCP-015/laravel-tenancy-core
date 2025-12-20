<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class EducationLevel extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'education_levels';

    protected $fillable = [
        'nusawork_id', // relasi ke nusawork
        'nusawork_name',
        'name',
        'index',
    ];

    /**
     * Attributes to include in audit
     *
     * @var array
     */
    protected $auditInclude = [
        'name',
        'index',
        'nusawork_id',
        'nusawork_name',
    ];

    /**
     * Generate tags for audit
     *
     * @return array
     */
    public function generateTags(): array
    {
        return ['education-level'];
    }
}
