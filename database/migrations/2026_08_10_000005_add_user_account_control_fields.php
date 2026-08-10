<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status_source', 20)
                ->nullable()
                ->after('account_status');

            $table->timestamp('deletion_requested_at')
                ->nullable()
                ->after('account_status_changed_by');

            $table->timestamp('deletion_scheduled_for')
                ->nullable()
                ->after('deletion_requested_at');

            $table->timestamp('deleted_at_app')
                ->nullable()
                ->after('deletion_scheduled_for');

            $table->index(
                ['account_status', 'deletion_scheduled_for'],
                'users_account_deletion_schedule_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(
                'users_account_deletion_schedule_index'
            );

            $table->dropColumn([
                'account_status_source',
                'deletion_requested_at',
                'deletion_scheduled_for',
                'deleted_at_app',
            ]);
        });
    }
};
