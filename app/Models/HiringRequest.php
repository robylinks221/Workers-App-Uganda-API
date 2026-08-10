<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'homeowner_id',
        'worker_id',
        'status',
        'message',
        'offered_amount',
        'start_date',
        'accepted_at',
        'declined_at',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'offered_amount' => 'decimal:2',
        'start_date' => 'date',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function homeowner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeowner_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }
}
