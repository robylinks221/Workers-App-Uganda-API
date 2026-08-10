<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->enum('cancelled_by', ['worker', 'homeowner'])->nullable()->after('cancelled_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_by');
            $table->text('cancellation_note')->nullable()->after('cancellation_reason');
        });

        // Phase 10 used "busy" automatically for an active assignment. Phase 11
        // no longer does that, so normalize those legacy rows once.
        DB::table('worker_profiles')->where('availability', 'busy')->update([
            'availability' => 'available',
        ]);
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'cancellation_note',
            ]);
        });
    }
};
