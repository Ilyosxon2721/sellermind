<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Traits\StorefrontHelpers;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreReview;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Публичные отзывы на товары витрины — просмотр и отправка
 */
final class ReviewController extends Controller
{
    use ApiResponder, StorefrontHelpers;

    /**
     * Получить одобренные отзывы на товар
     *
     * GET /store/{slug}/api/products/{productId}/reviews
     */
    public function index(string $slug, int $productId, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $storeProduct = StoreProduct::where('store_id', $store->id)
            ->where('id', $productId)
            ->where('is_visible', true)
            ->firstOrFail();

        $perPage = min((int) ($request->input('per_page', 10)), 50);

        $reviews = StoreReview::where('store_product_id', $storeProduct->id)
            ->where('is_approved', true)
            ->latest()
            ->paginate($perPage);

        // Общая статистика
        $stats = StoreReview::where('store_product_id', $storeProduct->id)
            ->where('is_approved', true)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
            ->first();

        $ratingDistribution = StoreReview::where('store_product_id', $storeProduct->id)
            ->where('is_approved', true)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        return $this->successResponse([
            'reviews' => $reviews->items(),
            'stats' => [
                'total' => (int) $stats->total,
                'average_rating' => $stats->avg_rating ? round((float) $stats->avg_rating, 1) : null,
                'distribution' => $ratingDistribution,
            ],
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Отправить отзыв на товар
     *
     * POST /store/{slug}/api/products/{productId}/reviews
     *
     * Отзыв отправляется на модерацию (is_approved=false по умолчанию)
     */
    public function store(string $slug, int $productId, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $storeProduct = StoreProduct::where('store_id', $store->id)
            ->where('id', $productId)
            ->where('is_visible', true)
            ->firstOrFail();

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'author_phone' => ['nullable', 'string', 'max:50'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'text' => ['nullable', 'string', 'max:2000'],
            'pros' => ['nullable', 'string', 'max:1000'],
            'cons' => ['nullable', 'string', 'max:1000'],
            'order_number' => ['nullable', 'string', 'max:50'],
        ]);

        // Попытка привязать к заказу по номеру
        $orderId = null;
        if (! empty($data['order_number'])) {
            $order = \App\Models\Store\StoreOrder::where('store_id', $store->id)
                ->where('order_number', $data['order_number'])
                ->first();
            $orderId = $order?->id;
        }

        $review = StoreReview::create([
            'store_id' => $store->id,
            'store_product_id' => $storeProduct->id,
            'store_order_id' => $orderId,
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'author_phone' => $data['author_phone'] ?? null,
            'rating' => $data['rating'],
            'text' => $data['text'] ?? null,
            'pros' => $data['pros'] ?? null,
            'cons' => $data['cons'] ?? null,
            'is_approved' => false,
        ]);

        return $this->successResponse(
            $review,
            ['message' => 'Спасибо за отзыв! Он будет опубликован после модерации.']
        )->setStatusCode(201);
    }
}
