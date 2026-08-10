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
        Schema::create('worker_profiles', function (Blueprint $table) {
            $table->id();

            // Link to users table
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Profile
            $table->text('bio')->nullable();

            $table->integer('experience_years')->default(0);

            $table->decimal('hourly_rate', 10, 2)->nullable();

            $table->decimal('monthly_rate', 10, 2)->nullable();

            $table->enum('availability', [
                'available',
                'busy',
                'offline'
            ])->default('available');

            // Statistics
            $table->decimal('rating', 3, 2)->default(0.00);

            $table->unsignedInteger('total_reviews')->default(0);

            $table->unsignedInteger('jobs_completed')->default(0);

            // Verification
            $table->boolean('background_checked')->default(false);

            $table->boolean('police_clearance')->default(false);

            $table->boolean('medical_clearance')->default(false);

            $table->boolean('identity_verified')->default(false);

            // Profile status
            $table->boolean('featured')->default(false);

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_profiles');
    }
};
