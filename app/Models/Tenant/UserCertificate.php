<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCertificate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_revoked' => 'boolean',
    ];
    
    /**
     * Hide sensitive fields from JSON responses
     */
    protected $hidden = [
        'certificate_path',
        'private_key_path',
        'passphrase',
        'serial_number',
    ];
    
    public function certificateAuthority(): BelongsTo
    {
        return $this->belongsTo(CertificateAuthority::class);
    }
    
    public function user(): BelongsTo 
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
