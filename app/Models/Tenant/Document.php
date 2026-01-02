<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'metadata' => 'array',
    ];
    
    public function signingSession(): HasOne
    {
        return $this->hasOne(SigningSession::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }
}
