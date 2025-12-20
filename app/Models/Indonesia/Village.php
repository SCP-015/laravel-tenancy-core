<?php

namespace App\Models\Indonesia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Village extends Model
{
    protected $table = 'id_villages';

    protected $fillable = [
        'code',
        'district_code',
        'name',
        'meta',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
