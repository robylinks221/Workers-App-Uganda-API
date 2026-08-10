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
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')
                ->nullable()
                ->after('user_id');

            $table->string('religion')
                ->nullable()
                ->after('age');

            $table->string('gender')
                ->nullable()
                ->after('religion');

            $table->string('district')
                ->nullable()
                ->after('gender');

            $table->enum('work_type', [
                'full_time',
                'part_time',
            ])
                ->nullable()
                ->after('district');

            $table->string('national_id_number', 30)
                ->nullable()
                ->unique()
                ->after('work_type');

            $table->string('national_id_document')
                ->nullable()
                ->after('national_id_number');

            $table->string('profile_photo')
                ->nullable()
                ->after('national_id_document');

            $table->boolean('profile_completed')
                ->default(false)
                ->after('profile_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropUnique(
                'worker_profiles_national_id_number_unique'
            );

            $table->dropColumn([
                'age',
                'religion',
                'gender',
                'district',
                'work_type',
                'national_id_number',
                'national_id_document',
                'profile_photo',
                'profile_completed',
            ]);
        });
    }
};
