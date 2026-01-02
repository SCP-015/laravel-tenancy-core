<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SigningSession extends Model
{
    use HasUuids;
    
    protected $guarded = ['id'];
    
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }
}
