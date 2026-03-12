<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Uzum\UzumAutoReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class UzumReviewsController extends Controller
{
    public function __construct(
        private readonly UzumAutoReviewService $reviewService,
    ) {}

    /**
     * Получить список отзывов аккаунта
     */
    public function index(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $token = $account->uzum_access_token ?? $account->oauth_token;

        if (! $token) {
            return response()->json(['message' => 'Нет токена Uzum.'], 400);
        }

        $page   = (int) $request->get('page', 0);
        $filter = $request->get('filter', 'all'); // all, unanswered, answered

        $url = 'https://api-seller.uzum.uz/api/seller/product-reviews?' . http_build_query([
            'page' => $page,
            'size' => 20,
        ]);

        $response = Http::withToken($token)->timeout(30)->post($url, (object) []);

        if (! $response->successful()) {
            Log::warning('UzumReviews: ошибка получения отзывов', [
                'account_id' => $account->id,
                'status'     => $response->status(),
            ]);

            return response()->json(['message' => 'Ошибка получения отзывов от Uzum.'], 502);
        }

        $reviews = $response->json('payload', []);

        // Фильтрация
        if ($filter === 'unanswered') {
            $reviews = array_values(array_filter($reviews, fn ($r) => ($r['replyStatus'] ?? null) === null));
        } elseif ($filter === 'answered') {
            $reviews = array_values(array_filter($reviews, fn ($r) => ($r['replyStatus'] ?? null) !== null));
        }

        return response()->json([
            'reviews'  => $reviews,
            'page'     => $page,
            'has_more' => count($response->json('payload', [])) === 20,
        ]);
    }

    /**
     * Отправить ответ на отзыв
     */
    public function reply(Request $request, MarketplaceAccount $account, int $reviewId): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $token = $account->uzum_access_token ?? $account->oauth_token;

        if (! $token) {
            return response()->json(['message' => 'Нет токена Uzum.'], 400);
        }

        $response = Http::withToken($token)->timeout(30)->post(
            'https://api-seller.uzum.uz/api/seller/product-reviews/reply/create',
            [['reviewId' => $reviewId, 'content' => $validated['content']]]
        );

        if (! $response->successful()) {
            Log::warning('UzumReviews: ошибка отправки ответа', [
                'account_id' => $account->id,
                'review_id'  => $reviewId,
                'status'     => $response->status(),
            ]);

            return response()->json(['message' => 'Ошибка отправки ответа.'], 502);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Сгенерировать ответ через AI
     */
    public function aiReply(Request $request, MarketplaceAccount $account, int $reviewId): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $token = $account->uzum_access_token ?? $account->oauth_token;

        if (! $token) {
            return response()->json(['message' => 'Нет токена Uzum.'], 400);
        }

        // Получаем данные отзыва
        $reviewResponse = Http::withToken($token)->timeout(30)
            ->get("https://api-seller.uzum.uz/api/seller/product-reviews/review/{$reviewId}");

        if (! $reviewResponse->successful()) {
            return response()->json(['message' => 'Отзыв не найден.'], 404);
        }

        $review = $reviewResponse->json();

        $rating      = $review['rating'] ?? 0;
        $text        = $review['content'] ?? '';
        $pros        = $review['pros'] ?? '';
        $cons        = $review['cons'] ?? '';
        $productName = $review['product']['productTitle'] ?? '';
        $tone        = $account->uzum_review_tone ?? 'friendly';

        $fullText = collect([$text, $pros ? "Плюсы: $pros" : '', $cons ? "Минусы: $cons" : ''])
            ->filter()->implode(' | ');

        // Быстрый шаблон для пустых положительных отзывов
        if (empty(trim($fullText)) && $rating >= 4) {
            $templates = [
                "Спасибо за оценку! Рады, что вам понравилось. Ждём вас снова! 🙏",
                "Благодарим за покупку и высокую оценку! Будем рады видеть вас снова.",
                "Спасибо за отзыв! Приятно знать, что всё понравилось. До новых покупок!",
            ];

            return response()->json(['text' => $templates[array_rand($templates)]]);
        }

        // Генерация через Claude AI
        $aiApiKey = config('services.anthropic.key', env('ANTHROPIC_API_KEY', ''));

        if (! $aiApiKey) {
            return response()->json(['message' => 'AI не настроен.'], 500);
        }

        $toneInstructions = match ($tone) {
            'professional' => 'Пиши в профессиональном деловом тоне.',
            'casual'       => 'Пиши в дружелюбном неформальном тоне, можно эмодзи.',
            default        => 'Пиши дружелюбно и тепло, но профессионально.',
        };

        $ratingText  = $rating >= 4 ? 'положительный' : ($rating >= 3 ? 'нейтральный' : 'негативный');
        $userPrompt  = "Напиши ответ на $ratingText отзыв (оценка: $rating/5)."
            . ($productName ? " Товар: $productName." : '')
            . ($fullText ? " Текст: $fullText" : ' Без текста.');

        $aiResponse = Http::withHeaders([
            'x-api-key'         => $aiApiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 300,
            'system'     => "Ты — ассистент по ответам на отзывы на Uzum Market. $toneInstructions Ответ 1-3 предложения, до 500 символов. Не используй 'уважаемый', не давай ложных обещаний.",
            'messages'   => [['role' => 'user', 'content' => $userPrompt]],
        ]);

        if (! $aiResponse->successful()) {
            return response()->json(['message' => 'Ошибка AI генерации.'], 502);
        }

        return response()->json(['text' => $aiResponse->json('content.0.text')]);
    }
}
