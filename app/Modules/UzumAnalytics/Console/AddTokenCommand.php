<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Console;

use App\Modules\UzumAnalytics\Models\UzumToken;
use Illuminate\Console\Command;

/**
 * Добавить JWT токен Uzum в пул вручную.
 *
 * Использование:
 *   php artisan uzum-analytics:add-token
 *
 * Как получить токен:
 *   1. Открой uzum.uz в браузере
 *   2. DevTools → Console → localStorage.getItem('auth_sdk_access_token')
 *   3. Скопируй токен и вставь при запросе команды
 */
final class AddTokenCommand extends Command
{
    protected $signature = 'uzum-analytics:add-token {token? : JWT токен} {--ttl=720 : TTL в минутах (дефолт 12 часов)}';

    protected $description = 'Добавить JWT токен Uzum в пул вручную';

    public function handle(): int
    {
        $this->info('=== Uzum Analytics: Добавление токена ===');
        $this->newLine();

        $token = $this->argument('token')
            ?? $this->ask('Вставьте JWT токен (из localStorage uzum.uz)');

        if (empty($token)) {
            $this->error('Токен не указан.');
            return self::FAILURE;
        }

        // Базовая валидация — JWT состоит из 3 частей разделённых точкой
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            $this->error('Это не похоже на JWT токен (должно быть 3 части, разделённые точкой).');
            return self::FAILURE;
        }

        // Попробуем декодировать payload
        $payload = null;
        try {
            $payloadJson = base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) + (4 - strlen($parts[1]) % 4) % 4, '='));
            $payload     = json_decode($payloadJson, true);
        } catch (\Throwable) {
            // Не критично
        }

        $ttlMinutes = (int) $this->option('ttl');
        $expiresAt  = now()->addMinutes($ttlMinutes);

        // Если в payload есть exp — используем его
        if (isset($payload['exp'])) {
            $expiresAt = \Carbon\Carbon::createFromTimestamp($payload['exp']);
            $this->line('  Срок из токена: <comment>' . $expiresAt->diffForHumans() . '</comment>');
        }

        if ($expiresAt->isPast()) {
            $this->error('Токен уже истёк!');
            return self::FAILURE;
        }

        // Деактивируем старые токены
        $deactivated = UzumToken::where('is_active', true)->update(['is_active' => false]);
        if ($deactivated > 0) {
            $this->line("  Деактивировано старых токенов: {$deactivated}");
        }

        $iid = $payload['iid'] ?? \Illuminate\Support\Str::uuid()->toString();

        UzumToken::create([
            'token'          => $token,
            'iid'            => $iid,
            'expires_at'     => $expiresAt,
            'requests_count' => 0,
            'is_active'      => true,
        ]);

        $this->info('✓ Токен добавлен!');
        $this->table(['Поле', 'Значение'], [
            ['Истекает',    $expiresAt->format('d.m.Y H:i')],
            ['Через',       $expiresAt->diffForHumans()],
            ['IID',         $iid],
            ['Токен (часть)', substr($token, 0, 30) . '...'],
        ]);

        $this->newLine();
        $this->info('Теперь запусти синхронизацию категорий:');
        $this->line('  php artisan uzum-analytics:sync-categories');

        return self::SUCCESS;
    }
}
