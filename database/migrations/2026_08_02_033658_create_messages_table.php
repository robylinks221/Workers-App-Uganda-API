<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {

            $table->id();

            // Sender
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Receiver
            $table->foreignId('receiver_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Optional Job
            $table->foreignId('job_id')
                ->nullable()
                ->constrained('jobs')
                ->nullOnDelete();

            // Message
            $table->text('body');

            // Attachments
            $table->string('attachment')->nullable();

            // Read Status
            $table->timestamp('read_at')->nullable();

            // Soft Delete
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
