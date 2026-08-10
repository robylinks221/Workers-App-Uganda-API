<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create messages belonging to conversations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Conversation
            |--------------------------------------------------------------------------
            */

            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Sender
            |--------------------------------------------------------------------------
            */

            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Message Content
            |--------------------------------------------------------------------------
            */

            $table->enum('message_type', [
                'text',
                'image',
                'file',
                'system',
            ])->default('text');

            $table->text('message')->nullable();

            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Delivery and Read Status
            |--------------------------------------------------------------------------
            */

            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Message State
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();

            $table->timestamp('deleted_for_sender_at')->nullable();
            $table->timestamp('deleted_for_receiver_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'conversation_id',
                'created_at',
            ]);

            $table->index([
                'conversation_id',
                'read_at',
            ]);

            $table->index([
                'sender_id',
                'created_at',
            ]);
        });
    }

    /**
     * Drop the messages table.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
