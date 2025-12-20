<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class RecruiterInvitation extends Model
{
    
    protected $table = 'recruiter_invitations';

    protected $fillable = [
        'email',
        'invited_by_email',
        'code',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
