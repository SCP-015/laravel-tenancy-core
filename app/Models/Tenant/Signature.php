<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'signed_at' => 'datetime',
        'is_required' => 'boolean',
    ];
    
    public function signingSession(): BelongsTo
    {
        return $this->belongsTo(SigningSession::class);
    }
    
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    public function userCertificate(): BelongsTo
    {
        return $this->belongsTo(UserCertificate::class, 'certificate_id');
    }
    
    public function user(): BelongsTo
    {
         return $this->belongsTo(User::class, 'user_id');
    }
}
