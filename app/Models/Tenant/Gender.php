<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'genders';

    protected $fillable = ['id', 'name'];
}
