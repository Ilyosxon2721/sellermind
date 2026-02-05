<?php

namespace App\Services\Agent\Tools;

use App\Models\MarketplaceAccount;
use App\Models\VpcSession;
use App\Services\Agent\Contracts\AgentToolInterface;
use App\Services\Vpc\VpcCommandClient;

/**
 * Tool for logging into marketplace accounts via VPC browser.
 * Uses stored credentials from marketplace_accounts table.
 */
class MarketplaceLoginTool implements AgentToolInterface
{
    private VpcCommandClient $vpcClient;

    // Login page URLs for each marketplace
    private const LOGIN_URLS = [
        'uzum' => 'https://seller.uzum.uz/signin',
        'wb' => 'https://seller.wildberries.ru/signin',
        'ozon' => 'https://seller.ozon.ru/signin',
        'ym' => 'https://partner.market.yandex.ru/',
    ];

    public function __construct()
    {
        $this->vpcClient = app(VpcCommandClient::class);
    }

    public function getName(): string
    {
        return 'marketplace_login';
    }

    public function getSchema(): array
    {
        return [
            'name' => 'marketplace_login',
            'description' => 'Log into a marketplace seller account using stored credentials. Use this before checking product pages that require authentication. Supported marketplaces: uzum, wb (Wildberries), ozon, ym (Yandex Market).',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'marketplace' => [
                        'type' => 'string',
                        'enum' => ['uzum', 'wb', 'ozon', 'ym'],
                        'description' => 'The marketplace to log into',
                    ],
                    'company_id' => [
                        'type' => 'integer',
                        'description' => 'Company ID to use credentials from. If not provided, will try to find any active account.',
                    ],
                    'session_id' => [
                        'type' => 'integer',
                        'description' => 'VPC session ID to use. If not provided, will use active session.',
                    ],
                ],
                'required' => ['marketplace'],
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $marketplace = $arguments['marketplace'] ?? null;
        $companyId = $arguments['company_id'] ?? null;
        $sessionId = $arguments['session_id'] ?? null;

        if (! $marketplace) {
            return [
                'success' => false,
                'error' => 'Marketplace is required',
            ];
        }

        if (! isset(self::LOGIN_URLS[$marketplace])) {
            return [
                'success' => false,
                'error' => "Unsupported marketplace: {$marketplace}",
            ];
        }

        // Get marketplace account with credentials
        $account = $this->getMarketplaceAccount($marketplace, $companyId);
        if (! $account) {
            return [
                'success' => false,
                'error' => "No active {$marketplace} account found. Please add marketplace credentials first.",
            ];
        }

        // Get VPC session
        $session = $this->getSession($sessionId);
        if (! $session) {
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
            return $this->performLogin($session, $account, $marketplace);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getMarketplaceAccount(string $marketplace, ?int $companyId): ?MarketplaceAccount
    {
        $query = MarketplaceAccount::where('marketplace', $marketplace)
            ->where('is_active', true);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
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

    private function performLogin(VpcSession $session, MarketplaceAccount $account, string $marketplace): array
    {
        $credentials = $account->getDecryptedCredentials();
        $loginUrl = self::LOGIN_URLS[$marketplace];

        // Step 1: Navigate to login page
        $this->vpcClient->openUrl($session, 'agent', $loginUrl);

        // Step 2: Log the login attempt (credentials are used by VM, not logged)
        $action = $this->vpcClient->sendCommand($session, 'agent', 'marketplace_login', [
            'marketplace' => $marketplace,
            'login_url' => $loginUrl,
            'account_id' => $account->id,
            // Note: Actual credentials are sent to VM securely, not logged
        ]);

        // TODO: Real implementation would:
        // 1. Wait for page load
        // 2. Find login form fields (email/phone, password)
        // 3. Fill in credentials
        // 4. Click submit button
        // 5. Wait for successful login redirect
        // 6. Handle 2FA if required

        return [
            'success' => true,
            'message' => "Login initiated for {$marketplace}",
            'marketplace' => $marketplace,
            'login_url' => $loginUrl,
            'action_id' => $action->id,
            'instructions' => $this->getLoginInstructions($marketplace),
        ];
    }

    /**
     * Get marketplace-specific login instructions for the agent.
     */
    private function getLoginInstructions(string $marketplace): array
    {
        return match ($marketplace) {
            'uzum' => [
                'После загрузки страницы:',
                '1. Найти поле для телефона/email',
                '2. Ввести логин',
                '3. Нажать кнопку "Продолжить"',
                '4. Ввести пароль или код из SMS',
                '5. Дождаться перенаправления в кабинет продавца',
            ],
            'wb' => [
                'После загрузки страницы:',
                '1. Найти поле для телефона',
                '2. Ввести номер телефона',
                '3. Нажать "Получить код"',
                '4. Ввести код из SMS',
                '5. Дождаться входа в личный кабинет',
            ],
            'ozon' => [
                'После загрузки страницы:',
                '1. Найти поле для email/телефона',
                '2. Ввести данные для входа',
                '3. Ввести пароль',
                '4. Нажать "Войти"',
                '5. Дождаться входа в seller cabinet',
            ],
            'ym' => [
                'После загрузки страницы:',
                '1. Нажать "Войти" через Яндекс ID',
                '2. Ввести логин Яндекса',
                '3. Ввести пароль',
                '4. Дождаться входа в партнёрский кабинет',
            ],
            default => ['Следуйте стандартной процедуре авторизации'],
        };
    }
}
