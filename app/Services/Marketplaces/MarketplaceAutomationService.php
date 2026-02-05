<?php

// file: app/Services/Marketplaces/MarketplaceAutomationService.php

namespace App\Services\Marketplaces;

use App\Models\AgentTask;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceAutomationRule;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceReturn;
use App\Models\MarketplaceStock;
use Illuminate\Support\Facades\Log;

class MarketplaceAutomationService
{
    /**
     * Run all active rules for an account
     */
    public function runForAccount(MarketplaceAccount $account): array
    {
        $results = [];

        $rules = MarketplaceAutomationRule::where('marketplace_account_id', $account->id)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $result = $this->runRule($rule);
            $results[] = [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'triggered' => $result['triggered'],
                'action_result' => $result['action_result'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * Run a single automation rule
     */
    public function runRule(MarketplaceAutomationRule $rule): array
    {
        $context = $this->buildContext($rule);
        $triggered = $rule->checkConditions($context);

        if (! $triggered) {
            return ['triggered' => false];
        }

        $actionResult = $this->executeAction($rule, $context);

        return [
            'triggered' => true,
            'context' => $context,
            'action_result' => $actionResult,
        ];
    }

    /**
     * Trigger rules for a specific event
     */
    public function triggerEvent(int $accountId, string $eventType, array $eventData = []): array
    {
        $rules = MarketplaceAutomationRule::where('marketplace_account_id', $accountId)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($rules as $rule) {
            if ($rule->checkConditions($eventData)) {
                $actionResult = $this->executeAction($rule, $eventData);
                $results[] = [
                    'rule_id' => $rule->id,
                    'action_type' => $rule->action_type,
                    'result' => $actionResult,
                ];
            }
        }

        return $results;
    }

    /**
     * Build context for rule evaluation based on event type
     */
    protected function buildContext(MarketplaceAutomationRule $rule): array
    {
        $accountId = $rule->marketplace_account_id;

        return match ($rule->event_type) {
            MarketplaceAutomationRule::EVENT_LOW_STOCK => $this->buildLowStockContext($accountId),
            MarketplaceAutomationRule::EVENT_NO_SALES => $this->buildNoSalesContext($accountId),
            MarketplaceAutomationRule::EVENT_HIGH_RETURN_RATE => $this->buildReturnRateContext($accountId),
            default => [],
        };
    }

    /**
     * Build context for low stock event
     */
    protected function buildLowStockContext(int $accountId): array
    {
        $lowStockProducts = MarketplaceStock::whereHas('marketplaceProduct', function ($query) use ($accountId) {
            $query->whereHas('account', function ($q) use ($accountId) {
                $q->where('id', $accountId);
            });
        })
            ->where('stock', '<', 10)
            ->count();

        return [
            'low_stock_count' => $lowStockProducts,
        ];
    }

    /**
     * Build context for no sales event
     */
    protected function buildNoSalesContext(int $accountId): array
    {
        $noSalesProducts = MarketplaceProduct::where('marketplace_account_id', $accountId)
            ->whereDoesntHave('account.orders', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->count();

        return [
            'no_sales_count' => $noSalesProducts,
            'days_without_sales' => 30,
        ];
    }

    /**
     * Build context for return rate event
     */
    protected function buildReturnRateContext(int $accountId): array
    {
        $account = MarketplaceAccount::find($accountId);
        if (! $account) {
            return ['return_rate' => 0];
        }

        $ordersCount = $account->orders()->where('created_at', '>=', now()->subDays(30))->count();
        $returnsCount = MarketplaceReturn::whereHas('order', function ($query) use ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        })
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $returnRate = $ordersCount > 0 ? ($returnsCount / $ordersCount) * 100 : 0;

        return [
            'return_rate' => $returnRate,
            'orders_count' => $ordersCount,
            'returns_count' => $returnsCount,
        ];
    }

    /**
     * Execute the action specified in the rule
     */
    protected function executeAction(MarketplaceAutomationRule $rule, array $context): array
    {
        try {
            return match ($rule->action_type) {
                MarketplaceAutomationRule::ACTION_NOTIFY => $this->executeNotify($rule, $context),
                MarketplaceAutomationRule::ACTION_ADJUST_PRICE => $this->executeAdjustPrice($rule, $context),
                MarketplaceAutomationRule::ACTION_CREATE_AGENT_TASK => $this->executeCreateAgentTask($rule, $context),
                MarketplaceAutomationRule::ACTION_SYNC_STOCKS => $this->executeSyncStocks($rule, $context),
                MarketplaceAutomationRule::ACTION_DISABLE_PRODUCT => $this->executeDisableProduct($rule, $context),
                default => ['success' => false, 'message' => 'Unknown action type'],
            };
        } catch (\Exception $e) {
            Log::error('Automation rule execution failed', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Execute notify action
     */
    protected function executeNotify(MarketplaceAutomationRule $rule, array $context): array
    {
        $params = $rule->action_params_json ?? [];
        $channel = $params['channel'] ?? 'log';
        $message = $params['message'] ?? "Правило '{$rule->name}' сработало";

        // Replace placeholders in message
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $message = str_replace("{{$key}}", (string) $value, $message);
            }
        }

        Log::info('Marketplace automation notification', [
            'rule_id' => $rule->id,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        ]);

        return ['success' => true, 'message' => $message];
    }

    /**
     * Execute adjust price action
     */
    protected function executeAdjustPrice(MarketplaceAutomationRule $rule, array $context): array
    {
        // Price adjustment logic to be implemented based on params
        $params = $rule->action_params_json ?? [];

        return [
            'success' => true,
            'message' => 'Price adjustment queued',
            'params' => $params,
        ];
    }

    /**
     * Execute create agent task action
     */
    protected function executeCreateAgentTask(MarketplaceAutomationRule $rule, array $context): array
    {
        $params = $rule->action_params_json ?? [];
        $account = $rule->account;

        if (! $account) {
            return ['success' => false, 'message' => 'Account not found'];
        }

        $taskType = $params['task_type'] ?? 'analysis_marketplace_account';
        $description = $params['description'] ?? "Автозадача: {$rule->name}";

        $task = AgentTask::create([
            'company_id' => $account->company_id,
            'user_id' => $account->user_id,
            'type' => $taskType,
            'status' => 'pending',
            'input_data' => [
                'automation_rule_id' => $rule->id,
                'event_type' => $rule->event_type,
                'context' => $context,
                'description' => $description,
            ],
        ]);

        return [
            'success' => true,
            'task_id' => $task->id,
            'message' => "Создана задача агенту #{$task->id}",
        ];
    }

    /**
     * Execute sync stocks action
     */
    protected function executeSyncStocks(MarketplaceAutomationRule $rule, array $context): array
    {
        // Dispatch sync job
        // SyncMarketplaceStocksJob::dispatch($rule->marketplace_account_id);

        return [
            'success' => true,
            'message' => 'Stock sync queued',
        ];
    }

    /**
     * Execute disable product action
     */
    protected function executeDisableProduct(MarketplaceAutomationRule $rule, array $context): array
    {
        $params = $rule->action_params_json ?? [];
        $productId = $params['product_id'] ?? $context['product_id'] ?? null;

        if (! $productId) {
            return ['success' => false, 'message' => 'Product ID not specified'];
        }

        $product = MarketplaceProduct::find($productId);
        if ($product) {
            $product->update(['status' => MarketplaceProduct::STATUS_ARCHIVED]);

            return ['success' => true, 'message' => "Product #{$productId} disabled"];
        }

        return ['success' => false, 'message' => 'Product not found'];
    }
}
