<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AnthropicProvider implements AiProviderInterface
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('anthropic.api_key') ?? '';
        $this->baseUrl = rtrim(config('anthropic.api_url', 'https://api.anthropic.com/v1'), '/');
    }

    public function getName(): string
    {
        return 'anthropic';
    }

    public function chatCompletion(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? config('anthropic.models.kpi', 'claude-sonnet-4-20250514');
        $temperature = $options['temperature'] ?? 0.3;
        $maxTokens = $options['max_tokens'] ?? 4096;
        $timeout = $options['timeout'] ?? 60;

        // Преобразуем формат OpenAI в формат Anthropic
        $systemMessage = '';
        $anthropicMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessage .= $msg['content']."\n";
            } else {
                $anthropicMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $anthropicMessages,
        ];

        if ($systemMessage) {
            $payload['system'] = trim($systemMessage);
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout($timeout)->post("{$this->baseUrl}/messages", $payload);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::error('Anthropic API Error', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            throw new \Exception("Anthropic API Error: {$error}");
        }

        $data = $response->json();

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'usage' => [
                'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            ],
            'model' => $model,
        ];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }
}
