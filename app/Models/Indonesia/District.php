<?php

namespace App\Models\Indonesia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $table = 'id_districts';

    protected $fillable = [
        'code',
        'city_code',
        'name',
        'meta',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'district_code', 'code');
    }
}
