<?php

declare(strict_types=1);

namespace App\Services\Uzum;

use App\Models\MarketplaceAccount;
use App\Services\Uzum\Api\UzumApiManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UzumAutoReviewService
{
    private UzumAiReplyService $aiService;

    public function __construct()
    {
        $this->aiService = new UzumAiReplyService();
    }

    /**
     * Обработать все неотвеченные отзывы для аккаунта
     *
     * @return array{processed: int, replied: int, skipped: int, errors: int}
     */
    public function processAccount(MarketplaceAccount $account): array
    {
        $stats = ['processed' => 0, 'replied' => 0, 'skipped' => 0, 'errors' => 0];

        $uzum = new UzumApiManager($account);

        $authValue = str_starts_with($token, 'eyJ') ? "Bearer {$token}" : $token;

        $page = 0;
        do {
            $url = 'https://api-seller.uzum.uz/api/seller/product-reviews?'.http_build_query([
                'page' => $page,
                'size' => 20,
            ]);

            $response = Http::withHeaders(['Authorization' => $authValue])->timeout(30)->post($url, (object) []);

            if (! $response->successful()) {
                break;
            }

            $reviews = $response['payload'] ?? [];
            $unanswered = array_filter($reviews, fn ($r) => ($r['replyStatus'] ?? null) === null);

            if (empty($unanswered)) {
                break;
            }

            foreach ($unanswered as $review) {
                $stats['processed']++;
                $result = $this->processReview($review, $account, $uzum);
                $stats[$result]++;
                usleep(500_000); // Пауза 0.5 сек между запросами
            }

            $page++;
            if ($page > 10) {
                break;
            }
        } while (! empty($unanswered));

        return $stats;
    }

    /**
     * Обработать один отзыв: сгенерировать и отправить ответ
     */
    private function processReview(array $review, MarketplaceAccount $account, UzumApiManager $uzum): string
    {
        $reviewId = $review['reviewId'] ?? null;
        if (! $reviewId) {
            return 'skipped';
        }

        // Проверяем, не отвечали ли уже на этот отзыв
        $exists = DB::table('uzum_review_reply_logs')
            ->where('marketplace_account_id', $account->id)
            ->where('uzum_review_id', $reviewId)
            ->exists();

        if ($exists) {
            return 'skipped';
        }

        $rating = $review['rating'] ?? 0;
        $text = $review['content'] ?? '';
        $pros = $review['pros'] ?? '';
        $cons = $review['cons'] ?? '';
        $productName = $review['product']['productTitle'] ?? '';

        $fullText = collect([
            $text,
            $pros ? "Плюсы: $pros" : '',
            $cons ? "Минусы: $cons" : '',
        ])->filter()->implode(' | ');

        // Генерация через AI-сервис
        $replyText = $this->aiService->generate(
            $rating,
            $fullText,
            $productName,
            $account->uzum_review_tone ?? 'friendly'
        );

        if (! $replyText) {
            return 'error';
        }

        // Отправляем ответ на отзыв
        $authValue = str_starts_with($token, 'eyJ') ? "Bearer {$token}" : $token;
        $sendResponse = Http::withHeaders(['Authorization' => $authValue])->timeout(30)->post(
            'https://api-seller.uzum.uz/api/seller/product-reviews/reply/create',
            [['reviewId' => $reviewId, 'content' => $replyText]]
        );

        $status = $sendResponse->successful() ? 'sent' : 'failed';

        DB::table('uzum_review_reply_logs')->insert([
            'marketplace_account_id' => $account->id,
            'uzum_review_id' => $reviewId,
            'rating' => $rating,
            'review_text' => mb_substr($fullText, 0, 1000),
            'reply_text' => $replyText,
            'product_name' => mb_substr($productName, 0, 255),
            'status' => $success ? 'sent' : 'failed',
            'error_message' => $errorMessage,
            'replied_at' => $success ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $success ? 'replied' : 'error';
    }
}
