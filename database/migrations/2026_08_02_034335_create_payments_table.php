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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            // Related Job
            $table->foreignId('job_id')
                ->constrained('jobs')
                ->cascadeOnDelete();

            // Customer (Homeowner)
            $table->foreignId('homeowner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Worker
            $table->foreignId('worker_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Payment Information
            $table->decimal('amount', 10, 2);

            $table->enum('payment_method', [
                'mtn_momo',
                'airtel_money',
                'card',
                'cash'
            ]);

            $table->string('transaction_reference')->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
