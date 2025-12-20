<?php

namespace App\Models\Indonesia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table = 'id_cities';

    protected $fillable = [
        'code',
        'province_code',
        'name',
        'meta',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'city_code', 'code');
    }
}
