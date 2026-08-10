<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add awaiting_confirmation to the jobs status enum.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE jobs
            MODIFY status ENUM(
                'open',
                'accepted',
                'in_progress',
                'awaiting_confirmation',
                'completed',
                'cancelled'
            )
            NOT NULL
            DEFAULT 'open'
        ");
    }

    /**
     * Remove awaiting_confirmation from the jobs status enum.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE jobs
            MODIFY status ENUM(
                'open',
                'accepted',
                'in_progress',
                'completed',
                'cancelled'
            )
            NOT NULL
            DEFAULT 'open'
        ");
    }
};
