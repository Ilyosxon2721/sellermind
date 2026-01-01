<?php

namespace App\Services\Agent\Tools;

use App\Models\VpcSession;
use App\Services\Agent\Contracts\AgentToolInterface;
use App\Services\Vpc\VpcCommandClient;

/**
 * Tool for checking marketplace product pages by URL.
 * Opens the product page in VPC browser and extracts information.
 */
class CheckProductByUrlTool implements AgentToolInterface
{
    private VpcCommandClient $vpcClient;

    // Patterns to detect marketplace from URL
    private const MARKETPLACE_PATTERNS = [
        'uzum' => ['uzum.uz', 'seller.uzum.uz'],
        'wb' => ['wildberries.ru', 'seller.wildberries.ru'],
        'ozon' => ['ozon.ru', 'seller.ozon.ru'],
        'ym' => ['market.yandex.ru', 'partner.market.yandex.ru'],
    ];

    public function __construct()
    {
        $this->vpcClient = app(VpcCommandClient::class);
    }

    public function getName(): string
    {
        return 'check_product_by_url';
    }

    public function getSchema(): array
    {
        return [
            'name' => 'check_product_by_url',
            'description' => 'Check a product page on a marketplace by URL. Opens the URL in VPC browser, takes a screenshot, and extracts product information. Use this to analyze competitor products or verify your own product listings.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'url' => [
                        'type' => 'string',
                        'description' => 'The full URL of the product page to check',
                    ],
                    'session_id' => [
                        'type' => 'integer',
                        'description' => 'VPC session ID to use. If not provided, will use active session.',
                    ],
                    'take_screenshot' => [
                        'type' => 'boolean',
                        'description' => 'Whether to take a screenshot of the page (default: true)',
                    ],
                    'extract_info' => [
                        'type' => 'boolean',
                        'description' => 'Whether to extract product information from the page (default: true)',
                    ],
                    'scroll_full_page' => [
                        'type' => 'boolean',
                        'description' => 'Whether to scroll through the full page to capture all content (default: false)',
                    ],
                ],
                'required' => ['url'],
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $url = $arguments['url'] ?? null;
        $sessionId = $arguments['session_id'] ?? null;
        $takeScreenshot = $arguments['take_screenshot'] ?? true;
        $extractInfo = $arguments['extract_info'] ?? true;
        $scrollFullPage = $arguments['scroll_full_page'] ?? false;

