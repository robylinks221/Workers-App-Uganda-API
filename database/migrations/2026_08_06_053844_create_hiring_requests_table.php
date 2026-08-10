<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiring_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_id')
                ->constrained('jobs')
                ->cascadeOnDelete();

            $table->foreignId('homeowner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('worker_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('status', [
                'pending',
                'accepted',
                'declined',
                'cancelled',
                'in_progress',
                'completed',
            ])->default('pending');

            $table->text('message')->nullable();

            $table->decimal('offered_amount', 12, 2)->nullable();

            $table->date('start_date')->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['job_id', 'worker_id'],
                'unique_job_worker_hiring_request'
            );

            $table->index(['homeowner_id', 'status']);
            $table->index(['worker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_requests');
    }
};
