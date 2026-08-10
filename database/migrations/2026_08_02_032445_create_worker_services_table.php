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
        Schema::create('worker_services', function (Blueprint $table) {

            $table->id();

            $table->foreignId('worker_profile_id')
                ->constrained('worker_profiles')
                ->cascadeOnDelete();

            $table->foreignId('service_category_id')
                ->constrained('service_categories')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'worker_profile_id',
                'service_category_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_services');
    }
};
