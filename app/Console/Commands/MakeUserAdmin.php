<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeUserAdmin extends Command
{
    protected $signature = 'admin:create {login?} {--password=}';
    protected $description = 'Create a new admin user for Filament panel';

    public function handle(): int
    {
        $login = $this->argument('login');

        if (!$login) {
            $login = $this->ask('Enter login for admin');
        }

        if (Admin::where('login', $login)->exists()) {
            $this->error("Admin with login '{$login}' already exists.");
            return 1;
        }

        $name = $this->ask('Enter name', $login);
        $email = $this->ask('Enter email (optional, press Enter to skip)');

        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Enter password');
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');
            return 1;
        }

        $admin = Admin::create([
            'name' => $name,
            'login' => $login,
            'email' => $email ?: null,
            'password' => $password,
            'is_active' => true,
        ]);

        $this->info("Admin '{$admin->login}' created successfully!");
        $this->info("Login URL: /admin");

        return 0;
    }
}
