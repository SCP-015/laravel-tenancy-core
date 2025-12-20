<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class ExperienceLevel extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'experience_levels';

    protected $fillable = [
        'name', 'index',
    ];

    /**
     * Attributes to include in audit
     *
     * @var array
     */
    protected $auditInclude = [
        'name',
        'index',
    ];

    /**
     * Generate tags for audit
     *
     * @return array
     */
    public function generateTags(): array
    {
        return ['experience-level'];
    }
}
