<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\ProductImage;
use Illuminate\Support\Collection;

class ProductCardService
{
    public function __construct(
        private AIService $aiService
    ) {}

    public function generateFromImage(
        ProductImage $image,
        string $marketplace,
        string $language,
        int $companyId,
        int $userId
    ): ProductDescription {
        // First, analyze the image
        $analysis = $this->aiService->analyzeImage(
            $image->url,
            [
                'category' => $image->product->category,
                'brand' => $image->product->brand,
            ],
            $companyId,
            $userId
        );

        // Generate product texts based on analysis
        $texts = $this->aiService->generateProductTexts(
            [
                'images' => [$image->url],
                'category' => $image->product->category,
                'brand' => $image->product->brand,
                'analysis' => $analysis,
            ],
            $marketplace,
            $language,
            $companyId,
            $userId
        );

        // Create new description version
        return ProductDescription::createNewVersion(
            $image->product,
            $marketplace,
            $language,
            [
                'title' => $texts['title'] ?? '',
                'short_description' => $texts['short_description'] ?? null,
                'full_description' => $texts['full_description'] ?? null,
                'bullets' => $texts['bullets'] ?? null,
                'attributes' => $texts['attributes'] ?? null,
                'keywords' => $texts['keywords'] ?? null,
            ]
        );
    }

    public function generateFromImages(
        array $imageUrls,
        string $marketplace,
        string $language,
        ?string $category,
        ?string $brand,
        int $companyId,
        int $userId
    ): array {
        // Analyze first image
        $analysis = $this->aiService->analyzeImage(
            $imageUrls[0],
            [
                'category' => $category,
                'brand' => $brand,
            ],
            $companyId,
            $userId
        );

        // Generate product texts
        $texts = $this->aiService->generateProductTexts(
            [
                'images' => $imageUrls,
                'category' => $category ?? ($analysis['category'] ?? null),
                'brand' => $brand,
                'analysis' => $analysis,
            ],
            $marketplace,
            $language,
            $companyId,
            $userId
        );

        return [
            'analysis' => $analysis,
            'texts' => $texts,
        ];
    }

    public function generateBulk(
        array $products,
        string $marketplace,
        string $language,
        int $companyId,
        int $userId,
        ?callable $onProgress = null
    ): Collection {
        $results = collect();
        $total = count($products);

        foreach ($products as $index => $productData) {
            try {
                $product = Product::find($productData['id']);
                if (! $product) {
                    continue;
                }

                $primaryImage = $product->primaryImage();
                if (! $primaryImage) {
                    $results->push([
                        'product_id' => $product->id,
                        'success' => false,
                        'error' => 'No image available',
                    ]);

                    continue;
                }

                $description = $this->generateFromImage(
                    $primaryImage,
                    $marketplace,
                    $language,
                    $companyId,
                    $userId
                );

                $results->push([
                    'product_id' => $product->id,
                    'success' => true,
                    'description_id' => $description->id,
                ]);

            } catch (\Exception $e) {
                $results->push([
                    'product_id' => $productData['id'] ?? null,
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($onProgress) {
                $progress = (int) (($index + 1) / $total * 100);
                $onProgress($progress);
            }
        }

        return $results;
    }

    public function translateDescription(
        ProductDescription $description,
        string $targetLanguage,
        int $companyId,
        int $userId
    ): ProductDescription {
        $sourceData = [
            'title' => $description->title,
            'short_description' => $description->short_description,
            'full_description' => $description->full_description,
            'bullets' => $description->bullets,
        ];

        $sourceLang = $description->language === 'ru' ? 'русского' : 'узбекского';
        $targetLang = $targetLanguage === 'ru' ? 'русский' : 'узбекский';

        $prompt = "Переведи карточку товара с {$sourceLang} на {$targetLang} язык.\n\n";
        $prompt .= "Исходные данные:\n".json_encode($sourceData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt .= "\n\nВерни результат в том же JSON формате.";

        // Use AI service for translation
        $response = $this->aiService->generateChatResponse(
            [],
            $prompt,
            ['company_id' => $companyId, 'user_id' => $userId]
        );

        $translated = json_decode($response, true) ?? [];

        return ProductDescription::createNewVersion(
            $description->product,
            $description->marketplace,
            $targetLanguage,
            [
                'title' => $translated['title'] ?? $description->title,
                'short_description' => $translated['short_description'] ?? $description->short_description,
                'full_description' => $translated['full_description'] ?? $description->full_description,
                'bullets' => $translated['bullets'] ?? $description->bullets,
                'attributes' => $description->attributes, // Keep original attributes
                'keywords' => $description->keywords, // Keep original keywords
            ]
        );
    }
}
