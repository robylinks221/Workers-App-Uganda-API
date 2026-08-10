<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('work_arrangement', 50)
                ->nullable()
                ->after('duration');

            $table->string('contract_duration', 100)
                ->nullable()
                ->after('work_arrangement');

            $table->boolean('accommodation_provided')
                ->default(false)
                ->after('contract_duration');

            $table->boolean('meals_provided')
                ->default(false)
                ->after('accommodation_provided');

            $table->boolean('transport_allowance')
                ->default(false)
                ->after('meals_provided');

            $table->boolean('medical_support')
                ->default(false)
                ->after('transport_allowance');

            $table->boolean('uniform_provided')
                ->default(false)
                ->after('medical_support');

            $table->text('other_benefits')
                ->nullable()
                ->after('uniform_provided');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'work_arrangement',
                'contract_duration',
                'accommodation_provided',
                'meals_provided',
                'transport_allowance',
                'medical_support',
                'uniform_provided',
                'other_benefits',
            ]);
        });
    }
};
