<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PurgeExpiredUserAccounts extends Command
{
    protected $signature = 'accounts:purge-expired';

    protected $description =
        'Anonymize personal data for user accounts whose 30-day deletion grace period has expired.';

    public function handle(): int
    {
        $users = User::query()
            ->where('account_status', 'pending_deletion')
            ->whereNotNull('deletion_scheduled_for')
            ->where('deletion_scheduled_for', '<=', now())
            ->get();

        foreach ($users as $user) {
            try {
                $this->purgeUser($user);
                $this->info(
                    "Purged account {$user->id}"
                );
            } catch (Throwable $exception) {
                report($exception);
                $this->error(
                    "Failed to purge account {$user->id}"
                );
            }
        }

        return self::SUCCESS;
    }

    private function purgeUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $workerProfile = $user->workerProfile;
            $homeownerProfile = $user->homeownerProfile;

            /*
            |--------------------------------------------------------------------------
            | Delete personal media
            |--------------------------------------------------------------------------
            */

            $publicFiles = [];

            if (!empty($user->profile_photo)) {
                $publicFiles[] = $user->profile_photo;
            }

            if ($workerProfile !== null) {
                if (!empty($workerProfile->profile_photo)) {
                    $publicFiles[] =
                        $workerProfile->profile_photo;
                }

                if (
                    method_exists(
                        $workerProfile,
                        'galleryImages'
                    )
                ) {
                    foreach (
                        $workerProfile->galleryImages
                        as $image
                    ) {
                        if (!empty($image->image_path)) {
                            $publicFiles[] =
                                $image->image_path;
                        }
                    }
                }

                foreach ([
                    $workerProfile
                        ->national_id_front_document
                        ?? null,
                    $workerProfile
                        ->national_id_back_document
                        ?? null,
                ] as $privateId) {
                    if (!empty($privateId)) {
                        Storage::disk('local')
                            ->delete($privateId);

                        // Legacy fallback for IDs uploaded
                        // before private storage was added.
                        Storage::disk('public')
                            ->delete($privateId);
                    }
                }
            }

            if ($homeownerProfile !== null) {
                if (!empty($homeownerProfile->profile_photo)) {
                    $publicFiles[] =
                        $homeownerProfile->profile_photo;
                }
            }

            foreach (
                array_unique(
                    array_filter($publicFiles)
                )
                as $path
            ) {
                Storage::disk('public')->delete($path);
            }

            /*
            |--------------------------------------------------------------------------
            | Remove profile / device data
            |--------------------------------------------------------------------------
            */

            $user->deviceTokens()->delete();
            $user->tokens()->delete();

            if ($workerProfile !== null) {
                $workerProfile->galleryImages()->delete();
                $workerProfile->delete();
            }

            if ($homeownerProfile !== null) {
                $homeownerProfile->delete();
            }

            /*
            |--------------------------------------------------------------------------
            | Anonymize user instead of deleting the row
            |--------------------------------------------------------------------------
            |
            | Jobs, applications, conversations, payments and reviews may have
            | foreign keys to this user. Keeping an anonymized row protects
            | transactional integrity while removing personal account data.
            |
            */

            $user->forceFill([
                'full_name' => 'Deleted User',
                'phone' => 'deleted-'.$user->id.'-'.now()->timestamp,
                'email' => null,
                'profile_photo' => null,
                'location' => null,
                'latitude' => null,
                'longitude' => null,
                'password' => bcrypt(
                    bin2hex(random_bytes(32))
                ),
                'is_verified' => false,
                'account_status' => 'deleted',
                'account_status_source' => 'system',
                'account_status_reason' =>
                    'User deletion grace period completed.',
                'account_status_changed_at' => now(),
                'account_status_changed_by' => null,
                'deleted_at_app' => now(),
            ])->save();
        });
    }
}
