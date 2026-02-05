<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymeWebhookController extends Controller
{
    /**
     * Обработка Payme вебхука (JSON-RPC)
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('Payme webhook request', $request->all());

        // Проверка авторизации
        if (! $this->verifyAuthorization($request)) {
            return $this->error(-32504, 'Insufficient privilege to perform this method', $request->input('id'));
        }

        $validated = $request->validate([
            'method' => ['required', 'string'],
            'params' => ['required', 'array'],
            'id' => ['required'],
        ]);

        $method = $validated['method'];
        $params = $validated['params'];
        $id = $validated['id'];

        return match ($method) {
            'CheckPerformTransaction' => $this->checkPerformTransaction($params, $id),
            'CreateTransaction' => $this->createTransaction($params, $id),
            'PerformTransaction' => $this->performTransaction($params, $id),
            'CancelTransaction' => $this->cancelTransaction($params, $id),
            'CheckTransaction' => $this->checkTransaction($params, $id),
            default => $this->error(-32601, 'Method not found', $id),
        };
    }

    /**
     * Check if transaction can be performed
     */
    protected function checkPerformTransaction(array $params, $id): JsonResponse
    {
        $account = $params['account'] ?? [];
        $amount = $params['amount'] ?? 0;

        $subscriptionId = $account['subscription_id'] ?? null;
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            return $this->error(-31050, 'Subscription not found', $id);
        }

        // Check amount (Payme uses tiyin, 1 UZS = 100 tiyin)
        $expectedAmount = $subscription->plan->price * 100;
        if ((int) $amount !== (int) $expectedAmount) {
            return $this->error(-31001, 'Incorrect amount', $id);
        }

        // Check if already paid
        if ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isFuture()) {
            return $this->error(-31051, 'Subscription already active', $id);
        }

        return response()->json([
            'result' => [
                'allow' => true,
            ],
            'id' => $id,
        ]);
    }

    /**
     * Create transaction
     */
    protected function createTransaction(array $params, $id): JsonResponse
    {
        $account = $params['account'] ?? [];
        $amount = $params['amount'] ?? 0;
        $transId = $params['id'] ?? null;
        $time = $params['time'] ?? null;

        $subscriptionId = $account['subscription_id'] ?? null;
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            return $this->error(-31050, 'Subscription not found', $id);
        }

        // Store transaction reference
        $subscription->update([
            'payment_method' => 'payme',
            'payment_reference' => $transId,
        ]);

        return response()->json([
            'result' => [
                'create_time' => $time,
                'transaction' => (string) $subscription->id,
                'state' => 1, // Created
            ],
            'id' => $id,
        ]);
    }

    /**
     * Perform transaction (complete payment)
     */
    protected function performTransaction(array $params, $id): JsonResponse
    {
        $transId = $params['id'] ?? null;

        // Find subscription by payment reference
        $subscription = Subscription::where('payment_reference', $transId)
            ->where('payment_method', 'payme')
            ->first();

        if (! $subscription) {
            return $this->error(-31003, 'Transaction not found', $id);
        }

        // Activate subscription
        $amount = $subscription->plan->price;

        $subscription->update([
            'status' => 'active',
            'amount_paid' => $amount,
            'starts_at' => now(),
            'ends_at' => match ($subscription->plan->billing_period) {
                'monthly' => now()->addMonth(),
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            },
            'usage_reset_at' => now(),
        ]);

        Log::info("Subscription {$subscription->id} activated via Payme payment");

        return response()->json([
            'result' => [
                'transaction' => (string) $subscription->id,
                'perform_time' => now()->timestamp * 1000,
                'state' => 2, // Completed
            ],
            'id' => $id,
        ]);
    }

    /**
     * Cancel transaction
     */
    protected function cancelTransaction(array $params, $id): JsonResponse
    {
        $transId = $params['id'] ?? null;
        $reason = $params['reason'] ?? null;

        $subscription = Subscription::where('payment_reference', $transId)
            ->where('payment_method', 'payme')
            ->first();

        if (! $subscription) {
            return $this->error(-31003, 'Transaction not found', $id);
        }

        // Reset subscription to pending if it was activated
        if ($subscription->status === 'active') {
            $subscription->update([
                'status' => 'pending',
                'amount_paid' => 0,
                'starts_at' => null,
                'ends_at' => null,
            ]);
        }

        Log::info("Subscription {$subscription->id} payment cancelled via Payme (reason: {$reason})");

        return response()->json([
            'result' => [
                'transaction' => (string) $subscription->id,
                'cancel_time' => now()->timestamp * 1000,
                'state' => -2, // Cancelled after perform
            ],
            'id' => $id,
        ]);
    }

    /**
     * Check transaction status
     */
    protected function checkTransaction(array $params, $id): JsonResponse
    {
        $transId = $params['id'] ?? null;

        $subscription = Subscription::where('payment_reference', $transId)
            ->where('payment_method', 'payme')
            ->first();

        if (! $subscription) {
            return $this->error(-31003, 'Transaction not found', $id);
        }

        $state = match ($subscription->status) {
            'pending' => 1,
            'active' => 2,
            'cancelled' => -2,
            default => 0,
        };

        return response()->json([
            'result' => [
                'create_time' => $subscription->created_at->timestamp * 1000,
                'perform_time' => $subscription->starts_at ? $subscription->starts_at->timestamp * 1000 : 0,
                'cancel_time' => $subscription->cancelled_at ? $subscription->cancelled_at->timestamp * 1000 : 0,
                'transaction' => (string) $subscription->id,
                'state' => $state,
                'reason' => null,
            ],
            'id' => $id,
        ]);
    }

    /**
     * Verify HTTP Basic Authorization
     */
    protected function verifyAuthorization(Request $request): bool
    {
        $merchantId = config('payments.payme.merchant_id');
        $secretKey = config('payments.payme.secret_key');

        $expectedAuth = 'Basic '.base64_encode("Paycom:{$secretKey}");
        $providedAuth = $request->header('Authorization');

        return $providedAuth === $expectedAuth;
    }

    /**
     * Return JSON-RPC error response
     */
    protected function error(int $code, string $message, $id): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => [
                    'ru' => $message,
                    'uz' => $message,
                    'en' => $message,
                ],
            ],
            'id' => $id,
        ]);
    }
}
