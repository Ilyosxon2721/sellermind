<?php

namespace App\Services\Agent\Tools;

use App\Models\VpcSession;
use App\Services\Agent\Contracts\AgentToolInterface;
use App\Services\Vpc\VpcCommandClient;

/**
 * Tool for controlling browser in VPC session.
 * Allows agent to navigate, click, type, scroll, and take screenshots.
 */
class VpcBrowserTool implements AgentToolInterface
{
    private VpcCommandClient $vpcClient;

    public function __construct()
    {
        $this->vpcClient = app(VpcCommandClient::class);
    }

    public function getName(): string
    {
        return 'vpc_browser';
    }

    public function getSchema(): array
    {
        return [
            'name' => 'vpc_browser',
            'description' => 'Control a browser in VPC (Virtual PC) session. Use this to navigate websites, click elements, type text, scroll pages, and take screenshots. Useful for checking marketplace product pages that require authentication.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'session_id' => [
                        'type' => 'integer',
                        'description' => 'The VPC session ID to use. If not provided, will use active session or create new one.',
                    ],
                    'action' => [
                        'type' => 'string',
                        'enum' => ['open_url', 'click', 'type', 'scroll', 'screenshot', 'key_press', 'get_page_content'],
                        'description' => 'The browser action to perform',
                    ],
                    'url' => [
                        'type' => 'string',
                        'description' => 'URL to navigate to (for open_url action)',
                    ],
                    'x' => [
                        'type' => 'integer',
                        'description' => 'X coordinate for click action',
                    ],
                    'y' => [
                        'type' => 'integer',
                        'description' => 'Y coordinate for click action',
                    ],
                    'text' => [
                        'type' => 'string',
                        'description' => 'Text to type (for type action)',
                    ],
                    'selector' => [
                        'type' => 'string',
                        'description' => 'CSS selector to interact with (alternative to x,y coordinates)',
                    ],
                    'direction' => [
                        'type' => 'string',
                        'enum' => ['up', 'down'],
                        'description' => 'Scroll direction (for scroll action)',
                    ],
                    'amount' => [
                        'type' => 'integer',
                        'description' => 'Scroll amount in pixels (for scroll action)',
                    ],
                    'key' => [
                        'type' => 'string',
                        'description' => 'Key to press (for key_press action), e.g., "Enter", "Tab", "Escape"',
                    ],
                ],
                'required' => ['action'],
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $action = $arguments['action'] ?? null;
        $sessionId = $arguments['session_id'] ?? null;

        if (!$action) {
            return [
                'success' => false,
                'error' => 'Action is required',
            ];
        }

        // Get or create VPC session
        $session = $this->getSession($sessionId);
        if (!$session) {
            return [
                'success' => false,
                'error' => 'No active VPC session found. Please create a VPC session first.',
            ];
        }

        // Check if session is in correct state
        if ($session->control_mode !== VpcSession::MODE_AGENT_CONTROL) {
            return [
                'success' => false,
                'error' => "VPC session is in '{$session->control_mode}' mode. Agent control is not active.",
            ];
        }

        if (!in_array($session->status, [VpcSession::STATUS_READY, VpcSession::STATUS_RUNNING])) {
            return [
                'success' => false,
                'error' => "VPC session status is '{$session->status}'. Session must be ready or running.",
            ];
        }

        try {
            return match ($action) {
                'open_url' => $this->openUrl($session, $arguments),
                'click' => $this->click($session, $arguments),
                'type' => $this->typeText($session, $arguments),
                'scroll' => $this->scroll($session, $arguments),
                'screenshot' => $this->screenshot($session),
                'key_press' => $this->keyPress($session, $arguments),
                'get_page_content' => $this->getPageContent($session),
                default => ['success' => false, 'error' => "Unknown action: {$action}"],
            };
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getSession(?int $sessionId): ?VpcSession
    {
        if ($sessionId) {
            return VpcSession::find($sessionId);
        }

        // Try to find an active session with agent control
        return VpcSession::where('control_mode', VpcSession::MODE_AGENT_CONTROL)
            ->whereIn('status', [VpcSession::STATUS_READY, VpcSession::STATUS_RUNNING])
            ->latest()
            ->first();
    }

    private function openUrl(VpcSession $session, array $arguments): array
    {
        $url = $arguments['url'] ?? null;
        if (!$url) {
            return ['success' => false, 'error' => 'URL is required for open_url action'];
        }

        $action = $this->vpcClient->openUrl($session, 'agent', $url);

        return [
            'success' => true,
            'message' => "Navigated to: {$url}",
            'action_id' => $action->id,
        ];
    }

    private function click(VpcSession $session, array $arguments): array
    {
        $x = $arguments['x'] ?? null;
        $y = $arguments['y'] ?? null;
        $selector = $arguments['selector'] ?? null;

        if ($selector) {
            // Click by selector
            $action = $this->vpcClient->sendCommand($session, 'agent', 'click', [
                'selector' => $selector,
            ]);
            return [
                'success' => true,
                'message' => "Clicked on element: {$selector}",
                'action_id' => $action->id,
            ];
        }

        if ($x === null || $y === null) {
            return ['success' => false, 'error' => 'Either x,y coordinates or selector is required for click action'];
        }

        $action = $this->vpcClient->click($session, 'agent', (int)$x, (int)$y);

        return [
            'success' => true,
            'message' => "Clicked at coordinates ({$x}, {$y})",
            'action_id' => $action->id,
        ];
    }

    private function typeText(VpcSession $session, array $arguments): array
    {
        $text = $arguments['text'] ?? null;
        if (!$text) {
            return ['success' => false, 'error' => 'Text is required for type action'];
        }

        $action = $this->vpcClient->type($session, 'agent', $text);

        return [
            'success' => true,
            'message' => "Typed text: " . mb_substr($text, 0, 50) . (mb_strlen($text) > 50 ? '...' : ''),
            'action_id' => $action->id,
        ];
    }

    private function scroll(VpcSession $session, array $arguments): array
    {
        $direction = $arguments['direction'] ?? 'down';
        $amount = $arguments['amount'] ?? 300;

        $action = $this->vpcClient->scroll($session, 'agent', $direction, (int)$amount);

        return [
            'success' => true,
            'message' => "Scrolled {$direction} by {$amount}px",
            'action_id' => $action->id,
        ];
    }

    private function screenshot(VpcSession $session): array
    {
        $action = $this->vpcClient->screenshot($session, 'agent');

        // TODO: Return actual screenshot URL when VM integration is ready
        return [
            'success' => true,
            'message' => 'Screenshot taken',
            'action_id' => $action->id,
            'screenshot_url' => null, // Will be populated when real VM returns screenshot
        ];
    }

    private function keyPress(VpcSession $session, array $arguments): array
    {
        $key = $arguments['key'] ?? null;
        if (!$key) {
            return ['success' => false, 'error' => 'Key is required for key_press action'];
        }

        $action = $this->vpcClient->keyPress($session, 'agent', $key);

        return [
            'success' => true,
            'message' => "Pressed key: {$key}",
            'action_id' => $action->id,
        ];
    }

    private function getPageContent(VpcSession $session): array
    {
        // TODO: Implement actual page content extraction via VM
        $action = $this->vpcClient->sendCommand($session, 'agent', 'get_page_content', []);

        return [
            'success' => true,
            'message' => 'Page content extraction requested',
            'action_id' => $action->id,
            'content' => null, // Will be populated when real VM returns content
        ];
    }
}
