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
        'is_active' => 'boolean',
    ];
    
    /**
     * Hide sensitive fields from JSON responses
     */
    protected $hidden = [
        'certificate_path',
        'private_key_path',
        'passphrase',
        'passphrase_hash',
        'serial_number',
    ];
    
    public function certificateAuthority(): BelongsTo
    {
        return $this->belongsTo(CertificateAuthority::class);
    }
    
    public function user(): BelongsTo 
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if certificate is currently valid (not expired and already started)
     */
    public function isValid(): bool
    {
        $now = now();
        return $now >= $this->valid_from && $now <= $this->valid_to;
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired(): bool
    {
        return now() > $this->valid_to;
    }

    /**
     * Check if certificate is not yet valid
     */
    public function isNotYetValid(): bool
    {
        return now() < $this->valid_from;
    }

    /**
     * Get certificate status
     */
    public function getStatus(): string
    {
        if ($this->is_revoked) {
            return 'revoked';
        }
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isNotYetValid()) {
            return 'not_yet_valid';
        }
        return 'valid';
    }
}
