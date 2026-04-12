<?php

// file: app/Modules/UzumAnalytics/Services/TokenRefreshService.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Services;

use App\Modules\UzumAnalytics\Models\UzumToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Управление пулом JWT токенов для публичного API Uzum.
 *
 * Поддерживает минимальный размер пула, round-robin ротацию,
 * автообновление истекающих токенов.
 */
class TokenRefreshService
{
    private readonly array $config;

    public function __construct()
    {
        $this->config = config('uzum-crawler.token_pool');
    }

    /**
     * Получить активный токен (round-robin, наименьший requests_count)
     */
    public function getToken(): ?UzumToken
    {
        $maxRequests = $this->config['max_requests'];

        $token = UzumToken::usable()
            ->where('requests_count', '<', $maxRequests)
            ->orderBy('requests_count')
            ->first();

        if ($token) {
            $token->incrementRequests();

            return $token;
        }

        // Пул пуст или все токены заполнены — получаем новый
        return $this->createToken();
    }

    /**
     * Обновить пул токенов (вызывается по расписанию каждые 5 минут)
     */
    public function refreshPool(): void
    {
        // Деактивировать истёкшие токены
        UzumToken::where('expires_at', '<', now())->update(['is_active' => false]);

        // Деактивировать переполненные токены
        UzumToken::where('requests_count', '>=', $this->config['max_requests'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Проверить текущий размер пула
        $activeCount = UzumToken::active()->count();
        $needed = $this->config['min_size'] - $activeCount;

        // Создать токены до минимального размера
        for ($i = 0; $i < $needed; $i++) {
            $this->createToken();
        }

        // Обновить токены, которые скоро истекут
        UzumToken::active()
            ->where('expires_at', '<', now()->addMinutes($this->config['refresh_before']))
            ->get()
            ->each(function (UzumToken $token): void {
                $token->update(['is_active' => false]);
                $this->createToken();
            });

        Log::info('UzumCrawler: пул токенов обновлён', [
            'active_before' => $activeCount,
            'active_now' => UzumToken::active()->count(),
        ]);
    }

    /**
     * Получить анонимный JWT от публичного API Uzum
     */
    private function createToken(): ?UzumToken
    {
        try {
            $userAgent = $this->getRandomUserAgent();

            $response = Http::withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'application/json',
                'Accept-Language' => 'ru-RU,ru;q=0.9,uz;q=0.8',
                'Origin' => 'https://uzum.uz',
                'Referer' => 'https://uzum.uz/',
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post(config('uzum-crawler.token_url'), []);

            if (! $response->successful()) {
                Log::warning('UzumCrawler: не удалось получить токен', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // API может вернуть token в разных полях
            $jwt = $data['access_token']
                ?? $data['accessToken']
                ?? $data['token']
                ?? $data['jwt']
                ?? null;

            if (! $jwt) {
                Log::warning('UzumCrawler: токен отсутствует в ответе', ['response' => $data]);

                return null;
            }

            // Извлечь iid из JWT payload (если есть), иначе сгенерировать UUID
            $iid = $this->extractIidFromJwt($jwt) ?? Str::uuid()->toString();

            return UzumToken::create([
                'token' => $jwt,
                'iid' => $iid,
                'expires_at' => now()->addMinutes($this->config['ttl_minutes']),
                'requests_count' => 0,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('UzumCrawler: ошибка получения токена', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Извлечь iid из payload JWT токена
     */
    private function extractIidFromJwt(string $jwt): ?string
    {
        try {
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(
                base64_decode(strtr($parts[1], '-_', '+/')),
                true,
            );

            return $payload['iid'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function getRandomUserAgent(): string
    {
        $agents = config('uzum-crawler.user_agents', []);

        return $agents[array_rand($agents)];
    }
}
