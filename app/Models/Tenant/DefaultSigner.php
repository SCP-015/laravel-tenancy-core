<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class DefaultSigner extends Model
{
    use HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'workgroup_id',
        'user_id',
        'step_order',
        'role',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'step_order' => 'integer',
    ];

    /**
     * Get the user that is the default signer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workgroup.
     */
    public function workgroup(): BelongsTo
    {
        return $this->belongsTo(Workgroup::class);
    }

    /**
     * Scope untuk filter berdasarkan workgroup.
     */
    public function scopeForWorkgroup($query, string $workgroupId)
    {
        return $query->where('workgroup_id', $workgroupId);
    }

    /**
     * Scope untuk filter hanya yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get signers ordered by step_order untuk sequential signing.
     */
    public static function getSignersForWorkgroup(string $workgroupId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('workgroup_id', $workgroupId)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->with('user')
            ->get();
    }

    /**
     * Get distinct workgroups.
     */
    public static function getWorkgroups(): \Illuminate\Database\Eloquent\Collection
    {
        return Workgroup::where('is_active', true)->get();
    }
}
