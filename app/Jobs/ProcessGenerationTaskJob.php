<?php

namespace App\Jobs;

use App\Models\GenerationTask;
use App\Services\AIService;
use App\Services\ProductCardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGenerationTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    public function __construct(
        public GenerationTask $task
    ) {}

    public function handle(AIService $aiService, ProductCardService $cardService): void
    {
        $this->task->markAsInProgress();

        try {
            $result = match ($this->task->type) {
                'cards_bulk' => $this->processCardsBulk($cardService),
                'descriptions_update' => $this->processDescriptionsUpdate($aiService),
                'images_bulk' => $this->processImagesBulk($aiService),
                default => throw new \Exception("Unknown task type: {$this->task->type}"),
            };

            $this->task->markAsDone($result);

        } catch (\Exception $e) {
            Log::error('Generation task failed', [
                'task_id' => $this->task->id,
                'type' => $this->task->type,
                'error' => $e->getMessage(),
            ]);

            $this->task->markAsFailed($e->getMessage());
        }
    }

    private function processCardsBulk(ProductCardService $cardService): array
    {
        $inputData = $this->task->input_data;
        $products = $inputData['products'] ?? [];
        $marketplace = $inputData['marketplace'] ?? 'universal';
        $language = $inputData['language'] ?? 'ru';

        $results = $cardService->generateBulk(
            $products,
            $marketplace,
            $language,
            $this->task->company_id,
            $this->task->user_id,
            fn($progress) => $this->task->updateProgress($progress)
        );

        return [
            'processed' => $results->count(),
            'successful' => $results->where('success', true)->count(),
            'failed' => $results->where('success', false)->count(),
            'details' => $results->toArray(),
        ];
    }

    private function processDescriptionsUpdate(AIService $aiService): array
    {
        $inputData = $this->task->input_data;
        $descriptions = $inputData['description_ids'] ?? [];
        $prompt = $inputData['prompt'] ?? '';
        $total = count($descriptions);
        $processed = 0;
        $results = [];

        foreach ($descriptions as $descriptionId) {
            try {
                $description = \App\Models\ProductDescription::find($descriptionId);
                if (!$description) {
                    continue;
                }

                $updatePrompt = "Обнови описание товара согласно инструкции:\n";
                $updatePrompt .= "Инструкция: {$prompt}\n\n";
                $updatePrompt .= "Текущее описание:\n";
                $updatePrompt .= "Название: {$description->title}\n";
                $updatePrompt .= "Описание: {$description->full_description}\n\n";
                $updatePrompt .= "Верни обновлённые данные в JSON формате: {\"title\": \"...\", \"full_description\": \"...\"}";

                $response = $aiService->generateChatResponse(
                    [],
                    $updatePrompt,
                    [
                        'company_id' => $this->task->company_id,
                        'user_id' => $this->task->user_id,
                    ]
                );

                $updated = json_decode($response, true);
                if ($updated) {
                    \App\Models\ProductDescription::createNewVersion(
                        $description->product,
                        $description->marketplace,
                        $description->language,
                        [
                            'title' => $updated['title'] ?? $description->title,
                            'short_description' => $updated['short_description'] ?? $description->short_description,
                            'full_description' => $updated['full_description'] ?? $description->full_description,
                            'bullets' => $description->bullets,
                            'attributes' => $description->attributes,
                            'keywords' => $description->keywords,
                        ]
                    );

                    $results[] = ['id' => $descriptionId, 'success' => true];
                }

            } catch (\Exception $e) {
                $results[] = ['id' => $descriptionId, 'success' => false, 'error' => $e->getMessage()];
            }

            $processed++;
            $this->task->updateProgress((int) ($processed / $total * 100));
        }

        return [
            'processed' => $processed,
            'results' => $results,
        ];
    }

    private function processImagesBulk(AIService $aiService): array
    {
        $inputData = $this->task->input_data;
        $products = $inputData['products'] ?? [];
        $prompt = $inputData['prompt'] ?? '';
        $quality = $inputData['quality'] ?? 'medium';
        $total = count($products);
        $processed = 0;
        $results = [];

        foreach ($products as $productData) {
            try {
                $product = \App\Models\Product::find($productData['id']);
                if (!$product) {
                    continue;
                }

                $imagePrompt = $prompt;
                if (empty($imagePrompt)) {
                    $imagePrompt = "{$product->name_internal} на белом фоне, профессиональное фото товара для маркетплейса";
                }

                $images = $aiService->generateImages(
                    $imagePrompt,
                    $quality,
                    1,
                    $this->task->company_id,
                    $this->task->user_id
                );

                foreach ($images as $url) {
                    \App\Models\ProductImage::create([
                        'product_id' => $product->id,
                        'type' => 'generated',
                        'quality' => $quality,
                        'url' => $url,
                        'prompt' => $imagePrompt,
                        'source' => 'generated',
                        'is_primary' => false,
                    ]);
                }

                $results[] = ['product_id' => $product->id, 'success' => true, 'images' => count($images)];

            } catch (\Exception $e) {
                $results[] = ['product_id' => $productData['id'] ?? null, 'success' => false, 'error' => $e->getMessage()];
            }

            $processed++;
            $this->task->updateProgress((int) ($processed / $total * 100));
        }

        return [
            'processed' => $processed,
            'results' => $results,
        ];
    }

    public function failed(\Throwable $exception): void
    {
        $this->task->markAsFailed($exception->getMessage());
    }
}
