<?php

namespace App\Http\Controllers\Api\Risment;

use App\Http\Controllers\Controller;
use App\Models\Risment\RismentWebhookEndpoint;
use App\Services\Risment\RismentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    protected const ALLOWED_EVENTS = [
        'product.created',
        'product.updated',
        'stock.updated',
        'order.shipped',
        'order.delivered',
    ];

    /**
     * POST /api/v1/integration/webhooks
     * Register a new webhook endpoint
     */
    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('risment_company');

        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', self::ALLOWED_EVENTS),
        ]);

        $secret = Str::random(32);

        $endpoint = RismentWebhookEndpoint::create([
            'company_id' => $company->id,
            'url' => $validated['url'],
            'secret' => $secret,
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $endpoint->id,
                'url' => $endpoint->url,
                'events' => $endpoint->events,
                'secret' => $secret, // shown only once
                'is_active' => $endpoint->is_active,
                'created_at' => $endpoint->created_at,
            ],
            'message' => 'Save the secret securely. It will not be shown again. '
                . 'Use it to verify webhook signatures via X-Risment-Signature header (HMAC-SHA256).',
        ], 201);
    }

    /**
     * GET /api/v1/integration/webhooks
     * List webhook endpoints
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('risment_company');

        $endpoints = RismentWebhookEndpoint::where('company_id', $company->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'url' => $e->url,
                'events' => $e->events,
                'is_active' => $e->is_active,
                'created_at' => $e->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $endpoints,
            'available_events' => self::ALLOWED_EVENTS,
        ]);
    }

    /**
     * DELETE /api/v1/integration/webhooks/{id}
     * Remove a webhook endpoint
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $company = $request->attributes->get('risment_company');

        $endpoint = RismentWebhookEndpoint::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $endpoint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook endpoint removed.',
        ]);
    }

    /**
     * POST /api/v1/integration/webhooks/{id}/test
     * Send a test webhook
     */
    public function test(Request $request, int $id, RismentWebhookService $webhookService): JsonResponse
    {
        $company = $request->attributes->get('risment_company');

        $endpoint = RismentWebhookEndpoint::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        // Send a test event synchronously
        $testPayload = [
            'event' => 'test',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook from SellerMind RISMENT integration.',
            ],
        ];

        $signature = hash_hmac('sha256', json_encode($testPayload), $endpoint->secret);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Risment-Signature' => $signature,
                    'X-Risment-Event' => 'test',
                    'X-Risment-Delivery' => 'test-' . Str::random(8),
                ])
                ->post($endpoint->url, $testPayload);

            return response()->json([
                'success' => $response->successful(),
                'data' => [
                    'status_code' => $response->status(),
                    'response_body' => mb_substr($response->body(), 0, 500),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deliver test webhook',
                'error' => $e->getMessage(),
            ], 502);
        }
    }
}
