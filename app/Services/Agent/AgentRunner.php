<?php

namespace App\Services\Agent;

use App\Models\AgentMessage;
use App\Models\AgentTaskRun;
use Illuminate\Support\Facades\Log;

class AgentRunner
{
    private OpenAiClient $openAiClient;
    private ToolRegistry $toolRegistry;

    public function __construct(
        OpenAiClient $openAiClient,
        ToolRegistry $toolRegistry
    ) {
        $this->openAiClient = $openAiClient;
        $this->toolRegistry = $toolRegistry;
    }

    public function run(AgentTaskRun $run): void
    {
        $run->load(['task.agent']);
        $task = $run->task;
        $agent = $task->agent;

        try {
            // Mark as running
            $run->markAsRunning();

            // Build messages array
            $messages = $this->buildMessages($run);

            // Get tool schemas for enabled tools
            $tools = $this->getToolSchemas($agent->getEnabledToolNames());

            // Log system and user messages
            $this->logMessage($run, AgentMessage::ROLE_SYSTEM, $agent->system_prompt);
            $this->logMessage($run, AgentMessage::ROLE_USER, $task->description ?? $task->title);

            // Call OpenAI
            $response = $this->openAiClient->sendChat(
                $messages,
                $agent->model,
                $tools
            );

            // Check for tool calls (MVP: no loop, just log if present)
            if ($this->openAiClient->hasToolCalls($response)) {
                $toolCalls = $this->openAiClient->extractToolCalls($response);
                foreach ($toolCalls as $toolCall) {
                    $this->logMessage(
                        $run,
                        AgentMessage::ROLE_TOOL,
                        json_encode($toolCall['function']['arguments'] ?? []),
                        $toolCall['function']['name'] ?? 'unknown',
                        ['tool_call_id' => $toolCall['id'] ?? null]
                    );
                }
            }

            // Extract assistant content
            $assistantContent = $this->openAiClient->extractAssistantContent($response);

            // Log assistant response
            $this->logMessage($run, AgentMessage::ROLE_ASSISTANT, $assistantContent);

            // Generate summary
            $summary = $this->generateSummary($assistantContent);

            // Mark as success
            $run->markAsSuccess($summary);

        } catch (\Exception $e) {
            Log::error('AgentRunner Error', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->logMessage(
                $run,
                AgentMessage::ROLE_SYSTEM,
                "Error: {$e->getMessage()}",
                null,
                ['error' => true]
            );

            $run->markAsFailed($e->getMessage());
        }
    }

    /**
     * Continue an existing run with a follow-up message
     */
    public function continueRun(AgentTaskRun $run, string $userMessage): void
    {
        $run->load(['task.agent', 'messages']);
        $task = $run->task;
        $agent = $task->agent;

        try {
            // Mark as running
            $run->markAsRunning();

            // Build messages from history
            $messages = $this->buildMessagesFromHistory($run);

            // Add new user message
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage,
            ];

            // Log the new user message
            $this->logMessage($run, AgentMessage::ROLE_USER, $userMessage);

            // Get tool schemas for enabled tools
            $tools = $this->getToolSchemas($agent->getEnabledToolNames());

            // Call OpenAI
            $response = $this->openAiClient->sendChat(
                $messages,
                $agent->model,
                $tools
            );

            // Check for tool calls
            if ($this->openAiClient->hasToolCalls($response)) {
                $toolCalls = $this->openAiClient->extractToolCalls($response);
                foreach ($toolCalls as $toolCall) {
                    $this->logMessage(
                        $run,
                        AgentMessage::ROLE_TOOL,
                        json_encode($toolCall['function']['arguments'] ?? []),
                        $toolCall['function']['name'] ?? 'unknown',
                        ['tool_call_id' => $toolCall['id'] ?? null]
                    );
                }
            }

            // Extract assistant content
            $assistantContent = $this->openAiClient->extractAssistantContent($response);

            // Log assistant response
            $this->logMessage($run, AgentMessage::ROLE_ASSISTANT, $assistantContent);

            // Generate summary
            $summary = $this->generateSummary($assistantContent);

            // Mark as success
            $run->markAsSuccess($summary);

        } catch (\Exception $e) {
            Log::error('AgentRunner Continue Error', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->logMessage(
                $run,
                AgentMessage::ROLE_SYSTEM,
                "Error: {$e->getMessage()}",
                null,
                ['error' => true]
            );

            $run->markAsFailed($e->getMessage());
        }
    }

    /**
     * Build messages array from existing message history
     */
    private function buildMessagesFromHistory(AgentTaskRun $run): array
    {
        $messages = [];

        foreach ($run->messages as $message) {
            // Skip tool messages for now (they have different format)
            if ($message->role === AgentMessage::ROLE_TOOL) {
                continue;
            }

            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        return $messages;
    }

    private function buildMessages(AgentTaskRun $run): array
    {
        $task = $run->task;
        $agent = $task->agent;

        $messages = [];

        // System message
        $messages[] = [
            'role' => 'system',
            'content' => $agent->system_prompt,
        ];

        // User message (task description)
        $userContent = $task->description ?? $task->title;

        // Add input payload context if present
        if (!empty($task->input_payload)) {
            $userContent .= "\n\nДополнительный контекст:\n" . json_encode($task->input_payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userContent,
        ];

        return $messages;
    }

    private function getToolSchemas(array $toolNames): array
    {
        if (empty($toolNames)) {
            return [];
        }

        $schemas = [];
        foreach ($toolNames as $toolName) {
            $tool = $this->toolRegistry->get($toolName);
            if ($tool) {
                $schemas[] = [
                    'type' => 'function',
                    'function' => $tool->getSchema(),
                ];
            }
        }

        return $schemas;
    }

    private function logMessage(
        AgentTaskRun $run,
        string $role,
        string $content,
        ?string $toolName = null,
        ?array $metadata = null
    ): void {
        AgentMessage::create([
            'agent_task_run_id' => $run->id,
            'role' => $role,
            'content' => $content,
            'tool_name' => $toolName,
            'metadata' => $metadata,
        ]);
    }

    private function generateSummary(string $content): string
    {
        // Simple summary: first 200 characters
        $summary = strip_tags($content);
        if (mb_strlen($summary) > 200) {
            $summary = mb_substr($summary, 0, 197) . '...';
        }
        return $summary;
    }
}
