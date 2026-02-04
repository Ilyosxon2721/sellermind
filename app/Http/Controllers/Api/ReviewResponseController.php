<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Review;
use App\Models\ReviewTemplate;
use App\Services\ReviewResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewResponseController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        protected ReviewResponseService $reviewService
    ) {}

    /**
     * Получить все отзывы.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sentiment' => ['nullable', 'string', 'in:positive,negative,neutral'],
            'has_response' => ['nullable', 'boolean'],
        ]);

        $companyId = $this->getCompanyId();

        $query = Review::where('company_id', $companyId)
            ->with(['product:id,name', 'marketplaceAccount:id,name,marketplace']);

        // Фильтры
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['rating'])) {
            $query->where('rating', $validated['rating']);
        }

        if (isset($validated['sentiment'])) {
            $query->where('sentiment', $validated['sentiment']);
        }

        if (array_key_exists('has_response', $validated) && $validated['has_response'] !== null) {
            if ($validated['has_response']) {
                $query->whereNotNull('response_text');
            } else {
                $query->whereNull('response_text');
            }
        }

        $reviews = $query->orderByDesc('review_date')->paginate(20);

        return response()->json($reviews);
    }

    /**
     * Get single review.
     */
    public function show(Review $review): JsonResponse
    {
        if ($review->company_id !== $this->getCompanyId()) {
            abort(403, 'Unauthorized access to company');
        }

        $review->load(['product', 'marketplaceAccount', 'responder:id,name', 'template']);

        return response()->json($review);
    }

    /**
     * Create review manually.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'marketplace_account_id' => 'nullable|exists:marketplace_accounts,id',
            'marketplace' => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string',
            'review_date' => 'nullable|date',
        ]);

        $review = Review::create([
            ...$validated,
            'company_id' => $companyId,
            'review_date' => $validated['review_date'] ?? now(),
            'sentiment' => null,
        ]);

        // Analyze sentiment
        $sentiment = $this->reviewService->analyzeSentiment($review);
        $review->update(['sentiment' => $sentiment]);

        return response()->json($review, 201);
    }

    /**
     * Generate AI response for review.
     */
    public function generateResponse(Request $request, Review $review): JsonResponse
    {
        if ($review->company_id !== $this->getCompanyId()) {
            abort(403, 'Unauthorized access to company');
        }

        $validated = $request->validate([
            'tone' => 'sometimes|in:professional,friendly,formal',
            'length' => 'sometimes|in:short,medium,long',
            'language' => 'sometimes|in:ru,en',
        ]);

        $response = $this->reviewService->generateResponse($review, $validated);

        return response()->json([
            'response' => $response,
            'is_ai_generated' => true,
        ]);
    }

    /**
     * Save response to review.
     */
    public function saveResponse(Request $request, Review $review): JsonResponse
    {
        if ($review->company_id !== $this->getCompanyId()) {
            abort(403, 'Unauthorized access to company');
        }

        $validated = $request->validate([
            'response_text' => 'required|string',
            'is_ai_generated' => 'sometimes|boolean',
            'template_id' => 'nullable|exists:review_templates,id',
        ]);

        $review->update([
            'response_text' => $validated['response_text'],
            'response_date' => now(),
            'responded_by' => Auth::id(),
            'is_ai_generated' => $validated['is_ai_generated'] ?? false,
            'template_id' => $validated['template_id'] ?? null,
            'status' => 'responded',
        ]);

        // Increment template usage if used
        if ($validated['template_id'] ?? null) {
            $template = ReviewTemplate::find($validated['template_id']);
            $template?->incrementUsage();
        }

        return response()->json($review);
    }

    /**
     * Get suggested templates for review.
     */
    public function suggestTemplates(Review $review): JsonResponse
    {
        if ($review->company_id !== $this->getCompanyId()) {
            abort(403, 'Unauthorized access to company');
        }

        $templates = $this->reviewService->suggestTemplates($review, 5);

        return response()->json($templates);
    }

    /**
     * Bulk generate responses.
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
            'tone' => 'sometimes|in:professional,friendly,formal',
            'length' => 'sometimes|in:short,medium,long',
        ]);

        // Verify all reviews belong to company
        $reviews = Review::whereIn('id', $validated['review_ids'])
            ->where('company_id', $companyId)
            ->pluck('id')
            ->toArray();

        $results = $this->reviewService->bulkGenerate($reviews, [
            'tone' => $validated['tone'] ?? 'professional',
            'length' => $validated['length'] ?? 'medium',
        ]);

        return response()->json([
            'results' => $results,
            'total' => count($results),
            'successful' => count(array_filter($results, fn ($r) => $r['success'])),
        ]);
    }

    /**
     * Get review statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $stats = [
            'total' => Review::where('company_id', $companyId)->count(),
            'pending' => Review::where('company_id', $companyId)->pending()->count(),
            'responded' => Review::where('company_id', $companyId)->responded()->count(),
            'avg_rating' => round(Review::where('company_id', $companyId)->avg('rating'), 2),
            'by_rating' => [
                5 => Review::where('company_id', $companyId)->where('rating', 5)->count(),
                4 => Review::where('company_id', $companyId)->where('rating', 4)->count(),
                3 => Review::where('company_id', $companyId)->where('rating', 3)->count(),
                2 => Review::where('company_id', $companyId)->where('rating', 2)->count(),
                1 => Review::where('company_id', $companyId)->where('rating', 1)->count(),
            ],
            'by_sentiment' => [
                'positive' => Review::where('company_id', $companyId)->where('sentiment', 'positive')->count(),
                'neutral' => Review::where('company_id', $companyId)->where('sentiment', 'neutral')->count(),
                'negative' => Review::where('company_id', $companyId)->where('sentiment', 'negative')->count(),
            ],
            'response_rate' => $this->calculateResponseRate($companyId),
        ];

        return response()->json($stats);
    }

    /**
     * Get review templates.
     */
    public function templates(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $templates = ReviewTemplate::active()
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->orderBy('category')
            ->orderByDesc('usage_count')
            ->get();

        return response()->json($templates);
    }

    /**
     * Create review template.
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_text' => 'required|string',
            'category' => 'required|in:positive,negative_quality,negative_delivery,negative_size,neutral,question,complaint',
            'rating_range' => 'nullable|array',
            'keywords' => 'nullable|array',
        ]);

        $template = ReviewTemplate::create([
            ...$validated,
            'company_id' => $companyId,
            'is_system' => false,
            'is_active' => true,
        ]);

        return response()->json($template, 201);
    }

    /**
     * Calculate response rate.
     */
    protected function calculateResponseRate(int $companyId): float
    {
        $total = Review::where('company_id', $companyId)->count();
        if ($total === 0) {
            return 0;
        }

        $responded = Review::where('company_id', $companyId)->whereNotNull('response_text')->count();

        return round(($responded / $total) * 100, 2);
    }
}
