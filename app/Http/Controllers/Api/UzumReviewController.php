<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Uzum\Api\UzumApiManager;
use App\Services\Uzum\UzumAiReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Единый контроллер отзывов Uzum.
 *
 * Обслуживает оба набора роутов:
 * - /api/uzum-reviews/{accountId}/...  (старые, frontend)
 * - /api/marketplace/uzum/accounts/{account}/reviews/...  (новые, REST)
 */
final class UzumReviewController extends Controller
{
    // ─── АВТОРИЗАЦИЯ ───────────────────────────────────────────

    /**
     * Проверить наличие токена для отзывов
     */
    public function checkAuth(int $accountId): JsonResponse
    {
        $account = MarketplaceAccount::findOrFail($accountId);
        $token = $this->resolveToken($account);

        return response()->json([
            'authenticated' => ! empty($token),
        ]);
    }

    /**
     * OAuth2 Password Grant — авторизация в Uzum
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
            $response = Http::asForm()
                ->accept('application/json')
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(30)
                ->post($tokenUrl, [
                    'grant_type' => 'password',
                    'username' => $request->input('login'),
                    'password' => $request->input('password'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;

                if ($token) {
                    $account->uzum_access_token = $token;
                    if (! empty($data['refresh_token'])) {
                        $account->uzum_refresh_token = $data['refresh_token'];
                    }
                    $account->uzum_token_expires_at = now()->addSeconds(($data['expires_in'] ?? 3600) - 60);
                    $account->save();

                    return response()->json(['success' => true, 'message' => 'Авторизация успешна']);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Не удалось авторизоваться. Проверьте логин и пароль.',
            ], 422);
        } catch (\Exception $e) {
            Log::error("Uzum OAuth2 error: {$e->getMessage()}", ['account_id' => $accountId]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка подключения к Uzum',
            ], 500);
        }
    }

    /**
     * Сохранить токен вручную
     */
    public function saveToken(Request $request, int $accountId): JsonResponse
    {
        $request->validate(['token' => 'required|string|min:10']);

        $account = MarketplaceAccount::findOrFail($accountId);
        $account->uzum_access_token = $request->input('token');
        $account->uzum_token_expires_at = now()->addDays(30);
        $account->save();

        return response()->json(['success' => true, 'message' => 'Токен сохранён']);
    }

    /**
     * Диагностика токена (только debug режим)
     */
    public function debug(int $accountId): JsonResponse
    {
        if (! config('app.debug')) {
            return response()->json(['message' => 'Debug disabled'], 403);
        }

        $account = MarketplaceAccount::findOrFail($accountId);
        $token = $this->resolveToken($account);

        return response()->json([
            'has_token' => ! empty($token),
            'token_length' => $token ? strlen($token) : 0,
            'token_start' => $token ? substr($token, 0, 30) . '...' : null,
            'is_jwt' => $token && str_starts_with($token, 'eyJ'),
            'has_refresh' => ! empty($account->uzum_refresh_token),
            'expires_at' => $account->uzum_token_expires_at,
        ]);
    }

    // ─── ОТЗЫВЫ ────────────────────────────────────────────────

