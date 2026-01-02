<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateAuthority extends Model
{
    use HasFactory;
    
    // In tenant database, so no need for BelongsToTenant trait unless using central DB
    // Assuming we use separate databases per tenant as per standard tenancy
    
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
        'serial_number',
    ];
}
