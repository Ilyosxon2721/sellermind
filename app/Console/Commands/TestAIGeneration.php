<?php

namespace App\Console\Commands;

use App\Services\AIService;
use Illuminate\Console\Command;

class TestAIGeneration extends Command
{
    protected $signature = 'ai:test {--type=chat : Type of test (chat, card, review)}';

    protected $description = 'Test AI generation capabilities';

    public function handle(AIService $ai): int
    {
        $type = $this->option('type');

        $this->info("Testing AI {$type} generation...\n");

        try {
            switch ($type) {
                case 'chat':
                    $response = $ai->generateChatResponse(
                        context: [],
                        prompt: 'Привет! Расскажи кратко, что ты умеешь?',
                        meta: []
                    );
                    $this->line("Response:\n{$response}");
                    break;

                case 'card':
                    $response = $ai->generateProductTexts(
                        productContext: [
                            'name' => 'Халат мужской махровый',
                            'category' => 'Домашняя одежда',
                            'brand' => 'HomeStyle',
                        ],
                        marketplace: 'uzum',
                        language: 'ru',
                        style: 'professional'
                    );
                    $this->line('Generated card:');
                    $this->line('Title: '.($response['title'] ?? 'N/A'));
                    $this->line('Short: '.($response['short_description'] ?? 'N/A'));
                    $this->newLine();
                    $this->line('Bullets:');
                    foreach ($response['bullets'] ?? [] as $bullet) {
                        $this->line("  - {$bullet}");
                    }
                    break;

                case 'review':
                    $responses = $ai->generateReviewResponses(
                        review: 'Товар пришел быстро, качество отличное! Рекомендую!',
                        rating: 5,
                        style: 'friendly',
                        count: 3
                    );
                    $this->line('Generated responses:');
                    foreach ($responses as $i => $response) {
                        $this->newLine();
                        $this->line('Option '.($i + 1).':');
                        $this->line($response);
                    }
                    break;

                default:
                    $this->error("Unknown test type: {$type}");

                    return Command::FAILURE;
            }

            $this->newLine();
            $this->info('Test completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
