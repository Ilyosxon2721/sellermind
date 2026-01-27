<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Make all existing users admins (for initial setup)
     * Or specify email via argument: php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        // Make first user admin (usually the owner)
        $user = User::first();

        if ($user) {
            $user->update(['is_admin' => true]);
            $this->command->info("User {$user->email} is now admin.");
        } else {
            $this->command->warn('No users found in database.');
        }
    }
}
