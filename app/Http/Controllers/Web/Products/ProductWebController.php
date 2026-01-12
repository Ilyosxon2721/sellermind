<?php

namespace App\Http\Controllers\Web\Products;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Products\ProductPublishService;
use App\Services\Products\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductWebController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ProductPublishService $publishService
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $companyId = $user?->company_id;

        // Если нет компании вовсе — отдаём пустой список, чтобы не падать
        if (!$companyId) {
            return view('products.index', [
                'products' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
                'categories' => collect(),
                'filters' => [
                    'search' => '',
                    'category_id' => null,
                    'is_archived' => false,
                ],
            ]);
        }
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'category_id' => $request->input('category_id'),
            'is_archived' => $request->boolean('is_archived', false),
        ];

        $query = Product::query()
            ->forCompany($companyId)
            ->with(['mainImage', 'images', 'channelSettings.channel'])
            ->withCount('variants');

        if ($filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('article', 'like', '%' . $filters['search'] . '%');
            });
        }

        if ($filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!$filters['is_archived']) {
            $query->where('is_archived', false);
        }

        $products = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $categories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $companyId = $user?->company_id;

        $categories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $attributes = Attribute::orderBy('name')->get();

        // Global options for sizes and colors
        $globalSizes = \App\Models\GlobalOption::sizes($companyId)?->activeValues ?? collect();
        $globalColors = \App\Models\GlobalOption::colors($companyId)?->activeValues ?? collect();

        return view('products.edit', [
            'product' => new Product(['company_id' => $companyId]),
            'categories' => $categories,
            'attributesList' => $attributes,
            'globalSizes' => $globalSizes,
            'globalColors' => $globalColors,
            'initialState' => $this->buildInitialState(null),
        ]);
    }

    public function edit(Request $request, Product $product): View
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

        $companyId = $request->user()->company_id;
        $categories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $attributes = Attribute::orderBy('name')->get();

        // Global options for sizes and colors
        $globalSizes = \App\Models\GlobalOption::sizes($companyId)?->activeValues ?? collect();
        $globalColors = \App\Models\GlobalOption::colors($companyId)?->activeValues ?? collect();

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'attributesList' => $attributes,
            'globalSizes' => $globalSizes,
            'globalColors' => $globalColors,
            'initialState' => $this->buildInitialState($product),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dto = $this->buildDto($request);
        $product = $this->productService->createProductFromDto($dto);

        return redirect()
            ->route('web.products.index', ['saved' => 'created'])
            ->with('success', 'Товар успешно создан!');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeCompany($request, $product);

        $dto = $this->buildDto($request, $product);
        $product = $this->productService->updateProductFromDto($product, $dto);

        return redirect()
            ->route('web.products.index', ['saved' => 'updated'])
            ->with('success', 'Товар успешно обновлён!');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeCompany($request, $product);

        $product->is_archived = true;
        $product->save();
        $product->delete();

        return redirect()
            ->route('web.products.index')
            ->with('success', 'Товар архивирован');
    }

    public function publish(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeCompany($request, $product);
        $channels = $request->input('channels', []);

        $this->publishService->publish($product, $channels);

        return back()->with('success', 'Публикация запущена');
    }

    protected function buildDto(Request $request, ?Product $product = null): array
    {
        $user = $request->user();
        $request->validate([
            'product.name' => ['required', 'string', 'max:255'],
            'product.article' => ['required', 'string', 'max:100'],
            'product.category_id' => ['nullable', 'integer'],
        ]);

        $productData = Arr::only($request->input('product', []), [
            'name',
            'article',
            'brand_name',
            'category_id',
            'country_of_origin',
            'manufacturer',
            'unit',
            'care_instructions',
            'composition',
            'package_weight_g',
            'package_length_mm',
            'package_width_mm',
            'package_height_mm',
            'is_active',
            'is_archived',
        ]);

        // Convert checkbox "on" values to proper booleans (1/0)
        $productData['is_active'] = !empty($productData['is_active']) ? 1 : 0;
        $productData['is_archived'] = !empty($productData['is_archived']) ? 1 : 0;

        $productData['company_id'] = $user?->company_id;

        $attributesProduct = $this->decodeJsonArray($request->input('attributes_product'));
        $attributesVariants = $this->decodeJsonArray($request->input('attributes_variants'));
        $attributes = [];

        foreach ($attributesProduct as $item) {
            $item['product_variant_id'] = null;
            $attributes[] = $item;
        }

        foreach ($attributesVariants as $item) {
            $attributes[] = $item;
        }

        return [
            'product' => $productData,
            'options' => $this->decodeJsonArray($request->input('options')),
            'variants' => $this->decodeJsonArray($request->input('variants')),
            'images' => $this->decodeJsonArray($request->input('images')),
            'attributes' => $attributes,
            'channel_settings' => $this->decodeJsonArray($request->input('channel_settings')),
            'channel_variants' => $this->decodeJsonArray($request->input('channel_variants')),
        ];
    }

    protected function decodeJsonArray(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function buildInitialState(?Product $product): array
    {
        if (!$product) {
            return [
                'product' => [
                    'name' => '',
                    'article' => '',
                    'brand_name' => '',
                    'category_id' => null,
                    'country_of_origin' => '',
                    'manufacturer' => '',
                    'unit' => '',
                    'care_instructions' => '',
                    'composition' => '',
                    'package_weight_g' => null,
                    'package_length_mm' => null,
                    'package_width_mm' => null,
                    'package_height_mm' => null,
                    'is_active' => true,
                    'is_archived' => false,
                ],
                'options' => [],
                'variants' => [],
                'images' => [],
                'attributes' => [
                    'product' => [],
                    'variants' => [],
                ],
                'channel_settings' => [],
                'channel_variants' => [],
            ];
        }

        $options = $product->options->map(function ($option) {
            return [
                'id' => $option->id,
                'code' => $option->code,
                'name' => $option->name,
                'type' => $option->type,
                'is_variant_dimension' => $option->is_variant_dimension,
                'values' => $option->values->map(function ($value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'code' => $value->code,
                        'color_hex' => $value->color_hex,
                        'sort_order' => $value->sort_order,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $variants = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'article_suffix' => $variant->article_suffix,
                'option_values_summary' => $variant->option_values_summary,
                'purchase_price' => $variant->purchase_price,
                'price_default' => $variant->price_default,
                'old_price_default' => $variant->old_price_default,
                'stock_default' => $variant->stock_default,
                'weight_g' => $variant->weight_g,
                'length_mm' => $variant->length_mm,
                'width_mm' => $variant->width_mm,
                'height_mm' => $variant->height_mm,
                'main_image_id' => $variant->main_image_id,
                'is_active' => $variant->is_active,
                'is_deleted' => $variant->is_deleted,
                'option_value_ids' => $variant->optionValues->pluck('id')->all(),
            ];
        })->values()->all();

        $images = $product->images->map(function ($image) {
            return [
                'id' => $image->id,
                'variant_id' => $image->variant_id,
                'file_path' => $image->file_path,
                'alt_text' => $image->alt_text,
                'is_main' => $image->is_main,
                'sort_order' => $image->sort_order,
            ];
        })->values()->all();

        $attributesProduct = $product->attributeValues
            ->whereNull('product_variant_id')
            ->values()
            ->map(function ($value) {
                return [
                    'id' => $value->id,
                    'attribute_id' => $value->attribute_id,
                    'value_string' => $value->value_string,
                    'value_number' => $value->value_number,
                    'value_json' => $value->value_json,
                ];
            })->all();

        $attributesVariants = $product->variants->flatMap(function ($variant) {
            return $variant->attributeValues->map(function ($value) use ($variant) {
                return [
                    'id' => $value->id,
                    'attribute_id' => $value->attribute_id,
                    'product_variant_id' => $variant->id,
                    'value_string' => $value->value_string,
                    'value_number' => $value->value_number,
                    'value_json' => $value->value_json,
                ];
            });
        })->values()->all();

        $channelSettings = $product->channelSettings->map(function ($setting) {
            return [
                'id' => $setting->id,
                'channel_id' => $setting->channel_id,
                'channel_code' => $setting->channel?->code,
                'external_product_id' => $setting->external_product_id,
                'category_external_id' => $setting->category_external_id,
                'name_override' => $setting->name_override,
                'description_override' => $setting->description_override,
                'brand_external_id' => $setting->brand_external_id,
                'brand_external_name' => $setting->brand_external_name,
                'is_enabled' => $setting->is_enabled,
                'status' => $setting->status,
                'last_synced_at' => $setting->last_synced_at,
                'last_sync_status_message' => $setting->last_sync_status_message,
                'extra' => $setting->extra,
            ];
        })->values()->all();

        $channelVariants = $product->variants->flatMap(function ($variant) {
            return $variant->channelVariantSettings->map(function ($setting) use ($variant) {
                return [
                    'id' => $setting->id,
                    'channel_id' => $setting->channel_id,
                    'channel_code' => $setting->channel?->code,
                    'product_variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'external_offer_id' => $setting->external_offer_id,
                    'price' => $setting->price,
                    'old_price' => $setting->old_price,
                    'stock' => $setting->stock,
                    'status' => $setting->status,
                    'last_synced_at' => $setting->last_synced_at,
                    'extra' => $setting->extra,
                ];
            });
        })->values()->all();

        return [
            'product' => Arr::only($product->toArray(), [
                'name',
                'article',
                'brand_name',
                'category_id',
                'country_of_origin',
                'manufacturer',
                'unit',
                'care_instructions',
                'composition',
                'package_weight_g',
                'package_length_mm',
                'package_width_mm',
                'package_height_mm',
                'is_active',
                'is_archived',
            ]),
            'options' => $options,
            'variants' => $variants,
            'images' => $images,
            'attributes' => [
                'product' => $attributesProduct,
                'variants' => $attributesVariants,
            ],
            'channel_settings' => $channelSettings,
            'channel_variants' => $channelVariants,
        ];
    }

    protected function authorizeCompany(Request $request, Product $product): void
    {
        $user = $request->user();
        if (!$user || $product->company_id !== $user->company_id) {
            abort(403, 'Forbidden');
        }
    }
}
