<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityAccessRequest extends Model
{
    protected $fillable = [
        'homeowner_id',
        'worker_id',
        'requested_at',
        'expires_at',
        'last_viewed_at',
        'view_count',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'view_count' => 'integer',
    ];

    public function homeowner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeowner_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function isActive(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}
