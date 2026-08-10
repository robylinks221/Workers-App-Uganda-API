<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create conversations between homeowners and workers.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Conversation Identity
            |--------------------------------------------------------------------------
            */

            $table->string('conversation_key')->unique();

            /*
            |--------------------------------------------------------------------------
            | Participants
            |--------------------------------------------------------------------------
            */

            $table->foreignId('homeowner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('worker_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Optional Related Job
            |--------------------------------------------------------------------------
            |
            | A direct conversation can begin before a job exists.
            | Once a conversation belongs to a job, job_id stores that job.
            |
            */

            $table->foreignId('job_id')
                ->nullable()
                ->constrained('jobs')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Conversation State
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'active',
                'archived',
                'blocked',
            ])->default('active');

            /*
            |--------------------------------------------------------------------------
            | Last Message Summary
            |--------------------------------------------------------------------------
            */

            $table->text('last_message')->nullable();

            $table->foreignId('last_message_sender_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('last_message_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Participant Archive State
            |--------------------------------------------------------------------------
            */

            $table->timestamp('homeowner_archived_at')->nullable();
            $table->timestamp('worker_archived_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'homeowner_id',
                'last_message_at',
            ]);

            $table->index([
                'worker_id',
                'last_message_at',
            ]);

            $table->index([
                'job_id',
                'status',
            ]);
        });
    }

    /**
     * Drop the conversations table.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
