<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class IntegrationLinkController extends Controller
{
    /**
     * Show the integration link page
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        $link = $company
            ? IntegrationLink::where('company_id', $company->id)
                ->where('external_system', 'risment')
                ->latest()
                ->first()
            : null;

        return view('pages.integration-link', compact('link'));
    }

    /**
     * Store/update the RISMENT link token
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'link_token' => 'required|string|min:8|max:128',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $user = $request->user();
        $company = $user->companies()->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'No company found. Please create a company first.',
            ], 422);
        }

        // Deactivate any existing RISMENT links for this company
        IntegrationLink::where('company_id', $company->id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Create new link
        $link = IntegrationLink::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'external_system' => 'risment',
            'link_token' => $validated['link_token'],
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'is_active' => true,
            'linked_at' => now(),
        ]);

        // Уведомить RISMENT о подтверждении связки через Redis
        $this->notifyRisment('link.confirm', $link);

        return response()->json([
            'success' => true,
            'message' => 'RISMENT integration linked successfully.',
            'data' => [
                'id' => $link->id,
                'linked_at' => $link->linked_at->toIso8601String(),
                'warehouse_id' => $link->warehouse_id,
            ],
        ]);
    }

    /**
     * Обновить настройки интеграции (склад и др.)
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $user = $request->user();
        $company = $user->companies()->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'No company found.',
            ], 422);
        }

        $link = IntegrationLink::where('company_id', $company->id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'No active RISMENT integration found.',
            ], 404);
        }

        $link->update([
            'warehouse_id' => $validated['warehouse_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Integration settings updated.',
            'data' => [
                'warehouse_id' => $link->warehouse_id,
            ],
        ]);
    }

    /**
     * Disconnect RISMENT integration
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if ($company) {
            $link = IntegrationLink::where('company_id', $company->id)
                ->where('external_system', 'risment')
                ->where('is_active', true)
                ->first();

            if ($link) {
                $link->update(['is_active' => false]);

                // Уведомить RISMENT об отключении связки
                $this->notifyRisment('link.disconnect', $link);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'RISMENT integration disconnected.',
        ]);
    }

    /**
     * Отправить уведомление в RISMENT через Redis очередь risment:link
     */
    private function notifyRisment(string $event, IntegrationLink $link): void
    {
        try {
            $message = json_encode([
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'source' => 'sellermind',
                'link_token' => $link->link_token,
                'data' => [
                    'company_id' => $link->company_id,
                    'linked_at' => $link->linked_at?->toIso8601String(),
                    'warehouse_id' => $link->warehouse_id,
                ],
            ], JSON_UNESCAPED_UNICODE);

            Redis::connection('integration')->rpush('risment:link', $message);

            Log::info("IntegrationLink: Pushed {$event} to risment:link", [
                'company_id' => $link->company_id,
                'link_token' => mb_substr($link->link_token, 0, 8) . '...',
            ]);
        } catch (\Exception $e) {
            Log::error("IntegrationLink: Failed to notify RISMENT ({$event})", [
                'company_id' => $link->company_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
