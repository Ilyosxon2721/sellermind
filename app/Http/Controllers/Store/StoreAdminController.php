<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление магазинами компании (CRUD)
 */
final class StoreAdminController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Список магазинов текущей компании
     */
    public function index(): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $stores = Store::where('company_id', $companyId)
            ->withCount(['products', 'orders'])
            ->get();

        return $this->successResponse($stores);
    }

    /**
     * Создать новый магазин
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:10'],
            'instagram' => ['nullable', 'string'],
            'telegram' => ['nullable', 'string'],
        ]);

        $data['company_id'] = $companyId;
        $store = Store::create($data);

        return $this->successResponse($store->load('theme'))->setStatusCode(201);
    }

    /**
     * Показать магазин со всеми связями
     */
    public function show(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $store = Store::where('company_id', $companyId)
            ->with(['theme', 'banners', 'categories', 'deliveryMethods', 'paymentMethods', 'pages'])
            ->withCount(['products', 'orders'])
            ->findOrFail($id);

        return $this->successResponse($store);
    }

    /**
     * Обновить настройки магазина
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $store = Store::where('company_id', $companyId)->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:500'],
            'favicon' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'working_hours' => ['nullable', 'array'],
            'instagram' => ['nullable', 'string'],
            'telegram' => ['nullable', 'string'],
            'facebook' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:10'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $store->update($data);

        return $this->successResponse($store->fresh('theme'));
    }

    /**
     * Удалить магазин
     */
    public function destroy(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $store = Store::where('company_id', $companyId)->findOrFail($id);
        $store->delete();

        return $this->successResponse(['message' => 'Магазин удалён']);
    }
}
