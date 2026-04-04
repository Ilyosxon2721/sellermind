<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\Store\Store;
use App\Models\Store\StoreReview;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Модерация отзывов магазина (admin)
 */
final class StoreReviewController extends Controller
{
    use ApiResponder, HasCompanyScope, HasPaginatedResponse;

    /**
     * Список отзывов магазина (с фильтрацией и пагинацией)
     */
    public function index(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);
        $perPage = $this->getPerPage($request);

        $query = StoreReview::where('store_id', $store->id)
            ->with('storeProduct');

        if ($request->filled('approved')) {
            $query->where('is_approved', $request->boolean('approved'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->input('rating'));
        }

        if ($request->filled('product_id')) {
            $query->where('store_product_id', (int) $request->input('product_id'));
        }

        $reviews = $query->latest()->paginate($perPage);

        return $this->successResponse($reviews->items(), $this->paginationMeta($reviews));
    }

    /**
     * Показать отзыв
     */
    public function show(int $storeId, int $reviewId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $review = StoreReview::where('store_id', $store->id)
            ->with(['storeProduct', 'order'])
            ->findOrFail($reviewId);

        return $this->successResponse($review);
    }

    /**
     * Одобрить/отклонить отзыв и ответить
     */
    public function update(int $storeId, int $reviewId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $review = StoreReview::where('store_id', $store->id)->findOrFail($reviewId);

        $data = $request->validate([
            'is_approved' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'admin_reply' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($data['admin_reply']) && $data['admin_reply'] !== null) {
            $data['admin_replied_at'] = now();
        }

        $review->update($data);

        return $this->successResponse($review->fresh('storeProduct'));
    }

    /**
     * Удалить отзыв
     */
    public function destroy(int $storeId, int $reviewId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $review = StoreReview::where('store_id', $store->id)->findOrFail($reviewId);
        $review->delete();

        return $this->successResponse(['message' => 'Отзыв удалён']);
    }

    /**
     * Статистика отзывов магазина
     */
    public function stats(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $reviews = StoreReview::where('store_id', $store->id);

        $total = (clone $reviews)->count();
        $approved = (clone $reviews)->where('is_approved', true)->count();
        $pending = (clone $reviews)->where('is_approved', false)->count();
        $avgRating = (clone $reviews)->where('is_approved', true)->avg('rating');

        $byRating = (clone $reviews)->where('is_approved', true)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        return $this->successResponse([
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'average_rating' => $avgRating ? round((float) $avgRating, 1) : null,
            'by_rating' => $byRating,
        ]);
    }

    private function findStore(int $storeId): Store
    {
        $companyId = $this->getCompanyId();

        return Store::where('company_id', $companyId)->findOrFail($storeId);
    }
}
