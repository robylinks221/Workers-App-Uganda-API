<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message_type',
        'message',
        'attachment_path',
        'attachment_name',
        'attachment_mime_type',
        'attachment_size',
        'delivered_at',
        'read_at',
        'is_edited',
        'edited_at',
        'deleted_for_sender_at',
        'deleted_for_receiver_at',
    ];

    protected $casts = [
        'attachment_size' => 'integer',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'deleted_for_sender_at' => 'datetime',
        'deleted_for_receiver_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