        if (!$url) {
            return [
                'success' => false,
                'error' => 'URL is required',
            ];
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'error' => 'Invalid URL format',
            ];
        }

        // Detect marketplace from URL
        $marketplace = $this->detectMarketplace($url);

        // Get VPC session
        $session = $this->getSession($sessionId);
        if (!$session) {
            return [
                'success' => false,
                'error' => 'No active VPC session found. Please create a VPC session first.',
            ];
        }

        // Check session state
        if ($session->control_mode !== VpcSession::MODE_AGENT_CONTROL) {
            return [
                'success' => false,
                'error' => "VPC session is in '{$session->control_mode}' mode. Agent control is not active.",
            ];
        }

        try {
            return $this->checkProduct($session, $url, $marketplace, $takeScreenshot, $extractInfo, $scrollFullPage);
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

        return VpcSession::where('control_mode', VpcSession::MODE_AGENT_CONTROL)
            ->whereIn('status', [VpcSession::STATUS_READY, VpcSession::STATUS_RUNNING])
            ->latest()
            ->first();
    }

    private function detectMarketplace(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        foreach (self::MARKETPLACE_PATTERNS as $marketplace => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($host, $pattern)) {
                    return $marketplace;
                }
            }
        }

        return null;
    }

    private function checkProduct(
        VpcSession $session,
        string $url,
        ?string $marketplace,
        bool $takeScreenshot,
        bool $extractInfo,
        bool $scrollFullPage
    ): array {
        $actions = [];

        // Step 1: Navigate to product page
        $navAction = $this->vpcClient->openUrl($session, 'agent', $url);
        $actions[] = ['type' => 'navigate', 'action_id' => $navAction->id];

        // Step 2: Wait for page load (simulated)
        // TODO: Real implementation would wait for specific elements

        // Step 3: Optionally scroll to load all content
        if ($scrollFullPage) {
            // Scroll down several times to load lazy content
            for ($i = 0; $i < 3; $i++) {
                $scrollAction = $this->vpcClient->scroll($session, 'agent', 'down', 500);
                $actions[] = ['type' => 'scroll', 'action_id' => $scrollAction->id];
            }
            // Scroll back to top
            $this->vpcClient->keyPress($session, 'agent', 'Home');
        }

        // Step 4: Take screenshot
        $screenshotUrl = null;
        if ($takeScreenshot) {
            $ssAction = $this->vpcClient->screenshot($session, 'agent');
            $actions[] = ['type' => 'screenshot', 'action_id' => $ssAction->id];
            // TODO: Get actual screenshot URL from VM response
        }

        // Step 5: Extract product info
        $productInfo = null;
        if ($extractInfo) {
            $extractAction = $this->vpcClient->sendCommand($session, 'agent', 'extract_product_info', [
                'url' => $url,
                'marketplace' => $marketplace,
                'selectors' => $this->getSelectors($marketplace),
            ]);
            $actions[] = ['type' => 'extract', 'action_id' => $extractAction->id];

            // TODO: Real implementation would return extracted data
            $productInfo = [
                'extraction_requested' => true,
                'marketplace' => $marketplace,
                'selectors_used' => $this->getSelectors($marketplace),
            ];
        }

        return [
            'success' => true,
            'message' => "Product page checked: {$url}",
            'marketplace' => $marketplace,
            'marketplace_label' => $marketplace ? $this->getMarketplaceLabel($marketplace) : 'Unknown',
            'url' => $url,
            'screenshot_url' => $screenshotUrl,
            'product_info' => $productInfo,
            'actions' => $actions,
            'next_steps' => $this->getNextSteps($marketplace),
        ];
    }

    /**
     * Get CSS selectors for extracting product info from each marketplace.
     */
    private function getSelectors(?string $marketplace): array
    {
        return match ($marketplace) {
            'uzum' => [
                'title' => 'h1.product-title',
                'price' => '.product-price',
                'description' => '.product-description',
                'images' => '.product-gallery img',
                'rating' => '.product-rating',
                'reviews_count' => '.reviews-count',
            ],
            'wb' => [
                'title' => '.product-page__title',
                'price' => '.price-block__final-price',
                'description' => '.collapsable__text',
                'images' => '.sw-slider-kt-mix__slide img',
                'rating' => '.product-review__rating',
                'reviews_count' => '.product-review__count-review',
            ],
            'ozon' => [
                'title' => '[data-widget="webProductHeading"] h1',
                'price' => '[data-widget="webPrice"]',
                'description' => '[data-widget="webDescription"]',
                'images' => '[data-widget="webGallery"] img',
                'rating' => '[data-widget="webSingleProductScore"]',
            ],
            'ym' => [
                'title' => 'h1[data-zone-name="title"]',
                'price' => '[data-zone-name="price"]',
                'description' => '[data-zone-name="description"]',
                'images' => '[data-zone-name="gallery"] img',
            ],
            default => [
                'title' => 'h1',
                'price' => '[class*="price"]',
                'description' => '[class*="description"]',
                'images' => 'img',
            ],
        };
    }

    private function getMarketplaceLabel(?string $marketplace): string
    {
        return match ($marketplace) {
            'uzum' => 'Uzum Market',
            'wb' => 'Wildberries',
            'ozon' => 'Ozon',
            'ym' => 'Yandex Market',
            default => 'Unknown Marketplace',
        };
    }

    private function getNextSteps(?string $marketplace): array
    {
        $steps = [
            'Дождитесь загрузки страницы',
            'Проанализируйте скриншот для визуальной оценки',
        ];

        if ($marketplace) {
            $steps[] = "Используйте vpc_browser с action='get_page_content' для получения текстового содержимого";
            $steps[] = 'Сравните с карточкой в базе SellerMind при необходимости';
        } else {
            $steps[] = 'Маркетплейс не определён автоматически - возможно потребуется ручной анализ';
        }

        return $steps;
    }
}
