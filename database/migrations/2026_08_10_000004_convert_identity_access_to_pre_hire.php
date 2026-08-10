<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Create table if it does not exist
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('identity_access_requests')) {
            Schema::create(
                'identity_access_requests',
                function (Blueprint $table) {
                    $table->id();

                    $table->foreignId('homeowner_id')
                        ->constrained('users')
                        ->cascadeOnDelete();

                    $table->foreignId('worker_id')
                        ->constrained('users')
                        ->cascadeOnDelete();

                    $table->timestamp('requested_at')
                        ->nullable();

                    $table->timestamp('expires_at')
                        ->nullable();

                    $table->timestamp('last_viewed_at')
                        ->nullable();

                    $table->unsignedInteger('view_count')
                        ->default(0);

                    $table->timestamps();

                    $table->unique(
                        [
                            'homeowner_id',
                            'worker_id',
                        ],
                        'identity_access_homeowner_worker_unique'
                    );

                    $table->index('expires_at');
                }
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Convert older hiring-request based design
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'identity_access_requests',
                'hiring_request_id'
            )
        ) {
            /*
            |--------------------------------------------------------------------------
            | Drop old unique index if it still exists
            |--------------------------------------------------------------------------
            */

            if (
                Schema::hasIndex(
                    'identity_access_requests',
                    'identity_access_hiring_request_unique'
                )
            ) {
                Schema::table(
                    'identity_access_requests',
                    function (Blueprint $table) {
                        $table->dropUnique(
                            'identity_access_hiring_request_unique'
                        );
                    }
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Drop old foreign key if it still exists
            |--------------------------------------------------------------------------
            */

            $foreignKeys = Schema::getForeignKeys(
                'identity_access_requests'
            );

            $hasHiringForeignKey = collect($foreignKeys)
                ->contains(function (array $foreignKey) {
                    return in_array(
                        'hiring_request_id',
                        $foreignKey['columns'] ?? [],
                        true
                    );
                });

            if ($hasHiringForeignKey) {
                Schema::table(
                    'identity_access_requests',
                    function (Blueprint $table) {
                        $table->dropForeign([
                            'hiring_request_id',
                        ]);
                    }
                );
            }

            if (
                Schema::hasColumn(
                    'identity_access_requests',
                    'hiring_request_id'
                )
            ) {
                Schema::table(
                    'identity_access_requests',
                    function (Blueprint $table) {
                        $table->dropColumn(
                            'hiring_request_id'
                        );
                    }
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Remove old consent fields
        |--------------------------------------------------------------------------
        */

        $columnsToDrop = [];

        foreach (
            [
                'status',
                'approved_at',
                'denied_at',
                'revoked_at',
            ] as $column
        ) {
            if (
                Schema::hasColumn(
                    'identity_access_requests',
                    $column
                )
            ) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table(
                'identity_access_requests',
                function (
                    Blueprint $table
                ) use ($columnsToDrop) {
                    $table->dropColumn(
                        $columnsToDrop
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure required pre-hire columns exist
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'identity_access_requests',
            function (Blueprint $table) {
                if (
                    !Schema::hasColumn(
                        'identity_access_requests',
                        'requested_at'
                    )
                ) {
                    $table->timestamp(
                        'requested_at'
                    )->nullable();
                }

                if (
                    !Schema::hasColumn(
                        'identity_access_requests',
                        'expires_at'
                    )
                ) {
                    $table->timestamp(
                        'expires_at'
                    )->nullable();
                }

                if (
                    !Schema::hasColumn(
                        'identity_access_requests',
                        'last_viewed_at'
                    )
                ) {
                    $table->timestamp(
                        'last_viewed_at'
                    )->nullable();
                }

                if (
                    !Schema::hasColumn(
                        'identity_access_requests',
                        'view_count'
                    )
                ) {
                    $table->unsignedInteger(
                        'view_count'
                    )->default(0);
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Remove duplicate homeowner / worker access rows
        |--------------------------------------------------------------------------
        |
        | Keep the newest record for each homeowner + worker pair.
        |
        */

        $duplicates = DB::table(
            'identity_access_requests'
        )
            ->select(
                'homeowner_id',
                'worker_id',
                DB::raw('MAX(id) as keep_id'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(
                'homeowner_id',
                'worker_id'
            )
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table(
                'identity_access_requests'
            )
                ->where(
                    'homeowner_id',
                    $duplicate->homeowner_id
                )
                ->where(
                    'worker_id',
                    $duplicate->worker_id
                )
                ->where(
                    'id',
                    '<>',
                    $duplicate->keep_id
                )
                ->delete();
        }

        /*
        |--------------------------------------------------------------------------
        | Add homeowner + worker unique index only if missing
        |--------------------------------------------------------------------------
        */

        if (
            !Schema::hasIndex(
                'identity_access_requests',
                'identity_access_homeowner_worker_unique'
            )
        ) {
            Schema::table(
                'identity_access_requests',
                function (Blueprint $table) {
                    $table->unique(
                        [
                            'homeowner_id',
                            'worker_id',
                        ],
                        'identity_access_homeowner_worker_unique'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Add expires_at index only if missing
        |--------------------------------------------------------------------------
        */

        if (
            !Schema::hasIndex(
                'identity_access_requests',
                ['expires_at']
            )
        ) {
            Schema::table(
                'identity_access_requests',
                function (Blueprint $table) {
                    $table->index(
                        'expires_at'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | We deliberately do not restore the old consent schema.
        |--------------------------------------------------------------------------
        */
    }
};
