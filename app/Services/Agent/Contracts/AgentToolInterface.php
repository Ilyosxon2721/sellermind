<?php

namespace App\Services\Agent\Contracts;

interface AgentToolInterface
{
    /**
     * Get the unique name of the tool.
     */
    public function getName(): string;

    /**
     * Get the JSON schema for OpenAI tools parameter.
     * Should return an array with 'name', 'description', and 'parameters'.
     */
    public function getSchema(): array;

    /**
     * Execute the tool with given arguments.
     *
     * @param  array  $arguments  The arguments passed to the tool
     * @return array The result of the tool execution
     */
    public function handle(array $arguments): array;
}
