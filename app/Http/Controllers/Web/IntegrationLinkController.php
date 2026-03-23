<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        // Деактивировать все старые RISMENT-связки этой компании
        IntegrationLink::where('company_id', $company->id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Создать или обновить запись по link_token (unique constraint)
        $link = IntegrationLink::updateOrCreate(
            ['link_token' => $validated['link_token']],
            [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'external_system' => 'risment',
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'is_active' => true,
                'linked_at' => now(),
            ]
        );

        // Подтвердить связку в RISMENT через webhook (не блокирует сохранение)
        $this->confirmLinkInRisment($link, $user);

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

                // Уведомить RISMENT об отключении
                $this->disconnectLinkInRisment($link);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'RISMENT integration disconnected.',
        ]);
    }

    /**
     * Подтвердить связку в RISMENT через HTTP webhook
     * POST https://risment.uz/api/integration/sellermind/confirm
     */
    private function confirmLinkInRisment(IntegrationLink $link, $user): void
    {
        $baseUrl = config('services.risment.base_url', 'https://risment.uz');
        $url = rtrim($baseUrl, '/') . '/api/integration/sellermind/confirm';

        try {
            $response = Http::timeout(10)
                ->retry(2, 1000)
                ->post($url, [
                    'link_token' => $link->link_token,
                    'sellermind_user_id' => $user->id,
                    'sellermind_company_id' => $link->company_id,
                ]);

            if ($response->successful()) {
                Log::info('IntegrationLink: RISMENT confirm webhook success', [
                    'company_id' => $link->company_id,
                    'status' => $response->status(),
                ]);
            } else {
                Log::warning('IntegrationLink: RISMENT confirm webhook failed', [
                    'company_id' => $link->company_id,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('IntegrationLink: RISMENT confirm webhook error', [
                'company_id' => $link->company_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомить RISMENT об отключении через HTTP webhook
     * POST https://risment.uz/api/integration/sellermind/disconnect
     */
    private function disconnectLinkInRisment(IntegrationLink $link): void
    {
        $baseUrl = config('services.risment.base_url', 'https://risment.uz');
        $url = rtrim($baseUrl, '/') . '/api/integration/sellermind/disconnect';

        try {
            Http::timeout(10)
                ->retry(2, 1000)
                ->post($url, [
                    'link_token' => $link->link_token,
                ]);

            Log::info('IntegrationLink: RISMENT disconnect webhook sent', [
                'company_id' => $link->company_id,
            ]);
        } catch (\Exception $e) {
            Log::error('IntegrationLink: RISMENT disconnect webhook error', [
                'company_id' => $link->company_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
