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

        $tokenUrl = 'https://api-seller.uzum.uz/api/oauth/token';
        $clientId = config('uzum.oauth_client_id', 'b2b-front');
        $clientSecret = config('uzum.oauth_client_secret', '');

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

        $page = (int) $request->input('page', 0);
        $size = (int) $request->input('size', 20);

        $url = 'https://api-seller.uzum.uz/api/seller/product-reviews?' . http_build_query([
            'page' => $page,
            'size' => $size,
        ]);

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post($url, (object) []);

            if ($response->status() === 401) {
                return response()->json([
                    'success' => false,
                    'message' => 'Токен Uzum истёк. Авторизуйтесь заново.',
                    'auth_required' => true,
                ], 401);
            }

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка загрузки отзывов: ' . $response->status(),
                ], $response->status());
            }

            $reviews = $response->json('payload', []);

            return response()->json([
                'success' => true,
                'reviews' => $reviews,
                'page' => $page,
                'size' => $size,
                'total' => count($reviews),
            ]);
        } catch (\Exception $e) {
            Log::error("Uzum reviews fetch error: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Ошибка подключения к Uzum',
            ], 500);
        }
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
            $response = Http::withToken($token)
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
