<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Initiate payment for a subscription
     */
    public function initiate(Request $request, Subscription $subscription): RedirectResponse|View
    {
        // Verify user owns the company
        $user = $request->user();
        if (! $user || $subscription->company_id !== $user->company_id) {
            abort(403, 'Forbidden');
        }

        // Verify subscription status
        if ($subscription->status !== 'pending') {
            return redirect()
                ->route('company.profile', ['tab' => 'billing'])
                ->with('error', 'Эта подписка не ожидает оплаты');
        }

        $plan = $subscription->plan;

        // Show payment method selection page
        return view('payment.select-method', [
            'subscription' => $subscription,
            'plan' => $plan,
            'amount' => $plan->price,
            'currency' => $plan->currency,
        ]);
    }

    /**
     * Initiate Click payment
     */
    public function initiateClick(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();
        if (! $user || $subscription->company_id !== $user->company_id) {
            abort(403);
        }

        $plan = $subscription->plan;

        // Click parameters
        $merchantId = config('payments.click.merchant_id');
        $serviceId = config('payments.click.service_id');
        $secretKey = config('payments.click.secret_key');

        // Generate transaction ID
        $transactionId = 'SUB-'.$subscription->id.'-'.time();

        // Store transaction info
        $subscription->update([
            'payment_method' => 'click',
            'payment_reference' => $transactionId,
        ]);

        // Build Click payment URL
        $returnUrl = route('payment.callback.click', ['subscription' => $subscription->id]);
        $amount = $plan->price;

        $clickUrl = 'https://my.click.uz/services/pay?'.http_build_query([
            'service_id' => $serviceId,
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'transaction_param' => $transactionId,
            'return_url' => $returnUrl,
        ]);

        return redirect($clickUrl);
    }

    /**
     * Initiate Payme payment
     */
    public function initiatePayme(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();
        if (! $user || $subscription->company_id !== $user->company_id) {
            abort(403);
        }

        $plan = $subscription->plan;

        // Payme parameters
        $merchantId = config('payments.payme.merchant_id');

        // Generate transaction ID
        $transactionId = 'SUB-'.$subscription->id.'-'.time();

        // Store transaction info
        $subscription->update([
            'payment_method' => 'payme',
            'payment_reference' => $transactionId,
        ]);

        // Build Payme payment URL
        $amount = $plan->price * 100; // Payme expects amount in tiyin (1 UZS = 100 tiyin)
        $returnUrl = route('payment.callback.payme', ['subscription' => $subscription->id]);

        // Encode account parameter
        $account = base64_encode(json_encode([
            'subscription_id' => $subscription->id,
        ]));

        $paymeUrl = 'https://checkout.paycom.uz/'.urlencode($merchantId).'?'.http_build_query([
            'amount' => $amount,
            'account' => $account,
            'return_url' => $returnUrl,
        ]);

        return redirect($paymeUrl);
    }

    /**
     * Click payment callback
     */
    public function callbackClick(Request $request, Subscription $subscription): RedirectResponse
    {
        // This is for user return, actual payment confirmation comes via webhook

        return redirect()
            ->route('company.profile', ['tab' => 'billing'])
            ->with('success', 'Платеж обрабатывается. Мы уведомим вас о результате.');
    }

    /**
     * Payme payment callback
     */
    public function callbackPayme(Request $request, Subscription $subscription): RedirectResponse
    {
        // This is for user return, actual payment confirmation comes via webhook

        return redirect()
            ->route('company.profile', ['tab' => 'billing'])
            ->with('success', 'Платеж обрабатывается. Мы уведомим вас о результате.');
    }

    /**
     * Renew existing subscription payment
     */
    public function renew(Request $request, Subscription $subscription): RedirectResponse|View
    {
        $user = $request->user();
        if (! $user || $subscription->company_id !== $user->company_id) {
            abort(403);
        }

        // Check if user is owner
        if (! $user->isOwnerOf($subscription->company_id)) {
            return redirect()
                ->back()
                ->with('error', 'Только владелец компании может продлевать подписку');
        }

        $plan = $subscription->plan;

        // Show payment method selection page for renewal
        return view('payment.select-method', [
            'subscription' => $subscription,
            'plan' => $plan,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'is_renewal' => true,
        ]);
    }
}
