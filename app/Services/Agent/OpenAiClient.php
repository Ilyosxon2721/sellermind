<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClient
{
    private string $apiKey;
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
        $this->apiUrl = config('openai.api_url', 'https://api.openai.com/v1');
        $this->timeout = config('openai.agent.timeout', 120);
    }

    public function sendChat(
        array $messages,
        string $model = null,
        array $tools = [],
        float $temperature = null,
        int $maxTokens = null
    ): array {
        $model = $model ?? config('openai.models.agent_default', 'gpt-4o-mini');
        $temperature = $temperature ?? config('openai.agent.temperature', 0.7);
        $maxTokens = $maxTokens ?? config('openai.agent.max_tokens', 4096);

        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];

        if ($this->supportsTemperature($model)) {
            $payload['temperature'] = $temperature;
        }

        $this->applyTokenLimit($payload, $maxTokens);

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post("{$this->apiUrl}/chat/completions", $payload);

            if ($response->failed()) {
                $error = $response->json('error.message') ?? $response->body();
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                throw new \Exception("OpenAI API Error: {$error}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('OpenAI Client Exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function extractAssistantContent(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }

    public function extractToolCalls(array $response): array
    {
        return $response['choices'][0]['message']['tool_calls'] ?? [];
    }

    public function hasToolCalls(array $response): bool
    {
        return !empty($this->extractToolCalls($response));
    }

    public function getUsage(array $response): array
    {
        return $response['usage'] ?? [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];
    }

    private function applyTokenLimit(array &$payload, int $maxTokens): void
    {
        $payload['max_completion_tokens'] = $maxTokens;
    }

    private function supportsTemperature(string $model): bool
    {
        return !str_starts_with($model, 'o1-');
    }
}
