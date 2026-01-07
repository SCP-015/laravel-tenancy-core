<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentralRootCA extends Model
{
    use HasFactory;

    protected $table = 'central_root_cas';
    protected $connection = 'pgsql';

    protected $guarded = ['id'];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_active' => 'boolean',
        'is_revoked' => 'boolean',
    ];

    protected $hidden = [
        'certificate_path',
        'private_key_path',
        'serial_number',
    ];

    /**
     * Get the active Central Root CA
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)
            ->where('is_revoked', false)
            ->first();
    }

    /**
     * Check if certificate is currently valid
     */
    public function isValid(): bool
    {
        $now = now();
        return $now >= $this->valid_from && $now <= $this->valid_to && !$this->is_revoked;
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired(): bool
    {
        return now() > $this->valid_to;
    }

    /**
     * Get validity days remaining
     */
    public function getValidityDaysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        return now()->diffInDays($this->valid_to);
    }
}
