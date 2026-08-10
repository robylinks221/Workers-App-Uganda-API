<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->renameColumn(
                'national_id_document',
                'national_id_front_document'
            );
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->string('national_id_back_document')
                ->nullable()
                ->after('national_id_front_document');

            $table->dropUnique(
                'worker_profiles_national_id_number_unique'
            );

            $table->dropColumn('national_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->string('national_id_number', 30)
                ->nullable()
                ->unique()
                ->after('work_type');

            $table->dropColumn(
                'national_id_back_document'
            );
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->renameColumn(
                'national_id_front_document',
                'national_id_document'
            );
        });
    }
};
