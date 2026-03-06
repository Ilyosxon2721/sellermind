<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Services\Products\ProductPublishService;
use App\Services\Products\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use HasPaginatedResponse;

    public function __construct(
        protected ProductService $productService,
        protected ProductPublishService $publishService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = $this->getPerPage($request);

        $query = Product::query()
            ->forCompany($companyId)
            ->withCount('variants as count_variants')
            ->with([
                'channelSettings.channel:id,code,name',
                'variants:id,product_id,purchase_price,price_default',
                'variants.channelVariantSettings:id,product_variant_id,channel_id,price,status',
                'variants.channelVariantSettings.channel:id,code',
            ]);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $this->escapeLike($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_archived')) {
            $query->where('is_archived', (bool) $request->boolean('is_archived'));
        }

        $channelCode = $request->string('channel')->trim()->toString();
        $channelStatus = $request->string('channel_status')->trim()->toString();
        if ($channelCode || $channelStatus) {
            $query->whereHas('channelSettings', function ($q) use ($channelCode, $channelStatus) {
                if ($channelStatus) {
                    $q->where('status', $channelStatus);
                }
                if ($channelCode) {
                    $q->whereHas('channel', fn ($c) => $c->where('code', $channelCode));
                }
            });
        }

        $products = $query->orderByDesc('id')->paginate($perPage);

        $items = $products->getCollection()->map(function (Product $product) {
            $channels = [];
            foreach ($product->channelSettings as $setting) {
                $code = $setting->channel?->code ?? (string) $setting->channel_id;
                $channels[$code] = [
                    'status' => $setting->status,
                ];
            }

            $firstVariant = $product->variants->first();

            // Собираем цены по маркетплейсам из channelVariantSettings первого варианта
            $marketplacePrices = [];
            if ($firstVariant) {
                foreach ($firstVariant->channelVariantSettings as $cvs) {
                    $code = $cvs->channel?->code;
                    if ($code) {
                        $marketplacePrices[$code] = [
                            'price' => $cvs->price,
                            'status' => $cvs->status,
                        ];
                    }
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'article' => $product->article,
                'category_id' => $product->category_id,
                'count_variants' => $product->count_variants,
                'purchase_price' => $firstVariant?->purchase_price,
                'price_default' => $firstVariant?->price_default,
                'channels' => $channels,
                'marketplace_prices' => $marketplacePrices,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => $this->paginationMeta($products),
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorizeCompany($request, $product);

        $product->load([
            'options.values',
            'variants.optionValues',
            'variants.optionValueLinks',
            'variants.channelVariantSettings.channel',
            'variants.attributeValues.attribute',
            'variants.mainImage',
            'images',
            'attributeValues.attribute',
            'channelSettings.channel',
        ]);

        return response()->json([
            'product' => $product,
            'options' => $product->options,
            'variants' => $product->variants,
            'images' => $product->images,
            'attributes' => $product->attributeValues,
            'channel_settings' => $product->channelSettings,
            'channel_variant_settings' => $product->variants
                ->flatMap(fn ($variant) => $variant->channelVariantSettings)
                ->values(),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['product']['company_id'] = $request->user()->company_id;

        $product = $this->productService->createProductFromDto($data);

        return response()->json([
            'product' => $product,
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorizeCompany($request, $product);

        $data = $request->validated();
        $data['product']['company_id'] = $product->company_id;

        $product = $this->productService->updateProductFromDto($product, $data);

        return response()->json([
            'product' => $product,
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeCompany($request, $product);

        $product->is_archived = true;
        $product->save();
        $product->delete();

        return response()->json([
            'message' => 'Product archived.',
        ]);
    }

    public function publish(Request $request, Product $product): JsonResponse
    {
        $this->authorizeCompany($request, $product);
        $channels = $request->input('channels', []);

        $result = $this->publishService->publish($product, $channels);

        return response()->json([
            'product_id' => $product->id,
            'channels' => $result,
        ], 202);
    }

    public function publishChannel(Request $request, Product $product, string $channel): JsonResponse
    {
        $this->authorizeCompany($request, $product);

        $result = $this->publishService->publish($product, [$channel]);

        return response()->json([
            'product_id' => $product->id,
            'channels' => $result,
        ], 202);
    }

    /**
     * История изменений цен для товара
     */
    public function priceHistory(Request $request, Product $product): JsonResponse
    {
        $this->authorizeCompany($request, $product);

        $variantId = $request->query('variant_id');
        $channel   = $request->query('channel', 'default');
        $days      = (int) $request->query('days', 90);

        $query = PriceHistory::where('product_id', $product->id)
            ->where('channel', $channel)
            ->where('changed_at', '>=', now()->subDays($days))
            ->orderBy('changed_at');

        if ($variantId) {
            $query->where('product_variant_id', (int) $variantId);
        }

        $history = $query->get(['product_variant_id', 'price', 'old_price', 'changed_at', 'changed_by']);

        return response()->json([
            'data'   => $history,
            'period' => $days,
        ]);
    }

    protected function authorizeCompany(Request $request, Product $product): void
    {
        if ($product->company_id !== $request->user()->company_id) {
            abort(403, 'Forbidden');
        }
    }
}
