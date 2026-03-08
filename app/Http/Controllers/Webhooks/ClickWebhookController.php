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

class ClickWebhookController extends Controller
{
    /**
     * Обработка Click prepare запроса
     */
    public function prepare(Request $request): JsonResponse
    {
        Log::info('Click prepare request', $request->all());

        $validated = $request->validate([
            'click_trans_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'merchant_trans_id' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'action' => ['required', 'integer'],
            'sign_time' => ['required', 'string'],
            'sign_string' => ['required', 'string'],
        ]);

        $clickTransId = $validated['click_trans_id'];
        $merchantTransId = $validated['merchant_trans_id'];
        $amount = $validated['amount'];

        // Проверка подписи
        if (! $this->verifySignature($request)) {
            return response()->json([
                'error' => -1,
                'error_note' => 'SIGN CHECK FAILED!',
            ]);
        }

        // Парсинг идентификатора транзакции
        $parts = explode('-', $merchantTransId);
        $type = $parts[0] ?? '';

        if ($type === 'SUB') {
            return $this->prepareSubscription($parts, $clickTransId, $merchantTransId, $amount);
        } elseif ($type === 'ORDER') {
            return $this->prepareOrder($parts, $clickTransId, $merchantTransId, $amount);
        }

        return response()->json([
            'error' => -5,
            'error_note' => 'Transaction not found',
        ]);
    }

    /**
     * Обработка Click complete запроса
     */
    public function complete(Request $request): JsonResponse
    {
        Log::info('Click complete request', $request->all());

        $validated = $request->validate([
            'click_trans_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'merchant_trans_id' => ['required', 'string'],
            'merchant_prepare_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric'],
            'action' => ['required', 'integer'],
            'error' => ['required', 'integer'],
            'sign_time' => ['required', 'string'],
            'sign_string' => ['required', 'string'],
        ]);

        $clickTransId = $validated['click_trans_id'];
        $merchantTransId = $validated['merchant_trans_id'];
        $merchantPrepareId = $validated['merchant_prepare_id'];
        $amount = $validated['amount'];
        $action = $validated['action'];
        $error = $validated['error'];

        // Проверка подписи
        if (! $this->verifySignature($request)) {
            return response()->json([
                'error' => -1,
                'error_note' => 'SIGN CHECK FAILED!',
            ]);
        }

        // Парсинг типа транзакции
        $parts = explode('-', $merchantTransId);
        $type = $parts[0] ?? '';

        if ($type === 'ORDER') {
            return $this->completeOrder($clickTransId, $merchantTransId, $merchantPrepareId, $amount, $action, $error);
        }

