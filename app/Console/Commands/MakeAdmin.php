<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:admin {email} {--name=} {--password=}';

    protected $description = 'Promote a user to admin, or create an admin account if the email is unknown';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['is_admin' => true]);
            $this->info("{$user->name} <{$user->email}> is now an admin.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $password = $this->option('password') ?: $this->secret('Password');

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
        ]);

        $this->info("Created admin {$user->name} <{$user->email}>.");

        return self::SUCCESS;
    }
}
