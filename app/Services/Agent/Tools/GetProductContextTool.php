<?php

namespace App\Services\Agent\Tools;

use App\Models\Product;
use App\Services\Agent\Contracts\AgentToolInterface;

class GetProductContextTool implements AgentToolInterface
{
    public function getName(): string
    {
        return 'get_product_context';
    }

    public function getSchema(): array
    {
        return [
            'name' => 'get_product_context',
            'description' => 'Retrieves detailed information about a product including its descriptions and images. Use this to get context about a specific product.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'The ID of the product to retrieve',
                    ],
                ],
                'required' => ['product_id'],
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $productId = $arguments['product_id'] ?? null;

        if (! $productId) {
            return [
                'success' => false,
                'error' => 'Product ID is required',
            ];
        }

        $product = Product::with(['descriptions', 'images', 'company'])
            ->find($productId);

        if (! $product) {
            return [
                'success' => false,
                'error' => "Product with ID {$productId} not found",
            ];
        }

        return [
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category,
                'status' => $product->status,
                'company' => $product->company?->name,
                'descriptions' => $product->descriptions->map(fn ($d) => [
                    'id' => $d->id,
                    'marketplace' => $d->marketplace,
                    'language' => $d->language,
                    'title' => $d->title,
                    'short_description' => $d->short_description,
                    'full_description' => mb_substr($d->full_description ?? '', 0, 500),
                    'bullets' => $d->bullets,
                    'keywords' => $d->keywords,
                ])->toArray(),
                'images_count' => $product->images->count(),
                'primary_image' => $product->images->where('is_primary', true)->first()?->url,
            ],
        ];
    }
}
