<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_key',
        'homeowner_id',
        'worker_id',
        'job_id',
        'status',
        'last_message',
        'last_message_sender_id',
        'last_message_at',
        'homeowner_archived_at',
        'worker_archived_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'homeowner_archived_at' => 'datetime',
        'worker_archived_at' => 'datetime',
    ];

    public function homeowner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeowner_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function lastMessageSender(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'last_message_sender_id'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isParticipant(int $userId): bool
    {
        return $this->homeowner_id === $userId
            || $this->worker_id === $userId;
    }

    public function otherParticipant(int $userId): ?User
    {
        if ($userId === $this->homeowner_id) {
            return $this->worker;
        }

        if ($userId === $this->worker_id) {
            return $this->homeowner;
        }

        return null;
    }
}
