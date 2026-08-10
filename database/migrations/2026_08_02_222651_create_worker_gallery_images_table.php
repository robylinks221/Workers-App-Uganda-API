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
        Schema::create('worker_gallery_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('worker_profile_id')
                ->constrained('worker_profiles')
                ->cascadeOnDelete();

            $table->string('image_path');

            $table->unsignedTinyInteger('position')
                ->default(1);

            $table->timestamps();

            $table->unique([
                'worker_profile_id',
                'position',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_gallery_images');
    }
};
