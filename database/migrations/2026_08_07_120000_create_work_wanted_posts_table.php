<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_wanted_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('district', 100);
            $table->enum('work_type', ['full_time', 'part_time', 'either'])->default('either');
            $table->enum('living_preference', ['live_in', 'live_out', 'either'])->default('either');
            $table->decimal('expected_salary_min', 12, 2)->nullable();
            $table->decimal('expected_salary_max', 12, 2)->nullable();
            $table->date('available_from')->nullable();
            $table->boolean('available_immediately')->default(true);
            $table->boolean('willing_to_relocate')->default(false);
            $table->enum('status', ['active', 'paused', 'hired', 'closed'])->default('active');
            $table->timestamps();
            $table->index(['status', 'district']);
            $table->index(['worker_id', 'status']);
        });

        Schema::create('work_wanted_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_wanted_post_id')->constrained('work_wanted_posts')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['work_wanted_post_id', 'service_category_id'], 'work_wanted_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_wanted_services');
        Schema::dropIfExists('work_wanted_posts');
    }
};
