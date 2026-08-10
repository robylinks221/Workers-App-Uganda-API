<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebasePushService;
use Illuminate\Console\Command;
use Throwable;

class SendTestPush extends Command
{
    protected $signature = 'firebase:test-push
        {user_id : The users.id that should receive the push}
        {--title=Maids App Test : Push title}
        {--body=Firebase push notifications are working. : Push body}';

    protected $description =
        'Send a direct Firebase push to one user for testing.';

    public function handle(
        FirebasePushService $firebasePushService
    ): int {
        $userId = (int) $this->argument('user_id');

        $user = User::query()->find($userId);

        if (!$user) {
            $this->error("User {$userId} was not found.");
            return self::FAILURE;
        }

        $this->info(
            "Sending Firebase test push to {$user->full_name} " .
            "(user {$user->id})..."
        );

        try {
            $result = $firebasePushService->sendToUser(
                $user->id,
                (string) $this->option('title'),
                (string) $this->option('body'),
                [
                    'type' => 'firebase_test',
                    'action_type' => 'none',
                ]
            );

            $this->line(
                'Registered devices: ' . $result['devices']
            );
            $this->line(
                'Pushes sent: ' . $result['sent']
            );
            $this->line(
                'Pushes failed: ' . $result['failed']
            );
            $this->line(
                'Invalid tokens removed: ' . $result['removed']
            );

            return $result['sent'] > 0
                ? self::SUCCESS
                : self::FAILURE;
        } catch (Throwable $error) {
            $this->error($error->getMessage());
            return self::FAILURE;
        }
    }
}
