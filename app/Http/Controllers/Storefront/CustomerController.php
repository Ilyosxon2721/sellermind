<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Traits\StorefrontHelpers;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Личный кабинет покупателя витрины — регистрация, вход, профиль, история заказов
 */
final class CustomerController extends Controller
{
    use ApiResponder, StorefrontHelpers;

    /**
     * Регистрация покупателя
     *
     * POST /store/{slug}/api/customer/register
     */
    public function register(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        // Проверяем уникальность телефона в рамках магазина
        $exists = StoreCustomer::where('store_id', $store->id)
            ->where('phone', $data['phone'])
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Покупатель с таким телефоном уже зарегистрирован',
                'phone_exists',
                'phone',
                422
            );
        }

        $customer = StoreCustomer::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'last_login_at' => now(),
        ]);

        // Сохраняем в сессию
        session()->put("store_customer_{$store->id}", $customer->id);

        return $this->successResponse(
            $customer->makeHidden('password_hash'),
            ['message' => 'Регистрация прошла успешно']
        )->setStatusCode(201);
    }

    /**
     * Вход покупателя по телефону + паролю
     *
     * POST /store/{slug}/api/customer/login
     */
    public function login(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $customer = StoreCustomer::where('store_id', $store->id)
            ->where('phone', $data['phone'])
            ->where('is_active', true)
            ->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password_hash)) {
            return $this->errorResponse(
                'Неверный телефон или пароль',
                'invalid_credentials',
                status: 401
            );
        }

        $customer->update(['last_login_at' => now()]);
        session()->put("store_customer_{$store->id}", $customer->id);

        return $this->successResponse(
            $customer->makeHidden('password_hash'),
            ['message' => 'Вход выполнен']
        );
    }

    /**
     * Выход
     *
     * POST /store/{slug}/api/customer/logout
     */
    public function logout(string $slug): JsonResponse
    {
        $store = $this->getPublishedStore($slug);
        session()->forget("store_customer_{$store->id}");

        return $this->successResponse(['message' => 'Выход выполнен']);
    }

    /**
     * Профиль текущего покупателя
     *
     * GET /store/{slug}/api/customer/profile
     */
    public function profile(string $slug): JsonResponse
    {
        $store = $this->getPublishedStore($slug);
        $customer = $this->getAuthenticatedCustomer($store->id);

        if (! $customer) {
            return $this->errorResponse('Необходимо авторизоваться', 'unauthorized', status: 401);
        }

        return $this->successResponse($customer->makeHidden('password_hash'));
    }

    /**
     * Обновить профиль
     *
     * PUT /store/{slug}/api/customer/profile
     */
    public function updateProfile(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);
        $customer = $this->getAuthenticatedCustomer($store->id);

        if (! $customer) {
            return $this->errorResponse('Необходимо авторизоваться', 'unauthorized', status: 401);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'default_city' => ['nullable', 'string', 'max:255'],
            'default_address' => ['nullable', 'string', 'max:500'],
        ]);

        $customer->update($data);

        return $this->successResponse($customer->fresh()->makeHidden('password_hash'));
    }

    /**
     * История заказов покупателя
     *
     * GET /store/{slug}/api/customer/orders
     */
    public function orders(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);
        $customer = $this->getAuthenticatedCustomer($store->id);

        if (! $customer) {
            return $this->errorResponse('Необходимо авторизоваться', 'unauthorized', status: 401);
        }

        $perPage = min((int) ($request->input('per_page', 10)), 50);

        $orders = StoreOrder::where('store_id', $store->id)
            ->where('store_customer_id', $customer->id)
            ->with('items')
            ->latest()
            ->paginate($perPage);

        return $this->successResponse($orders->items(), [
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
        ]);
    }

    /**
     * Детали конкретного заказа покупателя
     *
     * GET /store/{slug}/api/customer/orders/{orderNumber}
     */
    public function orderDetail(string $slug, string $orderNumber): JsonResponse
    {
        $store = $this->getPublishedStore($slug);
        $customer = $this->getAuthenticatedCustomer($store->id);

        if (! $customer) {
            return $this->errorResponse('Необходимо авторизоваться', 'unauthorized', status: 401);
        }

        $order = StoreOrder::where('store_id', $store->id)
            ->where('store_customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'deliveryMethod', 'paymentMethod'])
            ->firstOrFail();

        return $this->successResponse($order);
    }

    /**
     * Получить авторизованного покупателя из сессии
     */
    private function getAuthenticatedCustomer(int $storeId): ?StoreCustomer
    {
        $customerId = session()->get("store_customer_{$storeId}");

        if (! $customerId) {
            return null;
        }

        return StoreCustomer::where('id', $customerId)
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->first();
    }
}
