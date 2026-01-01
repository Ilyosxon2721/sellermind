<?php

namespace App\Services\Agent;

use App\Services\Agent\Contracts\AgentToolInterface;
use App\Services\Agent\Tools\EchoTool;
use App\Services\Agent\Tools\GetProductContextTool;
use App\Services\Agent\Tools\VpcBrowserTool;
use App\Services\Agent\Tools\MarketplaceLoginTool;
use App\Services\Agent\Tools\CheckProductByUrlTool;

class ToolRegistry
{
    private array $tools = [];

    public function __construct()
    {
        // Register default tools
        $this->register(new EchoTool());
        $this->register(new GetProductContextTool());

        // Register VPC/Browser tools
        $this->register(new VpcBrowserTool());
        $this->register(new MarketplaceLoginTool());
        $this->register(new CheckProductByUrlTool());
    }

    public function register(AgentToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function get(string $name): ?AgentToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function all(): array
    {
        return $this->tools;
    }

    public function getNames(): array
    {
        return array_keys($this->tools);
    }

    /**
     * Get schemas for specified tool names.
     * If no names provided, returns schemas for all tools.
     */
    public function getSchemas(array $toolNames = []): array
    {
        $tools = empty($toolNames) ? $this->tools : array_filter(
            $this->tools,
            fn($tool) => in_array($tool->getName(), $toolNames)
        );

        return array_map(
            fn(AgentToolInterface $tool) => $tool->getSchema(),
            array_values($tools)
        );
    }

    /**
     * Execute a tool by name with given arguments.
     */
    public function execute(string $name, array $arguments): array
    {
        $tool = $this->get($name);

        if (!$tool) {
            return [
                'success' => false,
                'error' => "Tool '{$name}' not found",
            ];
        }

        try {
            return $tool->handle($arguments);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
