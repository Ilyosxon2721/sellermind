<?php
// file: app/Http/Controllers/MarketplaceWebhookController.php

namespace App\Http\Controllers;

use App\Models\MarketplaceWebhook;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceAutomationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MarketplaceWebhookController extends Controller
{
    public function __construct(
        protected MarketplaceAutomationService $automationService
    ) {}

    /**
     * Handle incoming webhook from any marketplace
     */
    public function handle(string $marketplace, Request $request): JsonResponse
    {
        Log::info("Marketplace webhook received", [
            'marketplace' => $marketplace,
            'event_type' => $request->input('event_type'),
            'ip' => $request->ip(),
        ]);

        // Try to identify account by headers or payload
        $accountId = $this->identifyAccount($marketplace, $request);

        // Store the webhook
        $webhook = MarketplaceWebhook::create([
            'marketplace' => $marketplace,
            'marketplace_account_id' => $accountId,
            'event_type' => $this->extractEventType($marketplace, $request),
            'status' => MarketplaceWebhook::STATUS_NEW,
            'payload' => $request->all(),
        ]);

        // Process webhook asynchronously
        // ProcessMarketplaceWebhookJob::dispatch($webhook->id);

        // For now, process synchronously
        $this->processWebhook($webhook);

        return response()->json(['status' => 'ok', 'webhook_id' => $webhook->id]);
    }

    /**
     * Handle webhook for a specific account
     */
    public function handleForAccount(string $marketplace, int $accountId, Request $request): JsonResponse
    {
        $account = MarketplaceAccount::where('id', $accountId)
            ->where('marketplace', $marketplace)
            ->first();

        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $webhook = MarketplaceWebhook::create([
            'marketplace' => $marketplace,
            'marketplace_account_id' => $accountId,
            'event_type' => $this->extractEventType($marketplace, $request),
            'status' => MarketplaceWebhook::STATUS_NEW,
            'payload' => $request->all(),
        ]);

        $this->processWebhook($webhook);

        return response()->json(['status' => 'ok', 'webhook_id' => $webhook->id]);
    }

    /**
     * Try to identify the account from webhook data
     */
    protected function identifyAccount(string $marketplace, Request $request): ?int
    {
        // Marketplace-specific account identification logic
        $shopId = match ($marketplace) {
            'wb' => $request->header('X-Shop-Id') ?? $request->input('shopId'),
            'ozon' => $request->header('X-Client-Id') ?? $request->input('client_id'),
            'uzum' => $request->input('seller_id'),
            'ym' => $request->input('campaign_id'),
            default => null,
        };

        if (!$shopId) {
            return null;
        }

        $account = MarketplaceAccount::where('marketplace', $marketplace)
            ->where('shop_id', $shopId)
            ->first();

        return $account?->id;
    }

    /**
     * Extract event type from webhook payload
     */
    protected function extractEventType(string $marketplace, Request $request): ?string
    {
        return match ($marketplace) {
            'wb' => $request->input('type') ?? $request->header('X-Event-Type'),
            'ozon' => $request->input('message_type'),
            'uzum' => $request->input('event'),
            'ym' => $request->input('eventType'),
            default => $request->input('event_type') ?? $request->input('type'),
        };
    }

    /**
     * Process a webhook
     */
    protected function processWebhook(MarketplaceWebhook $webhook): void
    {
        try {
            // Trigger automation rules for this event
            if ($webhook->marketplace_account_id && $webhook->event_type) {
                $eventType = $this->mapToAutomationEvent($webhook->event_type);

                if ($eventType) {
                    $this->automationService->triggerEvent(
                        $webhook->marketplace_account_id,
                        $eventType,
                        $webhook->payload ?? []
                    );
                }
            }

            $webhook->markAsProcessed();
        } catch (\Exception $e) {
            Log::error("Webhook processing failed", [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            $webhook->markAsError($e->getMessage());
        }
    }

    /**
     * Map webhook event type to automation event type
     */
    protected function mapToAutomationEvent(string $webhookEvent): ?string
    {
        $mapping = [
            'order.created' => 'order_created',
            'order.delivered' => 'order_delivered',
            'order.cancelled' => 'order_canceled',
            'order.canceled' => 'order_canceled',
            'return.created' => 'return_created',
            'payout.completed' => 'payout_received',
            // Add more mappings as needed
        ];

        return $mapping[strtolower($webhookEvent)] ?? null;
    }
}
