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
        Schema::create('job_applications', function (Blueprint $table) {

            $table->id();

            // Job
            $table->foreignId('job_id')
                ->constrained('jobs')
                ->cascadeOnDelete();

            // Worker
            $table->foreignId('worker_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Cover Message
            $table->text('message')->nullable();

            // Expected Salary
            $table->decimal('expected_salary', 10, 2)->nullable();

            // Application Status
            $table->enum('status', [
                'pending',
                'accepted',
                'declined',
                'withdrawn'
            ])->default('pending');

            $table->timestamps();

            // Prevent duplicate applications
            $table->unique(['job_id', 'worker_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
