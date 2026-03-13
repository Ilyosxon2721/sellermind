<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class UzumReviewController extends Controller
{
    /**
     * Проверить наличие токена для отзывов
     */
    public function checkAuth(int $accountId): JsonResponse
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        $token = $account->uzum_access_token ?? $account->uzum_api_key ?? $account->api_key;

        return response()->json([
            'authenticated' => ! empty($token),
        ]);
    }

    /**
     * Авторизоваться в Uzum Seller через OAuth2 Password Grant
     */
    public function login(Request $request, int $accountId): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = MarketplaceAccount::findOrFail($accountId);

        $tokenUrl = config('uzum.oauth_token_url', 'https://api-seller.uzum.uz/api/oauth/token');
        $clientId = config('uzum.oauth_client_id', 'b2b-front');
        $clientSecret = config('uzum.oauth_client_secret', 'clientSecret');

        try {
            // Uzum OAuth2 требует Basic Auth с client_id (даже без secret)
            $httpRequest = Http::asForm()
                ->accept('application/json')
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(30);

            $formData = [
                'grant_type' => 'password',
                'username' => $request->input('login'),
                'password' => $request->input('password'),
            ];

            Log::debug("Uzum OAuth2 login attempt for account #{$accountId}", [
                'url' => $tokenUrl,
                'username' => $request->input('login'),
                'client_id' => $clientId,
            ]);

            $response = $httpRequest->post($tokenUrl, $formData);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                $refreshToken = $data['refresh_token'] ?? null;
                $expiresIn = $data['expires_in'] ?? 3600;

                if ($token) {
                    // Модель сама шифрует через mutator
                    $account->uzum_access_token = $token;
                    if ($refreshToken) {
                        $account->uzum_refresh_token = $refreshToken;
                    }
                    $account->uzum_token_expires_at = now()->addSeconds($expiresIn - 60);
                    $account->save();

                    Log::info("Uzum OAuth2 auth success for account #{$accountId}");

                    return response()->json([
                        'success' => true,
                        'message' => 'Авторизация успешна',
                    ]);
                }
            }

            $errorMessage = $response->json('error_description')
                ?? $response->json('error')
                ?? $response->body();

            Log::warning("Uzum OAuth2 auth failed for account #{$accountId}", [
                'status' => $response->status(),
                'error' => $errorMessage,
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось авторизоваться в Uzum. Проверьте логин и пароль.',
                'debug' => $errorMessage,
                'status_code' => $response->status(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Uzum OAuth2 auth exception for account #{$accountId}: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Ошибка подключения к Uzum',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Сохранить токен напрямую (если пользователь вставляет вручную)
     */
    public function saveToken(Request $request, int $accountId): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|min:10',
        ]);

        $account = MarketplaceAccount::findOrFail($accountId);
        // Модель сама шифрует через mutator
        $account->uzum_access_token = $request->input('token');
        $account->uzum_token_expires_at = now()->addDays(30);
        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Токен сохранён',
        ]);
    }

    /**
     * Загрузить отзывы из Uzum
     */
    public function reviews(Request $request, int $accountId): JsonResponse
    {
        $account = MarketplaceAccount::findOrFail($accountId);
        $token = $account->uzum_access_token ?? $account->uzum_api_key ?? $account->api_key;

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация в Uzum',
            ], 401);
        }

        Log::debug("Uzum reviews: token info for account #{$accountId}", [
            'token_length' => strlen($token),
            'token_start' => substr($token, 0, 20),
            'is_jwt' => str_starts_with($token, 'eyJ'),
            'has_refresh' => ! empty($account->uzum_refresh_token),
            'expires_at' => $account->uzum_token_expires_at,
        ]);

        $page = (int) $request->input('page', 0);
        $size = (int) $request->input('size', 20);

        $response = $this->fetchReviews($token, $page, $size, $accountId);

        // Если 401 и есть refresh_token — попробовать обновить токен
        if ($response->status() === 401 && $account->uzum_refresh_token) {
            Log::info("Uzum reviews: attempting token refresh for account #{$accountId}");
            $newToken = $this->refreshToken($account);
            if ($newToken) {
                $token = $newToken;
                $response = $this->fetchReviews($token, $page, $size, $accountId);
            }
        }

        // Если всё ещё 401 — попробовать без Bearer (некоторые эндпоинты Uzum не хотят Bearer)
        if ($response->status() === 401) {
            $rawToken = $account->uzum_access_token ?? $account->uzum_api_key ?? $account->api_key;
            Log::info("Uzum reviews: retrying without Bearer for account #{$accountId}");
            $response = $this->fetchReviewsRaw($rawToken, $page, $size, $accountId);
        }

        Log::debug("Uzum reviews API final response for account #{$accountId}", [
            'status' => $response->status(),
            'body_preview' => mb_substr($response->body(), 0, 2000),
        ]);

        if ($response->status() === 401) {
            return response()->json([
                'success' => false,
                'message' => 'Токен Uzum истёк. Авторизуйтесь заново.',
                'auth_required' => true,
            ], 401);
        }

        if (! $response->successful()) {
            Log::warning("Uzum reviews failed for account #{$accountId}", [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 2000),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки отзывов: ' . $response->status(),
            ], $response->status());
        }

        $json = $response->json();

        // Uzum может возвращать отзывы в разных форматах
        $reviews = $json['payload'] ?? $json['productReviews'] ?? $json['reviews']
            ?? $json['data'] ?? $json['content'] ?? $json['items'] ?? [];

        // Если ответ — массив напрямую (без обёртки)
        if (empty($reviews) && isset($json[0])) {
            $reviews = $json;
        }

        Log::info("Uzum reviews loaded for account #{$accountId}", [
            'count' => count($reviews),
            'response_keys' => is_array($json) ? array_keys($json) : [],
        ]);

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'page' => $page,
            'size' => $size,
            'total' => count($reviews),
            'debug_keys' => config('app.debug') ? (is_array($json) ? array_keys($json) : []) : null,
        ]);
    }

    /**
     * Запрос отзывов с Bearer-префиксом
     */
    private function fetchReviews(string $token, int $page, int $size, int $accountId): \Illuminate\Http\Client\Response
    {
        $url = 'https://api-seller.uzum.uz/api/seller/product-reviews?' . http_build_query([
            'page' => $page,
            'size' => $size,
        ]);

        $authValue = str_starts_with($token, 'eyJ') ? "Bearer {$token}" : $token;

        return Http::withHeaders([
            'Authorization' => $authValue,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, (object) []);
    }

    /**
     * Запрос отзывов без Bearer-префикса (fallback)
     */
    private function fetchReviewsRaw(string $token, int $page, int $size, int $accountId): \Illuminate\Http\Client\Response
    {
        $url = 'https://api-seller.uzum.uz/api/seller/product-reviews?' . http_build_query([
            'page' => $page,
            'size' => $size,
        ]);

        return Http::withHeaders([
            'Authorization' => $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, (object) []);
    }

    /**
     * Обновить access_token через refresh_token
     */
    private function refreshToken(MarketplaceAccount $account): ?string
    {
        $tokenUrl = config('uzum.oauth_token_url', 'https://api-seller.uzum.uz/api/oauth/token');
        $clientId = config('uzum.oauth_client_id', 'b2b-front');
        $clientSecret = config('uzum.oauth_client_secret', 'clientSecret');

        try {
            $response = Http::asForm()
                ->accept('application/json')
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(30)
                ->post($tokenUrl, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $account->uzum_refresh_token,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $newToken = $data['access_token'] ?? null;

                if ($newToken) {
                    $account->uzum_access_token = $newToken;
                    if (! empty($data['refresh_token'])) {
                        $account->uzum_refresh_token = $data['refresh_token'];
                    }
                    $account->uzum_token_expires_at = now()->addSeconds(($data['expires_in'] ?? 3600) - 60);
                    $account->save();

                    Log::info("Uzum token refreshed for account #{$account->id}");

                    return $newToken;
                }
            }

            Log::warning("Uzum token refresh failed for account #{$account->id}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error("Uzum token refresh exception: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Ответить на отзыв
     */
    public function reply(Request $request, int $accountId): JsonResponse
    {
        $request->validate([
            'review_id' => 'required|integer',
            'content' => 'required|string|max:500',
        ]);

        $account = MarketplaceAccount::findOrFail($accountId);
        $token = $account->uzum_access_token ?? $account->uzum_api_key ?? $account->api_key;

        if (! $token) {
            return response()->json(['success' => false, 'message' => 'Требуется авторизация'], 401);
        }

        try {
            $authValue = str_starts_with($token, 'eyJ') ? "Bearer {$token}" : $token;
            $response = Http::withHeaders([
                    'Authorization' => $authValue,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post('https://api-seller.uzum.uz/api/seller/product-reviews/reply/create', [
                    [
                        'reviewId' => (int) $request->input('review_id'),
                        'content' => $request->input('content'),
                    ],
                ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Ответ отправлен']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка отправки ответа: ' . $response->status(),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
