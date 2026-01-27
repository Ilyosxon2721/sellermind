<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password
                            {email : Email пользователя}
                            {--password= : Установить конкретный пароль (иначе генерируется случайный)}';

    protected $description = 'Сброс пароля пользователя';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Пользователь с email {$email} не найден");
            return Command::FAILURE;
        }

        $password = $this->option('password') ?: Str::random(12);

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Пароль для {$email} успешно обновлён");
        $this->newLine();
        $this->line("Email: {$email}");
        $this->line("Новый пароль: <fg=green;options=bold>{$password}</>");
        $this->newLine();
        $this->warn("Сохраните пароль - он больше не будет показан!");

        return Command::SUCCESS;
    }
}