        // Подписка (SUB) — существующая логика
        return $this->completeSubscription($clickTransId, $merchantTransId, $merchantPrepareId, $amount, $action, $error);
    }

    /**
     * Подготовка платежа для подписки
     */
    private function prepareSubscription(array $parts, int $clickTransId, string $merchantTransId, $amount): JsonResponse
    {
        if (count($parts) < 2) {
            return response()->json(['error' => -5, 'error_note' => 'Transaction not found']);
        }

        $subscriptionId = (int) $parts[1];
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            return response()->json(['error' => -5, 'error_note' => 'Subscription not found']);
        }

        $expectedAmount = $subscription->plan->price;
        if (bccomp((string) $amount, (string) $expectedAmount, 2) !== 0) {
            return response()->json(['error' => -2, 'error_note' => 'Incorrect parameter amount']);
        }

        if ($subscription->status === 'active') {
            return response()->json(['error' => -4, 'error_note' => 'Already paid']);
        }

        return response()->json([
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_prepare_id' => $subscription->id,
            'error' => 0,
            'error_note' => 'Success',
        ]);
    }

    /**
     * Подготовка платежа для заказа магазина
     */
    private function prepareOrder(array $parts, int $clickTransId, string $merchantTransId, $amount): JsonResponse
    {
        if (count($parts) < 2) {
            return response()->json(['error' => -5, 'error_note' => 'Transaction not found']);
        }

        $orderId = (int) $parts[1];
        $order = StoreOrder::find($orderId);

        if (! $order) {
            return response()->json(['error' => -5, 'error_note' => 'Order not found']);
        }

        // Проверка суммы
        if (bccomp((string) $amount, (string) $order->total, 2) !== 0) {
            return response()->json(['error' => -2, 'error_note' => 'Incorrect parameter amount']);
        }

        // Уже оплачен
        if ($order->payment_status === StoreOrder::PAYMENT_PAID) {
            return response()->json(['error' => -4, 'error_note' => 'Already paid']);
        }

        return response()->json([
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_prepare_id' => $order->id,
            'error' => 0,
            'error_note' => 'Success',
        ]);
    }

    /**
     * Завершение платежа для подписки
     */
    private function completeSubscription(int $clickTransId, string $merchantTransId, int $merchantPrepareId, $amount, int $action, int $error): JsonResponse
    {
        $subscription = Subscription::find($merchantPrepareId);

        if (! $subscription) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Subscription not found',
            ]);
        }

        // Проверка — платёж отменён
        if ($action === 0) {
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $subscription->id,
                'error' => -9,
                'error_note' => 'Transaction cancelled',
            ]);
        }

        // Проверка — ошибка платежа
        if ($error < 0) {
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $subscription->id,
                'error' => $error,
                'error_note' => 'Payment failed',
            ]);
        }

        // Платёж успешен — активация подписки в транзакции
        DB::transaction(function () use ($subscription, $amount) {
            $subscription = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            // Проверка что ещё не активирована (idempotency)
            if ($subscription->status === 'active') {
                return;
            }

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

        Log::info("Subscription {$subscription->id} activated via Click payment");

        return response()->json([
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_confirm_id' => $subscription->id,
            'error' => 0,
            'error_note' => 'Success',
        ]);
    }

    /**
     * Завершение платежа для заказа магазина
     */
    private function completeOrder(int $clickTransId, string $merchantTransId, int $merchantPrepareId, $amount, int $action, int $error): JsonResponse
    {
        $order = StoreOrder::find($merchantPrepareId);

        if (! $order) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Order not found',
            ]);
        }

        // Платёж отменён
        if ($action === 0) {
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $order->id,
                'error' => -9,
                'error_note' => 'Transaction cancelled',
            ]);
        }

        // Ошибка платежа
        if ($error < 0) {
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $order->id,
                'error' => $error,
                'error_note' => 'Payment failed',
            ]);
        }

        // Успешная оплата — обновляем статус в транзакции
        DB::transaction(function () use ($order) {
            $order = StoreOrder::where('id', $order->id)->lockForUpdate()->first();

            // Idempotency
            if ($order->payment_status === StoreOrder::PAYMENT_PAID) {
                return;
            }

            $order->update([
                'payment_status' => StoreOrder::PAYMENT_PAID,
            ]);
        });

        // Завершаем Sale в SellerMind при успешной оплате
        try {
            app(\App\Services\Store\StoreOrderService::class)->completeOrderSale($order);
        } catch (\Throwable $e) {
            Log::error('Failed to complete Sale after Click payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info("StoreOrder {$order->id} paid via Click payment");

        return response()->json([
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_confirm_id' => $order->id,
            'error' => 0,
            'error_note' => 'Success',
        ]);
    }

    /**
     * Verify Click signature
     */
    protected function verifySignature(Request $request): bool
    {
        $secretKey = config('payments.click.secret_key');

        $signString =
            $request->input('click_trans_id').
            $request->input('service_id').
            $secretKey.
            $request->input('merchant_trans_id').
            (string) ($request->input('merchant_prepare_id') ?? '').
            $request->input('amount').
            $request->input('action').
            $request->input('sign_time');

        $signHash = md5($signString);

        return $signHash === $request->input('sign_string');
    }
}
