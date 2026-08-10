<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_access_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('homeowner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('worker_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();

            $table->unsignedInteger('view_count')
                ->default(0);

            $table->timestamps();

            $table->unique(
                ['homeowner_id', 'worker_id'],
                'identity_access_homeowner_worker_unique'
            );

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_access_requests');
    }
};
