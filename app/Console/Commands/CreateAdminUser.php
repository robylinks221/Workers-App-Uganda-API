<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create or promote a WorkLink Africa administrator.';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Admin full name'));
        $phone = trim((string) $this->ask('Admin phone number'));
        $email = trim((string) $this->ask('Admin email (optional)'));
        $password = (string) $this->secret('Admin password');

        if ($name === '' || $phone === '' || strlen($password) < 8) {
            $this->error('Name, phone and a password of at least 8 characters are required.');
            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['phone' => $phone]);
        $user->full_name = $name;
        $user->email = $email !== '' ? $email : null;
        $user->role = 'admin';
        $user->password = Hash::make($password);
        $user->is_verified = true;
        $user->save();

        $this->info("Admin ready: {$user->full_name} (user #{$user->id})");

        return self::SUCCESS;
    }
}
