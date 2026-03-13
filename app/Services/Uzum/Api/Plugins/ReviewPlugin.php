<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api\Plugins;

use App\Services\Uzum\Api\UzumApi;
use App\Services\Uzum\Api\UzumEndpoints;

/**
 * Плагин: Отзывы (undocumented API)
 */
final class ReviewPlugin
{
    public function __construct(
        private readonly UzumApi $api,
    ) {}

    /**
     * Получить список отзывов
     */
    public function list(int $page = 0, int $size = 20): array
    {
        return $this->api->call(
            UzumEndpoints::REVIEWS_LIST,
            query: ['page' => $page, 'size' => $size],
        );
    }

    /**
     * Получить детали отзыва
     */
    public function detail(int $reviewId): array
    {
        return $this->api->call(
            UzumEndpoints::REVIEW_DETAIL,
            params: ['reviewId' => $reviewId],
        );
    }

    /**
     * Ответить на отзыв
     */
    public function reply(int $reviewId, string $content): array
    {
        return $this->api->call(
            UzumEndpoints::REVIEW_REPLY,
            body: [['reviewId' => $reviewId, 'content' => $content]],
        );
    }
}
