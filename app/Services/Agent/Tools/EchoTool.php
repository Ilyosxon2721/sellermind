<?php

namespace App\Services\Agent\Tools;

use App\Services\Agent\Contracts\AgentToolInterface;

class EchoTool implements AgentToolInterface
{
    public function getName(): string
    {
        return 'echo';
    }

    public function getSchema(): array
    {
        return [
            'name' => 'echo',
            'description' => 'Echoes back the provided text. Useful for testing tool integration.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'text' => [
                        'type' => 'string',
                        'description' => 'The text to echo back',
                    ],
                ],
                'required' => ['text'],
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $text = $arguments['text'] ?? '';

        return [
            'success' => true,
            'result' => $text,
            'message' => "Echo: {$text}",
        ];
    }
}
