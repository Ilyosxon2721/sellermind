<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        if (count($parts) < 2 || $parts[0] !== 'SUB') {
            return response()->json([
                'error' => -5,
                'error_note' => 'Transaction not found',
            ]);
        }

        $subscriptionId = (int) $parts[1];
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Subscription not found',
            ]);
        }

        // Проверка суммы
        $expectedAmount = $subscription->plan->price;
        if ((float) $amount !== (float) $expectedAmount) {
            return response()->json([
                'error' => -2,
                'error_note' => 'Incorrect parameter amount',
            ]);
        }

        // Проверка — уже оплачено
        if ($subscription->status === 'active') {
            return response()->json([
                'error' => -4,
                'error_note' => 'Already paid',
            ]);
        }

        // Успешный prepare
        return response()->json([
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_prepare_id' => $subscription->id,
            'error' => 0,
            'error_note' => 'Success',
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

        $subscription = Subscription::find($merchantPrepareId);

        if (! $subscription) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Subscription not found',
            ]);
        }

        // Проверка — платёж отменён
        if ((int) $action === 0) {
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $subscription->id,
                'error' => -9,
                'error_note' => 'Transaction cancelled',
            ]);
        }

        // Проверка — ошибка платежа
        if ((int) $error < 0) {
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $subscription->id,
                'error' => $error,
                'error_note' => 'Payment failed',
            ]);
        }

        // Платёж успешен — активация подписки
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
            ($request->input('merchant_prepare_id') ?: '').
            $request->input('amount').
            $request->input('action').
            $request->input('sign_time');

        $signHash = md5($signString);

        return $signHash === $request->input('sign_string');
    }
}
