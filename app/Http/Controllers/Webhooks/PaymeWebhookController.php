<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreOrder;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // StoreOrder оплата
        if (isset($account['order_id'])) {
            return $this->checkPerformOrder($account, $amount, $id);
        }

        // Подписка (существующая логика)
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

        // StoreOrder оплата
        if (isset($account['order_id'])) {
            return $this->createOrderTransaction($account, $amount, $transId, $time, $id);
        }

        // Подписка (существующая логика)
        $subscriptionId = $account['subscription_id'] ?? null;
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            return $this->error(-31050, 'Subscription not found', $id);
        }

        // Валидация суммы (Payme использует тийин, 1 UZS = 100 тийин)
        $expectedAmount = $subscription->plan->price * 100;
        if ((int) $amount !== (int) $expectedAmount) {
            return $this->error(-31001, 'Incorrect amount', $id);
        }

        // Проверка что подписка не уже активна
        if ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isFuture()) {
            return $this->error(-31051, 'Subscription already active', $id);
        }

        // Idempotency: если транзакция уже создана с этим transId — вернуть success
        if ($subscription->payment_reference === $transId && $subscription->payment_method === 'payme') {
            return response()->json([
                'result' => [
                    'create_time' => $subscription->updated_at->timestamp * 1000,
                    'transaction' => (string) $subscription->id,
                    'state' => 1,
                ],
                'id' => $id,
            ]);
        }

        // Сохранение ссылки на транзакцию
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

        // Сначала ищем подписку
        $subscription = Subscription::where('payment_reference', $transId)
            ->where('payment_method', 'payme')
            ->first();

        if (! $subscription) {
            // Ищем заказ магазина
            $order = StoreOrder::where('payment_id', $transId)->first();

            if ($order) {
                return $this->performOrderTransaction($order, $transId, $id);
            }

            return $this->error(-31003, 'Transaction not found', $id);
        }

        // Idempotency: если уже выполнена — вернуть success
        if ($subscription->status === 'active') {
            return response()->json([
                'result' => [
                    'transaction' => (string) $subscription->id,
                    'perform_time' => $subscription->starts_at ? $subscription->starts_at->timestamp * 1000 : now()->timestamp * 1000,
                    'state' => 2,
                ],
                'id' => $id,
            ]);
        }

        // Активация подписки в транзакции с блокировкой
        DB::transaction(function () use ($subscription) {
            $subscription = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            // Повторная проверка после блокировки
            if ($subscription->status === 'active') {
                return;
            }

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
        });

        $subscription->refresh();

        Log::info("Subscription {$subscription->id} activated via Payme payment");

        return response()->json([
            'result' => [
                'transaction' => (string) $subscription->id,
                'perform_time' => $subscription->starts_at ? $subscription->starts_at->timestamp * 1000 : now()->timestamp * 1000,
                'state' => 2,
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

        // Сначала ищем подписку
        $subscription = Subscription::where('payment_reference', $transId)
            ->where('payment_method', 'payme')
            ->first();

        if (! $subscription) {
            // Ищем заказ магазина
            $order = StoreOrder::where('payment_id', $transId)->first();

            if ($order) {
                // Определяем state: -1 = отмена до выполнения, -2 = отмена после выполнения
                $wasPaid = $order->payment_status === StoreOrder::PAYMENT_PAID;

                DB::transaction(function () use ($order) {
                    $order = StoreOrder::where('id', $order->id)->lockForUpdate()->first();
                    $order->update(['payment_status' => StoreOrder::PAYMENT_FAILED]);
                });

                $order->refresh();

                Log::info("StoreOrder {$order->id} payment cancelled via Payme (reason: {$reason})");

                return response()->json([
                    'result' => [
                        'transaction' => (string) $order->id,
                        'cancel_time' => now()->timestamp * 1000,
                        'state' => $wasPaid ? -2 : -1,
                    ],
                    'id' => $id,
                ]);
            }

            return $this->error(-31003, 'Transaction not found', $id);
        }

        // Отмена подписки в транзакции с блокировкой
        DB::transaction(function () use ($subscription) {
            $subscription = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            if ($subscription->status === 'active') {
                $subscription->update([
                    'status' => 'cancelled',
                    'amount_paid' => 0,
                    'starts_at' => null,
                    'ends_at' => null,
                ]);
            }
        });

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

        // Сначала ищем подписку
        $subscription = Subscription::where('payment_reference', $transId)
            ->where('payment_method', 'payme')
            ->first();

        if (! $subscription) {
            // Ищем заказ магазина
            $order = StoreOrder::where('payment_id', $transId)->first();

            if ($order) {
                // State: 1=создана, 2=выполнена, -1=отменена до выполнения, -2=отменена после выполнения
                $state = match ($order->payment_status) {
                    StoreOrder::PAYMENT_PENDING => 1,
                    StoreOrder::PAYMENT_PAID => 2,
                    StoreOrder::PAYMENT_FAILED => -1, // отмена до выполнения (по умолчанию)
                    StoreOrder::PAYMENT_REFUNDED => -2, // отмена после выполнения
                    default => 0,
                };

                return response()->json([
                    'result' => [
                        'create_time' => $order->created_at->timestamp * 1000,
                        'perform_time' => $order->payment_status === StoreOrder::PAYMENT_PAID
                            ? $order->updated_at->timestamp * 1000
                            : 0,
                        'cancel_time' => $order->payment_status === StoreOrder::PAYMENT_FAILED
                            ? $order->updated_at->timestamp * 1000
                            : 0,
                        'transaction' => (string) $order->id,
                        'state' => $state,
                        'reason' => null,
                    ],
                    'id' => $id,
                ]);
            }

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
     * Проверка возможности выполнения транзакции для заказа магазина
     */
    private function checkPerformOrder(array $account, $amount, $id): JsonResponse
    {
        $order = StoreOrder::find($account['order_id']);

        if (! $order) {
            return $this->error(-31050, 'Order not found', $id);
        }

        // Payme использует тийин (1 UZS = 100 тийин)
        $expectedAmount = (int) ($order->total * 100);
        if ((int) $amount !== $expectedAmount) {
            return $this->error(-31001, 'Incorrect amount', $id);
        }

        // Уже оплачен
        if ($order->payment_status === StoreOrder::PAYMENT_PAID) {
            return $this->error(-31051, 'Order already paid', $id);
        }

        return response()->json([
            'result' => ['allow' => true],
            'id' => $id,
        ]);
    }

    /**
     * Создание транзакции для заказа магазина
     */
    private function createOrderTransaction(array $account, $amount, $transId, $time, $id): JsonResponse
    {
        $order = StoreOrder::find($account['order_id']);

        if (! $order) {
            return $this->error(-31050, 'Order not found', $id);
        }

        $expectedAmount = (int) ($order->total * 100);
        if ((int) $amount !== $expectedAmount) {
            return $this->error(-31001, 'Incorrect amount', $id);
        }

        if ($order->payment_status === StoreOrder::PAYMENT_PAID) {
            return $this->error(-31051, 'Order already paid', $id);
        }

        // Idempotency: если транзакция уже создана с этим transId
        if ($order->payment_id === $transId) {
            return response()->json([
                'result' => [
                    'create_time' => $order->updated_at->timestamp * 1000,
                    'transaction' => (string) $order->id,
                    'state' => 1,
                ],
                'id' => $id,
            ]);
        }

        // Если заказ уже привязан к другой транзакции Payme — ошибка -31008
        if ($order->payment_id !== null && $order->payment_id !== $transId) {
            return $this->error(-31008, 'Unable to perform operation', $id);
        }

        // Сохраняем ссылку на транзакцию Payme
        $order->update(['payment_id' => $transId]);

        return response()->json([
            'result' => [
                'create_time' => $time,
                'transaction' => (string) $order->id,
                'state' => 1,
            ],
            'id' => $id,
        ]);
    }

    /**
     * Выполнение транзакции для заказа магазина (завершение оплаты)
     */
    private function performOrderTransaction(StoreOrder $order, string $transId, $id): JsonResponse
    {
        // Idempotency: уже оплачен
        if ($order->payment_status === StoreOrder::PAYMENT_PAID) {
            return response()->json([
                'result' => [
                    'transaction' => (string) $order->id,
                    'perform_time' => $order->updated_at->timestamp * 1000,
                    'state' => 2,
                ],
                'id' => $id,
            ]);
        }

        // Оплата заказа в транзакции с блокировкой
        DB::transaction(function () use ($order) {
            $order = StoreOrder::where('id', $order->id)->lockForUpdate()->first();

            if ($order->payment_status === StoreOrder::PAYMENT_PAID) {
                return;
            }

            $order->update([
                'payment_status' => StoreOrder::PAYMENT_PAID,
            ]);
        });

        $order->refresh();

        // Завершаем Sale в SellerMind при успешной оплате
        try {
            app(\App\Services\Store\StoreOrderService::class)->completeOrderSale($order);
        } catch (\Throwable $e) {
            Log::error('Failed to complete Sale after Payme payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info("StoreOrder {$order->id} paid via Payme payment");

        return response()->json([
            'result' => [
                'transaction' => (string) $order->id,
                'perform_time' => $order->updated_at->timestamp * 1000,
                'state' => 2,
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
