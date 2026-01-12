<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClickWebhookController extends Controller
{
    /**
     * Handle Click prepare request
     */
    public function prepare(Request $request): JsonResponse
    {
        Log::info('Click prepare request', $request->all());

        $clickTransId = $request->input('click_trans_id');
        $serviceId = $request->input('service_id');
        $merchantTransId = $request->input('merchant_trans_id');
        $amount = $request->input('amount');
        $signTime = $request->input('sign_time');
        $signString = $request->input('sign_string');

        // Verify signature
        if (!$this->verifySignature($request)) {
            return response()->json([
                'error' => -1,
                'error_note' => 'SIGN CHECK FAILED!',
            ]);
        }

        // Parse transaction ID
        $transactionId = $merchantTransId;
        $parts = explode('-', $transactionId);

        if (count($parts) < 2 || $parts[0] !== 'SUB') {
            return response()->json([
                'error' => -5,
                'error_note' => 'Transaction not found',
            ]);
        }

        $subscriptionId = (int) $parts[1];
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Subscription not found',
            ]);
        }

        // Check amount
        $expectedAmount = $subscription->plan->price;
        if ((float) $amount !== (float) $expectedAmount) {
            return response()->json([
                'error' => -2,
                'error_note' => 'Incorrect parameter amount',
            ]);
        }

        // Check if already paid
        if ($subscription->status === 'active') {
            return response()->json([
                'error' => -4,
                'error_note' => 'Already paid',
            ]);
        }

        // Success prepare
        return response()->json([
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_prepare_id' => $subscription->id,
            'error' => 0,
            'error_note' => 'Success',
        ]);
    }

    /**
     * Handle Click complete request
     */
    public function complete(Request $request): JsonResponse
    {
        Log::info('Click complete request', $request->all());

        $clickTransId = $request->input('click_trans_id');
        $serviceId = $request->input('service_id');
        $merchantTransId = $request->input('merchant_trans_id');
        $merchantPrepareId = $request->input('merchant_prepare_id');
        $amount = $request->input('amount');
        $action = $request->input('action');
        $error = $request->input('error');
        $signTime = $request->input('sign_time');
        $signString = $request->input('sign_string');

        // Verify signature
        if (!$this->verifySignature($request)) {
            return response()->json([
                'error' => -1,
                'error_note' => 'SIGN CHECK FAILED!',
            ]);
        }

        $subscription = Subscription::find($merchantPrepareId);

        if (!$subscription) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Subscription not found',
            ]);
        }

        // Check if payment was successful
        if ((int) $action === 0) {
            // Payment cancelled
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $subscription->id,
                'error' => -9,
                'error_note' => 'Transaction cancelled',
            ]);
        }

        if ((int) $error < 0) {
            // Payment failed
            return response()->json([
                'click_trans_id' => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'merchant_confirm_id' => $subscription->id,
                'error' => $error,
                'error_note' => 'Payment failed',
            ]);
        }

        // Payment successful - activate subscription
        $subscription->update([
            'status' => 'active',
            'amount_paid' => $amount,
            'starts_at' => now(),
            'ends_at' => match($subscription->plan->billing_period) {
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
            $request->input('click_trans_id') .
            $request->input('service_id') .
            $secretKey .
            $request->input('merchant_trans_id') .
            ($request->input('merchant_prepare_id') ?: '') .
            $request->input('amount') .
            $request->input('action') .
            $request->input('sign_time');

        $signHash = md5($signString);

        return $signHash === $request->input('sign_string');
    }
}
