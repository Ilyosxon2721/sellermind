<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLink;
use App\Models\Risment\RismentClient;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class RismentClientController extends Controller
{
    /**
     * Список всех RISMENT клиентов компании
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->companies()->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company found.'], 422);
        }

        $clients = RismentClient::where('company_id', $company->id)
            ->with(['activeLink'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RismentClient $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'description' => $client->description,
                'contact_person' => $client->contact_person,
                'contact_phone' => $client->contact_phone,
                'contact_email' => $client->contact_email,
                'risment_account_id' => $client->risment_account_id,
                'is_active' => $client->is_active,
                'is_linked' => $client->activeLink !== null,
                'linked_at' => $client->activeLink?->linked_at?->toIso8601String(),
                'warehouse_id' => $client->activeLink?->warehouse_id,
                'created_at' => $client->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    /**
     * Создать нового RISMENT клиента
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'risment_account_id' => 'nullable|string|max:100',
        ]);

        $company = $request->user()->companies()->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company found.'], 422);
        }

        $client = RismentClient::create([
            'company_id' => $company->id,
            ...$validated,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
            ],
            'message' => 'Клиент RISMENT создан.',
        ], 201);
    }

    /**
     * Обновить RISMENT клиента
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companies()->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company found.'], 422);
        }

        $client = RismentClient::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'risment_account_id' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $client->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'is_active' => $client->is_active,
            ],
            'message' => 'Клиент RISMENT обновлён.',
        ]);
    }

    /**
     * Удалить (деактивировать) RISMENT клиента
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companies()->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company found.'], 422);
        }

        $client = RismentClient::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        // Деактивируем клиента и все его связки
        $client->update(['is_active' => false]);

        IntegrationLink::where('risment_client_id', $client->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Клиент RISMENT деактивирован.',
        ]);
    }

    /**
     * Привязать RISMENT-токен к клиенту
     */
    public function link(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'link_token' => 'required|string|min:8|max:128',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $user = $request->user();
        $company = $user->companies()->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company found.'], 422);
        }

        $client = RismentClient::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        // Деактивировать старые связки этого клиента
        IntegrationLink::where('risment_client_id', $client->id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Создать новую связку
        $link = IntegrationLink::updateOrCreate(
            ['link_token' => $validated['link_token']],
            [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'risment_client_id' => $client->id,
                'external_system' => 'risment',
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'is_active' => true,
                'linked_at' => now(),
            ]
        );

        // Подтвердить связку в RISMENT
        $this->confirmLinkInRisment($link, $user, $client);

        return response()->json([
            'success' => true,
            'message' => 'RISMENT интеграция привязана к клиенту.',
            'data' => [
                'id' => $link->id,
                'client_id' => $client->id,
                'linked_at' => $link->linked_at->toIso8601String(),
                'warehouse_id' => $link->warehouse_id,
            ],
        ]);
    }

    /**
     * Обновить настройки связки клиента (склад и др.)
     */
    public function updateLink(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $company = $request->user()->companies()->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company found.'], 422);
        }

        $client = RismentClient::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $link = IntegrationLink::where('risment_client_id', $client->id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'Нет активной связки для этого клиента.',
            ], 404);
        }

        $link->update(['warehouse_id' => $validated['warehouse_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Настройки связки обновлены.',
            'data' => ['warehouse_id' => $link->warehouse_id],
        ]);
    }

    /**
     * Отвязать клиента от RISMENT
     */
    public function unlink(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companies()->first();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company found.'], 422);
        }

        $client = RismentClient::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $link = IntegrationLink::where('risment_client_id', $client->id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->first();

        if ($link) {
            $link->update(['is_active' => false]);
            $this->disconnectLinkInRisment($link);
        }

        return response()->json([
            'success' => true,
            'message' => 'RISMENT интеграция отключена для клиента.',
        ]);
    }

    /**
     * Подтвердить связку в RISMENT через HTTP webhook
     */
    private function confirmLinkInRisment(IntegrationLink $link, $user, RismentClient $client): void
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
                    'sellermind_client_id' => $client->id,
                    'client_name' => $client->name,
                ]);

            if ($response->successful()) {
                Log::info('RismentClient: confirm webhook success', [
                    'client_id' => $client->id,
                    'company_id' => $link->company_id,
                ]);
            } else {
                Log::warning('RismentClient: confirm webhook failed', [
                    'client_id' => $client->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RismentClient: confirm webhook error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомить RISMENT об отключении
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
        } catch (\Exception $e) {
            Log::error('RismentClient: disconnect webhook error', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
