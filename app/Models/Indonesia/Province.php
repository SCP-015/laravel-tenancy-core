<?php

namespace App\Models\Indonesia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $table = 'id_provinces';

    protected $fillable = [
        'code',
        'name',
        'meta',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'province_code', 'code');
    }
}
