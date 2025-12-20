<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class JobPosition extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'job_positions';

    protected $fillable = [
        'nusawork_id', // relasi ke nusawork
        'nusawork_name',
        'id_parent',
        'name',
    ];

    /**
     * Attributes to include in audit
     *
     * @var array
     */
    protected $auditInclude = [
        'name',
        'id_parent',
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
        return ['job-position'];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'id_parent');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'id_parent');
    }
}