    /**
     * Получить список отзывов
     *
     * Поддерживает оба формата:
     * - GET /api/uzum-reviews/{accountId}/reviews?page=0&size=20
     * - GET /api/marketplace/uzum/accounts/{account}/reviews?filter=unanswered
     */
    public function reviews(Request $request, int|MarketplaceAccount $accountId): JsonResponse
    {
        $account = $accountId instanceof MarketplaceAccount
            ? $accountId
            : MarketplaceAccount::findOrFail($accountId);

        // Проверка доступа (если вызвано через REST-роут с авторизованным пользователем)
        if ($request->user() && method_exists($request->user(), 'hasCompanyAccess')) {
            if (! $request->user()->hasCompanyAccess($account->company_id)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }
        }

        $token = $this->resolveToken($account);
        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация в Uzum',
                'auth_required' => true,
            ], 401);
        }

        $page = (int) $request->input('page', 0);
        $size = (int) $request->input('size', 20);
        $filter = $request->input('filter', 'all');

        // Запрос через UzumApiManager
        try {
            $uzum = new UzumApiManager($account);
            $response = $uzum->reviews()->list($page, $size);
            $reviews = $response['payload'] ?? $response['productReviews'] ?? $response['content'] ?? [];

            // Если ответ — массив напрямую
            if (empty($reviews) && isset($response[0])) {
                $reviews = $response;
            }
        } catch (\RuntimeException $e) {
            // Если 401 — попробовать refresh token
            if (str_contains($e->getMessage(), 'токен') || str_contains($e->getMessage(), '401')) {
                $newToken = $this->refreshToken($account);
                if ($newToken) {
                    try {
                        $uzum = new UzumApiManager($account->fresh());
                        $response = $uzum->reviews()->list($page, $size);
                        $reviews = $response['payload'] ?? $response['content'] ?? [];
                    } catch (\Exception) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Токен истёк. Авторизуйтесь заново.',
                            'auth_required' => true,
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Токен истёк. Авторизуйтесь заново.',
                        'auth_required' => true,
                    ], 401);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 502);
            }
        }

        // Фильтрация по статусу ответа
        if ($filter === 'unanswered') {
            $reviews = array_values(array_filter($reviews, fn ($r) => ($r['replyStatus'] ?? null) === null));
        } elseif ($filter === 'answered') {
            $reviews = array_values(array_filter($reviews, fn ($r) => ($r['replyStatus'] ?? null) !== null));
        }

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'page' => $page,
            'size' => $size,
            'total' => count($reviews),
            'has_more' => count($response['payload'] ?? $response['content'] ?? []) >= $size,
        ]);
    }

    /**
     * Ответить на отзыв
     *
     * Поддерживает оба формата:
     * - POST /api/uzum-reviews/{accountId}/reply          body: {review_id, content}
     * - POST /api/.../accounts/{account}/reviews/{reviewId}/reply  body: {content}
     */
    public function reply(Request $request, int|MarketplaceAccount $accountId, ?int $reviewId = null): JsonResponse
    {
        $account = $accountId instanceof MarketplaceAccount
            ? $accountId
            : MarketplaceAccount::findOrFail($accountId);

        if ($request->user() && method_exists($request->user(), 'hasCompanyAccess')) {
            if (! $request->user()->hasCompanyAccess($account->company_id)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }
        }

        $request->validate([
            'content' => 'required|string|max:1000',
            'review_id' => $reviewId ? 'nullable' : 'required|integer',
        ]);

        $reviewId = $reviewId ?? (int) $request->input('review_id');
        $content = $request->input('content');

        $token = $this->resolveToken($account);
        if (! $token) {
            return response()->json(['success' => false, 'message' => 'Требуется авторизация'], 401);
        }

        try {
            $uzum = new UzumApiManager($account);
            $uzum->reviews()->reply($reviewId, $content);

            return response()->json(['success' => true, 'status' => 'ok', 'message' => 'Ответ отправлен']);
        } catch (\Exception $e) {
            Log::warning('Uzum reply error', [
                'account_id' => $account->id,
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка отправки ответа: ' . $e->getMessage(),
            ], 502);
        }
    }

    /**
     * Сгенерировать ответ через Claude AI
     *
     * Поддерживает оба формата:
     * - POST /api/uzum-reviews/{accountId}/ai-reply       body: {review_id, review_text, rating, product_name}
     * - POST /api/.../accounts/{account}/reviews/{reviewId}/ai-reply
     */
    public function aiReply(Request $request, int|MarketplaceAccount $accountId, ?int $reviewId = null): JsonResponse
    {
        $account = $accountId instanceof MarketplaceAccount
            ? $accountId
            : MarketplaceAccount::findOrFail($accountId);

        if ($request->user() && method_exists($request->user(), 'hasCompanyAccess')) {
            if (! $request->user()->hasCompanyAccess($account->company_id)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }
        }

        $token = $this->resolveToken($account);
        if (! $token) {
            return response()->json(['message' => 'Требуется авторизация'], 401);
        }

        $reviewId = $reviewId ?? (int) $request->input('review_id');

        // Данные из запроса
        $reviewText = $request->input('review_text', '');
        $rating = (int) $request->input('rating', 0);
        $productName = $request->input('product_name', '');

        // Если данных нет — подтянуть из Uzum API
        if (empty($reviewText) && $reviewId) {
            try {
                $uzum = new UzumApiManager($account);
                $review = $uzum->reviews()->detail($reviewId);
                $reviewText = $review['content'] ?? '';
                $rating = $review['rating'] ?? $rating;
                $productName = $review['product']['productTitle'] ?? $productName;

                $pros = $review['pros'] ?? '';
                $cons = $review['cons'] ?? '';
                if ($pros) {
                    $reviewText .= " | Плюсы: {$pros}";
                }
                if ($cons) {
                    $reviewText .= " | Минусы: {$cons}";
                }
            } catch (\Exception $e) {
                Log::warning('Uzum: не удалось загрузить отзыв для AI', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Генерация через AI-сервис
        $aiService = new UzumAiReplyService();

        if (! $aiService->isConfigured() && ! (empty(trim($reviewText)) && $rating >= 4)) {
            return response()->json(['message' => 'AI не настроен.'], 500);
        }

        $tone = $account->uzum_review_tone ?? 'friendly';
        $text = $aiService->generate($rating, $reviewText, $productName, $tone);

        if (! $text) {
            return response()->json(['message' => 'Ошибка AI генерации.'], 502);
        }

        return response()->json(['text' => $text]);
    }

    // ─── ВНУТРЕННИЕ МЕТОДЫ ─────────────────────────────────────

    /**
     * Получить токен из аккаунта (единый порядок приоритетов)
     */
    private function resolveToken(MarketplaceAccount $account): ?string
    {
        return $account->uzum_access_token ?? $account->api_key ?? $account->oauth_token ?? null;
    }

    /**
     * Обновить access_token через refresh_token
     */
    private function refreshToken(MarketplaceAccount $account): ?string
    {
        if (! $account->uzum_refresh_token) {
            return null;
        }

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

                    Log::info('Uzum token refreshed', ['account_id' => $account->id]);

                    return $newToken;
                }
            }

            Log::warning('Uzum token refresh failed', [
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::error("Uzum token refresh exception: {$e->getMessage()}");
        }

        return null;
    }
}
