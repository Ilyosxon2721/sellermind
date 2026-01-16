<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {email?}';
    protected $description = 'Make a user admin by email';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (!$email) {
            // Show list of users to choose from
            $users = User::select('id', 'email', 'name', 'is_admin')->get();

            if ($users->isEmpty()) {
                $this->error('No users found in database.');
                return 1;
            }

            $this->table(
                ['ID', 'Email', 'Name', 'Admin?'],
                $users->map(fn($u) => [$u->id, $u->email, $u->name, $u->is_admin ? 'Yes' : 'No'])
            );

            $email = $this->ask('Enter email of user to make admin');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if ($user->is_admin) {
            $this->info("User {$user->email} is already an admin.");
            return 0;
        }

        $user->update(['is_admin' => true]);
        $this->info("User {$user->email} ({$user->name}) is now an admin.");

        return 0;
    }
}
