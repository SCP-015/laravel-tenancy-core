<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SigningApprovalTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SigningApprovalStep::class, 'template_id')->orderBy('step_order');
    }
}
