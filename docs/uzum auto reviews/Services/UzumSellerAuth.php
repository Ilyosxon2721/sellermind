<?php

namespace App\Services;

use App\Models\UzumShop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UzumSellerAuth
{
    /**
     * OAuth2 Token Endpoint
     */
    protected string $tokenUrl = 'https://api-seller.uzum.uz/api/oauth/token';

    /**
     * Check Token Endpoint (валидация + получение профиля)
     */
    protected string $checkTokenUrl = 'https://api-seller.uzum.uz/api/auth/seller/check_token';

    /**
     * OAuth2 client credentials (из check_token ответа: client_id = "b2b-front")
     * Если требуется Basic auth — задай client_secret в .env
     */
    protected string $clientId;

    protected string $clientSecret;

    public function __construct()
    {
        $this->clientId = config('uzum.oauth_client_id', 'b2b-front');
        $this->clientSecret = config('uzum.oauth_client_secret', '');
    }

    // ─────────────────────────────────────────────
    // LOGIN (OAuth2 Password Grant)
    // ─────────────────────────────────────────────

    /**
     * Авторизация по email + password
     *
     * Возвращает: [
     *   'access_token' => 'yDc8ITGPdMY...',
     *   'refresh_token' => '...',
     *   'token_type' => 'bearer',
     *   'expires_in' => 3600,
     *   'scope' => 'read write',
     * ]
     */
    public function login(string $username, string $password): array
    {
        try {
            $request = Http::asForm()
                ->accept('application/json')
                ->timeout(30);

            // Если есть client_secret — используем Basic auth
            if (! empty($this->clientSecret)) {
                $request = $request->withBasicAuth($this->clientId, $this->clientSecret);
            }

            $response = $request->post($this->tokenUrl, [
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('UzumAuth: логин успешен', [
                    'username' => $this->maskEmail($username),
                    'expires_in' => $data['expires_in'] ?? 'unknown',
                ]);

                return [
                    'success' => true,
                    'access_token' => $data['access_token'] ?? null,
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_type' => $data['token_type'] ?? 'bearer',
                    'expires_in' => $data['expires_in'] ?? 3600,
                    'scope' => $data['scope'] ?? '',
                ];
            }

            Log::warning('UzumAuth: ошибка логина', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error_description', $response->json('error', 'Ошибка авторизации')),
                'status' => $response->status(),
            ];

        } catch (\Throwable $e) {
            Log::error('UzumAuth: исключение при логине', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────
    // REFRESH TOKEN
    // ─────────────────────────────────────────────

    /**
     * Обновить access_token через refresh_token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $request = Http::asForm()
                ->accept('application/json')
                ->timeout(30);

            if (! empty($this->clientSecret)) {
                $request = $request->withBasicAuth($this->clientId, $this->clientSecret);
            }

            $response = $request->post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'access_token' => $data['access_token'] ?? null,
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_in' => $data['expires_in'] ?? 3600,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error_description', 'Ошибка обновления токена'),
                'status' => $response->status(),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────
    // CHECK TOKEN (валидация + профиль)
    // ─────────────────────────────────────────────

    /**
     * Проверить токен и получить профиль продавца
     *
     * Возвращает: sellerId, shopIds, permissions и т.д.
     */
    public function checkToken(string $token): array
    {
        try {
            $response = Http::asForm()
                ->accept('application/json')
                ->timeout(15)
                ->post($this->checkTokenUrl, [
                    'token' => $token,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'seller_id' => $data['sellerId'] ?? null,
                    'account_id' => $data['accountId'] ?? null,
                    'email' => $data['email'] ?? null,
                    'first_name' => $data['firstName'] ?? null,
                    'phone' => $data['phoneNumber'] ?? null,
                    'shop_ids' => array_keys($data['organizations'] ?? []),
                    'permissions' => $data['permissions'] ?? [],
                    'raw' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'Token invalid or expired',
                'status' => $response->status(),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────
    // TOKEN MANAGEMENT (для UzumShop модели)
    // ─────────────────────────────────────────────

    /**
     * Логин и сохранение токенов в модель магазина
     */
    public function loginAndSave(UzumShop $shop, string $username, string $password): array
    {
        $result = $this->login($username, $password);

        if (! $result['success']) {
            return $result;
        }

        // Сохраняем токены
        $shop->update([
            'session_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_expires_at' => now()->addSeconds($result['expires_in'] - 60), // с запасом в 1 мин
        ]);

        // Получаем профиль для обновления seller_id и shop_ids
        $profile = $this->checkToken($result['access_token']);

        if ($profile['success']) {
            $shop->update([
                'seller_id' => $profile['seller_id'],
            ]);
        }

        return $result;
    }

    /**
     * Получить актуальный session token (auto-refresh если истёк)
     */
    public function getValidToken(UzumShop $shop): ?string
    {
        // Токен ещё валидный
        if ($shop->session_token && $shop->token_expires_at && $shop->token_expires_at->isFuture()) {
            return $shop->session_token;
        }

        // Пробуем refresh
        if ($shop->refresh_token) {
            Log::info("UzumAuth: обновляем токен для магазина #{$shop->uzum_shop_id}");

            $result = $this->refreshToken($shop->refresh_token);

            if ($result['success']) {
                $shop->update([
                    'session_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'] ?? $shop->refresh_token,
                    'token_expires_at' => now()->addSeconds(($result['expires_in'] ?? 3600) - 60),
                ]);

                return $result['access_token'];
            }

            Log::warning('UzumAuth: refresh_token не сработал', [
                'shop_id' => $shop->uzum_shop_id,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }

        // Пробуем ре-логин если сохранены credentials
        if ($shop->seller_email && $shop->seller_password) {
            Log::info("UzumAuth: ре-логин для магазина #{$shop->uzum_shop_id}");

            $result = $this->login($shop->seller_email, $shop->seller_password);

            if ($result['success']) {
                $shop->update([
                    'session_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_expires_at' => now()->addSeconds(($result['expires_in'] ?? 3600) - 60),
                ]);

                return $result['access_token'];
            }
        }

        Log::error("UzumAuth: не удалось получить токен для магазина #{$shop->uzum_shop_id}");

        return null;
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }

        $name = $parts[0];
        $masked = substr($name, 0, 2).str_repeat('*', max(0, strlen($name) - 2));

        return $masked.'@'.$parts[1];
    }
}
