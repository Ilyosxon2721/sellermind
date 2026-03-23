<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenAiProvider implements AiProviderInterface
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key') ?? '';
        $this->baseUrl = rtrim(config('openai.api_url', 'https://api.openai.com/v1'), '/');
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function chatCompletion(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? config('openai.models.kpi', 'gpt-5.1');
        $temperature = $options['temperature'] ?? 0.3;
        $maxTokens = $options['max_tokens'] ?? 2000;
        $timeout = $options['timeout'] ?? 60;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_completion_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout($timeout)->post("{$this->baseUrl}/chat/completions", $payload);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            throw new \Exception("OpenAI API Error: {$error}");
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => [
                'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $data['usage']['total_tokens'] ?? 0,
            ],
            'model' => $model,
        ];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }
}
