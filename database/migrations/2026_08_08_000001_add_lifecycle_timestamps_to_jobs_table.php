<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('is_urgent');
            $table->timestamp('started_at')->nullable()->after('accepted_at');
            $table->timestamp('completion_requested_at')->nullable()->after('started_at');
            $table->timestamp('completed_at')->nullable()->after('completion_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['accepted_at','started_at','completion_requested_at','completed_at']);
        });
    }
};
