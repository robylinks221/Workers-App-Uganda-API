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
        Schema::create('jobs', function (Blueprint $table) {

            $table->id();

            // Homeowner who posted the job
            $table->foreignId('homeowner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Worker who gets the job
            $table->foreignId('accepted_worker_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Job Information
            $table->string('title');
            $table->string('category');

            $table->text('description');

            // Location
            $table->string('address');
            $table->string('district');
            $table->decimal('latitude',10,7)->nullable();
            $table->decimal('longitude',10,7)->nullable();

            // Schedule
            $table->date('start_date');
            $table->time('start_time')->nullable();

            $table->string('duration');

            // Budget
            $table->enum('budget_type',[
                'fixed',
                'daily',
                'monthly'
            ]);

            $table->decimal('budget_amount',10,2);

            // Status
            $table->enum('status',[
                'open',
                'accepted',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('open');

            $table->boolean('is_urgent')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
