<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update worker availability values.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE worker_profiles
            MODIFY availability ENUM(
                'available',
                'busy',
                'unavailable'
            )
            NOT NULL
            DEFAULT 'available'
        ");
    }

    /**
     * Restore the previous availability values.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE worker_profiles
            MODIFY availability ENUM(
                'available',
                'unavailable'
            )
            NOT NULL
            DEFAULT 'available'
        ");
    }
};
