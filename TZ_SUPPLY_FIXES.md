# Техническое задание: Исправление работы с поставками FBS

## Дата создания: 2025-12-05
## Приоритет: Высокий

---

## 1. ПРОБЛЕМА: Созданная поставка исчезает после обновления страницы

### Описание проблемы
После создания поставки через UI, она отображается в списке. Но после обновления страницы (F5) созданная поставка исчезает из списка.

### Причина
Поставка создается только в локальной БД (таблица `supplies`) и не синхронизируется с Wildberries. При обновлении страницы фронтенд запрашивает список поставок, но фильтрует только те, которые имеют `external_supply_id` (ID из WB).

### Решение

**Backend (SupplyController.php):**

1. Изменить метод `open()` для возврата всех поставок (как локальных, так и синхронизированных с WB):
```php
public function open(Request $request): JsonResponse
{
    // ...существующая валидация...

    $supplies = Supply::query()
        ->whereHas('account', fn($q) => $q->where('company_id', $request->company_id))
        ->forAccount($request->marketplace_account_id)
        ->open()
        ->orderBy('created_at', 'desc')
        ->get();

    // Добавить маркер синхронизации с WB
    $supplies->each(function($supply) {
        $supply->is_synced_with_wb = !empty($supply->external_supply_id);
        $supply->needs_sync = empty($supply->external_supply_id) && $supply->account->marketplace === 'wb';
    });

    return response()->json(['supplies' => $supplies]);
}
```

2. Изменить scope `open` в модели Supply:
```php
public function scopeOpen($query)
{
    return $query->whereIn('status', [
        self::STATUS_DRAFT,
        self::STATUS_IN_ASSEMBLY
    ])->whereNull('closed_at');
}
```

**Frontend (orders.blade.php):**

Обновить отображение поставок, показывая статус синхронизации:
```html
<div class="flex items-center space-x-2">
    <span x-text="supply.name"></span>
    <span x-show="!supply.is_synced_with_wb"
          class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">
        Не синхронизировано
    </span>
</div>
```

---

## 2. ПРОБЛЕМА: Отсутствие синхронизации с Wildberries при создании поставки

### Описание проблемы
При создании поставки через UI создается только локальная запись в БД. Поставка не создается автоматически на платформе Wildberries через API.

### Решение

**Backend (SupplyController.php):**

1. Добавить автоматическую синхронизацию в метод `store()`:
```php
public function store(Request $request): JsonResponse
{
    // ...существующая валидация...

    DB::transaction(function () use ($validated, &$supply) {
        $supply = Supply::create([
            'marketplace_account_id' => $validated['marketplace_account_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => Supply::STATUS_DRAFT,
        ]);

        // Автоматическая синхронизация с WB
        $account = MarketplaceAccount::find($validated['marketplace_account_id']);

        if ($account->marketplace === 'wb') {
            try {
                $wbService = app(WildberriesOrderService::class);
                $wbSupply = $wbService->createSupply($account, $validated['name']);

                $supply->external_supply_id = $wbSupply['id'];
                $supply->metadata = [
                    'wb_created_at' => $wbSupply['createdAt'] ?? now(),
                    'wb_name' => $wbSupply['name'],
                ];
                $supply->save();

                Log::info("Supply synced with WB", [
                    'supply_id' => $supply->id,
                    'wb_supply_id' => $wbSupply['id']
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to sync supply with WB", [
                    'supply_id' => $supply->id,
                    'error' => $e->getMessage()
                ]);
                // Продолжаем работу, поставка создана локально
                // Можно синхронизировать позже вручную
            }
        }
    });

    return response()->json([
        'supply' => $supply->fresh()->load('account'),
        'message' => 'Поставка создана успешно.',
    ]);
}
```

2. Добавить endpoint для ручной синхронизации (если автоматическая не удалась):
```php
public function syncWithWb(Request $request, Supply $supply): JsonResponse
{
    if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
        return response()->json(['message' => 'Доступ запрещён.'], 403);
    }

    if ($supply->external_supply_id) {
        return response()->json(['message' => 'Поставка уже синхронизирована.'], 422);
    }

    if ($supply->account->marketplace !== 'wb') {
        return response()->json(['message' => 'Синхронизация доступна только для Wildberries.'], 422);
    }

    try {
        $wbService = app(WildberriesOrderService::class);
        $wbSupply = $wbService->createSupply($supply->account, $supply->name);

        $supply->external_supply_id = $wbSupply['id'];
        $supply->metadata = array_merge($supply->metadata ?? [], [
            'wb_created_at' => $wbSupply['createdAt'] ?? now(),
            'wb_name' => $wbSupply['name'],
        ]);
        $supply->save();

        // Если в поставке уже есть заказы, добавляем их в WB
        if ($supply->orders_count > 0) {
            $orderIds = MarketplaceOrder::where('supply_id', 'SUPPLY-' . $supply->id)
                ->pluck('wb_order_id')
                ->filter()
                ->toArray();

            if (!empty($orderIds)) {
                $wbService->addOrdersToSupply($supply->account, $wbSupply['id'], $orderIds);
            }

            // Обновляем supply_id заказов на external_supply_id
            MarketplaceOrder::where('supply_id', 'SUPPLY-' . $supply->id)
                ->update(['supply_id' => $wbSupply['id']]);
        }

        return response()->json([
            'supply' => $supply->fresh()->load('account'),
            'message' => 'Поставка синхронизирована с Wildberries.',
        ]);
    } catch (\Exception $e) {
        Log::error("Failed to sync supply with WB", [
            'supply_id' => $supply->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'message' => 'Ошибка синхронизации: ' . $e->getMessage()
        ], 500);
    }
}
```

**Маршрут (routes/api.php):**
```php
Route::post('supplies/{supply}/sync-wb', [SupplyController::class, 'syncWithWb']);
```

**Frontend:**

Добавить кнопку для ручной синхронизации несинхронизированных поставок:
```html
<button x-show="supply.needs_sync"
        @click="syncSupplyWithWb(supply.id)"
        class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs rounded-lg">
    Синхронизировать с WB
</button>
```

```javascript
async syncSupplyWithWb(supplyId) {
    if (!confirm('Создать поставку на Wildberries?')) {
        return;
    }

    try {
        const response = await axios.post(`/api/marketplace/supplies/${supplyId}/sync-wb`, {}, {
            headers: this.getAuthHeaders()
        });

        await this.loadOpenSupplies();
        alert('Поставка синхронизирована с Wildberries!');
    } catch (error) {
        console.error('Sync error:', error);
        alert(error.response?.data?.message || 'Ошибка синхронизации');
    }
}
```

---

## 3. ПРОБЛЕМА: UX панели "Управление поставками FBS"

### Описание проблемы
Панель "Управление поставками FBS" всегда отображается на вкладке "На сборке", занимая много места. Нужно оптимизировать UX:
- Если нет поставок: показывать полную панель с призывом создать первую поставку
- Если есть поставки: показывать только компактную кнопку "Создать поставку"

### Решение

**Frontend (orders.blade.php):**

Изменить структуру панели управления:

```html
<!-- Supply Management Panel -->
<div x-show="activeTab === 'in_assembly'" class="mb-6">
    <!-- Вариант 1: Нет активных поставок - показываем полную панель -->
    <div x-show="openSupplies.length === 0"
         class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900 mb-1 flex items-center space-x-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Управление поставками FBS</span>
                </h3>
                <p class="text-sm text-gray-600">Начните работу, создав первую поставку для отправки заказов на склад WB</p>
            </div>
        </div>

        <div class="bg-white rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Нет активных поставок</h4>
            <p class="text-gray-600 mb-4">Создайте поставку, чтобы начать добавлять заказы для отправки на склад WB</p>
            <button @click="openCreateSupplyModal()"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition inline-flex items-center space-x-2 font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Создать первую поставку</span>
            </button>
        </div>
    </div>

    <!-- Вариант 2: Есть активные поставки - показываем компактную кнопку -->
    <div x-show="openSupplies.length > 0" class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Активные поставки FBS</h3>
        <button @click="openCreateSupplyModal()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Создать поставку</span>
        </button>
    </div>

    <!-- Список активных поставок (если есть) -->
    <div x-show="openSupplies.length > 0" class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- ...существующий код карточек поставок... -->
    </div>
</div>
```

---

## 4. ПРОБЛЕМА: Поставки из Wildberries не отображаются в системе

### Описание проблемы
Поставки, созданные через API Wildberries или через личный кабинет WB, не отображаются в системе. Система показывает "Нет активных поставок", хотя на WB есть активные поставки.

### Причина
Отсутствует периодическая синхронизация поставок из Wildberries в локальную БД. Система создает поставки в WB, но не загружает существующие поставки из WB.

### Решение

**1. Создать Job для синхронизации поставок:**

Файл: `app/Jobs/SyncWildberriesSupplies.php`
```php
<?php

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Models\Supply;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncWildberriesSupplies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected MarketplaceAccount $account;
    protected bool $fullSync;

    public function __construct(MarketplaceAccount $account, bool $fullSync = false)
    {
        $this->account = $account;
        $this->fullSync = $fullSync;
    }

    public function handle(WildberriesOrderService $wbService): void
    {
        Log::info("Starting WB supplies sync", [
            'account_id' => $this->account->id,
            'full_sync' => $this->fullSync
        ]);

        try {
            $limit = 1000;
            $next = 0;
            $totalSynced = 0;

            do {
                $response = $wbService->getSupplies($this->account, $limit, $next);
                $supplies = $response['supplies'] ?? [];

                if (empty($supplies)) {
                    break;
                }

                foreach ($supplies as $wbSupply) {
                    $this->syncSupply($wbSupply);
                    $totalSynced++;
                }

                $next = $response['next'] ?? 0;
            } while ($next > 0);

            Log::info("WB supplies sync completed", [
                'account_id' => $this->account->id,
                'total_synced' => $totalSynced
            ]);
        } catch (\Exception $e) {
            Log::error("WB supplies sync failed", [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    protected function syncSupply(array $wbSupply): void
    {
        DB::transaction(function () use ($wbSupply) {
            $supply = Supply::firstOrNew([
                'marketplace_account_id' => $this->account->id,
                'external_supply_id' => $wbSupply['id'],
            ]);

            $supply->name = $wbSupply['name'];

            // Маппинг статусов WB -> наши статусы
            $supply->status = $this->mapWbStatus($wbSupply);

            $supply->metadata = array_merge($supply->metadata ?? [], [
                'wb_created_at' => $wbSupply['createdAt'] ?? null,
                'wb_closed_at' => $wbSupply['closedAt'] ?? null,
                'wb_scan_dt' => $wbSupply['scanDt'] ?? null,
                'is_large_cargo' => $wbSupply['isLargeCargo'] ?? false,
            ]);

            // Если поставка закрыта в WB
            if (!empty($wbSupply['closedAt'])) {
                $supply->closed_at = $wbSupply['closedAt'];
                $supply->status = Supply::STATUS_READY;
            }

            // Если поставка доставлена в WB
            if (!empty($wbSupply['scanDt'])) {
                $supply->delivered_at = $wbSupply['scanDt'];
                $supply->status = Supply::STATUS_DELIVERED;
            }

            $supply->save();

            // Синхронизация заказов в поставке
            if ($this->fullSync || $supply->wasRecentlyCreated) {
                $this->syncSupplyOrders($supply, $wbSupply['id']);
            }
        });
    }

    protected function mapWbStatus(array $wbSupply): string
    {
        // Если есть дата сканирования (доставки)
        if (!empty($wbSupply['scanDt'])) {
            return Supply::STATUS_DELIVERED;
        }

        // Если закрыта
        if (!empty($wbSupply['closedAt'])) {
            return Supply::STATUS_READY;
        }

        // Если есть заказы - на сборке, иначе черновик
        return ($wbSupply['ordersCount'] ?? 0) > 0
            ? Supply::STATUS_IN_ASSEMBLY
            : Supply::STATUS_DRAFT;
    }

    protected function syncSupplyOrders(Supply $supply, string $wbSupplyId): void
    {
        try {
            $wbService = app(WildberriesOrderService::class);
            $orders = $wbService->getSupplyOrders($this->account, $wbSupplyId);

            foreach ($orders as $wbOrder) {
                // Находим заказ в локальной БД по wb_order_id
                MarketplaceOrder::where('marketplace_account_id', $this->account->id)
                    ->where('wb_order_id', $wbOrder['id'])
                    ->update(['supply_id' => $wbSupplyId]);
            }

            // Пересчитываем статистику поставки
            $supply->recalculateStats();
        } catch (\Exception $e) {
            Log::warning("Failed to sync supply orders", [
                'supply_id' => $supply->id,
                'wb_supply_id' => $wbSupplyId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

**2. Создать команду для запуска синхронизации:**

Файл: `app/Console/Commands/SyncWildberriesSuppliesCommand.php`
```php
<?php

namespace App\Console\Commands;

use App\Jobs\SyncWildberriesSupplies;
use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;

class SyncWildberriesSuppliesCommand extends Command
{
    protected $signature = 'wb:sync-supplies
                            {--account= : ID аккаунта маркетплейса}
                            {--full : Полная синхронизация с заказами}';

    protected $description = 'Синхронизация поставок Wildberries';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $fullSync = $this->option('full');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'wb')->get()
            : MarketplaceAccount::where('marketplace', 'wb')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->error('Не найдены активные аккаунты Wildberries');
            return self::FAILURE;
        }

        $this->info("Запуск синхронизации для {$accounts->count()} аккаунта(ов)...");

        foreach ($accounts as $account) {
            $this->info("Синхронизация аккаунта: {$account->name} (ID: {$account->id})");
            SyncWildberriesSupplies::dispatch($account, $fullSync);
        }

        $this->info('Задачи синхронизации добавлены в очередь');
        return self::SUCCESS;
    }
}
```

**3. Добавить в планировщик задач:**

Файл: `app/Console/Kernel.php`
```php
protected function schedule(Schedule $schedule): void
{
    // Синхронизация поставок каждые 30 минут
    $schedule->command('wb:sync-supplies')
        ->everyThirtyMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
```

**4. Добавить endpoint для ручной синхронизации:**

`app/Http/Controllers/Api/SupplyController.php`:
```php
public function syncFromWb(Request $request): JsonResponse
{
    $request->validate([
        'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
        'company_id' => ['required', 'exists:companies,id'],
    ]);

    if (!$request->user()->hasCompanyAccess($request->company_id)) {
        return response()->json(['message' => 'Доступ запрещён.'], 403);
    }

    $account = MarketplaceAccount::find($request->marketplace_account_id);

    if ($account->marketplace !== 'wb') {
        return response()->json(['message' => 'Синхронизация доступна только для Wildberries.'], 422);
    }

    SyncWildberriesSupplies::dispatch($account, false);

    return response()->json([
        'message' => 'Синхронизация запущена. Обновите страницу через несколько секунд.',
    ]);
}
```

Маршрут:
```php
Route::post('supplies/sync-from-wb', [SupplyController::class, 'syncFromWb']);
```

**Frontend:**

Добавить кнопку синхронизации:
```html
<button @click="syncSuppliesFromWb()"
        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center space-x-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
    </svg>
    <span>Синхронизировать с WB</span>
</button>
```

```javascript
async syncSuppliesFromWb() {
    try {
        const response = await axios.post('/api/marketplace/supplies/sync-from-wb', {
            marketplace_account_id: {{ $accountId }},
            company_id: this.$store.auth.currentCompany.id
        }, {
            headers: this.getAuthHeaders()
        });

        alert(response.data.message);

        // Перезагружаем через 3 секунды
        setTimeout(() => {
            this.loadOpenSupplies();
        }, 3000);
    } catch (error) {
        console.error('Sync error:', error);
        alert('Ошибка синхронизации');
    }
}
```

---

## 5. ПРОБЛЕМА: Невозможно удалить пустую поставку

### Описание проблемы
Нет функционала для удаления пустых поставок (с 0 заказов). Пустые поставки накапливаются и засоряют интерфейс.

### Требования
- Кнопка "Удалить" должна отображаться только для пустых поставок (orders_count = 0)
- Удаление должно происходить как из локальной БД, так и из Wildberries (через API)
- Если поставка не синхронизирована с WB - удалять только из локальной БД

### Решение

**Backend (SupplyController.php):**

Улучшить метод `destroy()`:
```php
public function destroy(Request $request, Supply $supply): JsonResponse
{
    if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
        return response()->json(['message' => 'Доступ запрещён.'], 403);
    }

    // Проверяем, что поставка пустая
    if ($supply->orders_count > 0) {
        return response()->json([
            'message' => 'Невозможно удалить поставку с заказами. Сначала удалите все заказы из поставки.'
        ], 422);
    }

    DB::transaction(function () use ($supply) {
        // Если поставка синхронизирована с WB - отменяем её там
        if ($supply->external_supply_id && $supply->account->marketplace === 'wb') {
            try {
                $wbService = app(WildberriesOrderService::class);
                $wbService->cancelSupply($supply->account, $supply->external_supply_id);

                Log::info("Supply cancelled in WB", [
                    'supply_id' => $supply->id,
                    'wb_supply_id' => $supply->external_supply_id
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to cancel supply in WB", [
                    'supply_id' => $supply->id,
                    'wb_supply_id' => $supply->external_supply_id,
                    'error' => $e->getMessage()
                ]);

                // Продолжаем удаление из локальной БД даже если не удалось отменить в WB
                // Пользователь может удалить вручную через ЛК WB
            }
        }

        // Удаляем из локальной БД
        $supply->delete();
    });

    return response()->json([
        'message' => 'Поставка удалена успешно.',
    ]);
}
```

**Frontend (orders.blade.php):**

Добавить кнопку удаления в карточку поставки:
```html
<div class="flex space-x-2">
    <button @click="viewSupplyOrders(supply)"
            class="flex-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs rounded-lg transition">
        Просмотр
    </button>

    <!-- Кнопка удаления для пустых поставок -->
    <button x-show="(supply.orders_count || 0) === 0"
            @click="deleteSupply(supply.id)"
            class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs rounded-lg transition"
            title="Удалить пустую поставку">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
    </button>

    <!-- Кнопка закрытия для непустых поставок -->
    <button x-show="(supply.orders_count || 0) > 0"
            @click="closeSupplyFromPanel(supply.id)"
            class="flex-1 px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs rounded-lg transition">
        Закрыть
    </button>
</div>
```

Добавить метод удаления:
```javascript
async deleteSupply(supplyId) {
    if (!confirm('Удалить пустую поставку? Это действие нельзя отменить.')) {
        return;
    }

    try {
        await axios.delete(`/api/marketplace/supplies/${supplyId}`, {
            headers: this.getAuthHeaders()
        });

        await this.loadOpenSupplies();
        alert('Поставка удалена');
    } catch (error) {
        console.error('Error deleting supply:', error);
        alert(error.response?.data?.message || 'Ошибка при удалении поставки');
    }
}
```

---

## 6. ПРОБЛЕМА: Отсутствует функционал перемещения заказов между поставками

### Описание проблемы
Невозможно переместить заказ из одной поставки в другую. Нужно сначала удалить заказ из одной поставки, затем добавить в другую.

### Требование
Реализовать функцию перемещения заказа между поставками одним действием, с синхронизацией через API Wildberries.

### Решение

**Backend (SupplyController.php):**

Добавить новый метод `moveOrder`:
```php
public function moveOrder(Request $request): JsonResponse
{
    $validated = $request->validate([
        'order_id' => ['required', 'exists:marketplace_orders,id'],
        'from_supply_id' => ['required', 'exists:supplies,id'],
        'to_supply_id' => ['required', 'exists:supplies,id'],
    ]);

    $order = MarketplaceOrder::findOrFail($validated['order_id']);
    $fromSupply = Supply::findOrFail($validated['from_supply_id']);
    $toSupply = Supply::findOrFail($validated['to_supply_id']);

    // Проверка доступа
    if (!$request->user()->hasCompanyAccess($fromSupply->account->company_id) ||
        !$request->user()->hasCompanyAccess($toSupply->account->company_id)) {
        return response()->json(['message' => 'Доступ запрещён.'], 403);
    }

    // Проверка, что поставки принадлежат одному аккаунту
    if ($fromSupply->marketplace_account_id !== $toSupply->marketplace_account_id) {
        return response()->json([
            'message' => 'Поставки должны принадлежать одному аккаунту маркетплейса.'
        ], 422);
    }

    // Проверка, что заказ действительно в исходной поставке
    $currentSupplyId = $order->supply_id;
    if ($currentSupplyId !== $fromSupply->external_supply_id &&
        $currentSupplyId !== 'SUPPLY-' . $fromSupply->id) {
        return response()->json([
            'message' => 'Заказ не находится в указанной поставке.'
        ], 422);
    }

    // Проверка, что целевая поставка открыта для добавления заказов
    if (!$toSupply->canAddOrders()) {
        return response()->json([
            'message' => 'Целевая поставка закрыта для добавления заказов.'
        ], 422);
    }

    DB::transaction(function () use ($order, $fromSupply, $toSupply) {
        $account = $fromSupply->account;

        // Если обе поставки синхронизированы с WB
        if ($fromSupply->external_supply_id && $toSupply->external_supply_id &&
            $account->marketplace === 'wb' && $order->wb_order_id) {

            try {
                $wbService = app(WildberriesOrderService::class);

                // Удаляем из старой поставки в WB
                $wbService->removeOrderFromSupply(
                    $account,
                    $fromSupply->external_supply_id,
                    $order->wb_order_id
                );

                // Добавляем в новую поставку в WB
                $wbService->addOrdersToSupply(
                    $account,
                    $toSupply->external_supply_id,
                    [$order->wb_order_id]
                );

                // Обновляем supply_id на external_supply_id целевой поставки
                $order->supply_id = $toSupply->external_supply_id;

                Log::info("Order moved between WB supplies", [
                    'order_id' => $order->id,
                    'from_supply' => $fromSupply->external_supply_id,
                    'to_supply' => $toSupply->external_supply_id
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to move order in WB", [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);

                throw new \Exception('Ошибка перемещения заказа в Wildberries: ' . $e->getMessage());
            }
        } else {
            // Локальное перемещение (одна или обе поставки не синхронизированы)
            $newSupplyId = $toSupply->external_supply_id ?: 'SUPPLY-' . $toSupply->id;
            $order->supply_id = $newSupplyId;
        }

        $order->save();

        // Пересчитываем статистику обеих поставок
        $fromSupply->recalculateStats();
        $toSupply->recalculateStats();
    });

    return response()->json([
        'order' => $order->fresh(),
        'from_supply' => $fromSupply->fresh(),
        'to_supply' => $toSupply->fresh(),
        'message' => 'Заказ перемещён в другую поставку.',
    ]);
}
```

Маршрут:
```php
Route::post('supplies/move-order', [SupplyController::class, 'moveOrder']);
```

**Frontend:**

Добавить UI для перемещения заказа в модальном окне просмотра заказа:
```html
<!-- В модальном окне деталей заказа -->
<div x-show="selectedOrder?.supply_id" class="mt-4 p-4 bg-blue-50 rounded-lg">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-700">Поставка</p>
            <p class="text-sm text-gray-900" x-text="selectedOrder?.supply_id"></p>
        </div>
        <button @click="showMoveOrderModal = true"
                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
            Переместить
        </button>
    </div>
</div>
```

Модальное окно для выбора целевой поставки:
```html
<!-- Move Order Modal -->
<div x-show="showMoveOrderModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showMoveOrderModal = false"></div>

        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 z-50">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Переместить заказ в другую поставку
            </h3>

            <div class="space-y-2 max-h-96 overflow-y-auto mb-4">
                <template x-for="supply in openSupplies.filter(s => s.id !== currentSupplyId)" :key="supply.id">
                    <div @click="targetSupplyId = supply.id"
                         :class="targetSupplyId === supply.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                         class="p-3 border-2 rounded-lg cursor-pointer hover:border-gray-300 transition">
                        <p class="font-medium text-gray-900" x-text="supply.name"></p>
                        <p class="text-sm text-gray-600">
                            Заказов: <span x-text="supply.orders_count || 0"></span>
                        </p>
                    </div>
                </template>
            </div>

            <div class="flex justify-end space-x-3">
                <button @click="showMoveOrderModal = false"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Отмена
                </button>
                <button @click="moveOrderToSupply()"
                        :disabled="!targetSupplyId"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    Переместить
                </button>
            </div>
        </div>
    </div>
</div>
```

JavaScript методы:
```javascript
showMoveOrderModal: false,
targetSupplyId: null,
currentSupplyId: null,

async moveOrderToSupply() {
    if (!this.targetSupplyId || !this.selectedOrder) {
        return;
    }

    try {
        const response = await axios.post('/api/marketplace/supplies/move-order', {
            order_id: this.selectedOrder.id,
            from_supply_id: this.currentSupplyId,
            to_supply_id: this.targetSupplyId
        }, {
            headers: this.getAuthHeaders()
        });

        this.showMoveOrderModal = false;
        this.showOrderModal = false;
        this.targetSupplyId = null;
        this.currentSupplyId = null;

        await this.loadOpenSupplies();
        await this.loadOrders();

        alert('Заказ перемещён в другую поставку');
    } catch (error) {
        console.error('Move order error:', error);
        alert(error.response?.data?.message || 'Ошибка при перемещении заказа');
    }
}
```

---

## 7. ПРОБЛЕМА: API не показывает последние данные (проблема с фильтрацией по датам)

### Описание проблемы
Заказы, которые поступили в течение дня, не отображаются в системе. Чтобы увидеть их, нужно установить завтрашнюю дату в фильтре. Это указывает на проблему с временными зонами или с логикой фильтрации по датам.

### Возможные причины
1. **Временная зона**: WB возвращает время в UTC, но система может фильтровать в локальной временной зоне
2. **Неправильная логика фильтрации**: фильтр `dateTo` может использовать время 00:00:00, исключая заказы текущего дня
3. **Кеширование**: данные могут кешироваться и не обновляться

### Решение

**1. Проверить и исправить фильтрацию по датам в MarketplaceOrderController:**

```php
public function index(Request $request): JsonResponse
{
    $validated = $request->validate([
        'company_id' => ['required', 'exists:companies,id'],
        'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
        'status' => ['nullable', 'string'],
        'from' => ['nullable', 'date'],
        'to' => ['nullable', 'date'],
        'search' => ['nullable', 'string'],
        'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
    ]);

    // ...проверки доступа...

    $query = MarketplaceOrder::query()
        ->where('marketplace_account_id', $validated['marketplace_account_id'])
        ->with(['account']);

    // Фильтр по статусу
    if (!empty($validated['status'])) {
        $query->where('status', $validated['status']);
    }

    // ИСПРАВЛЕНИЕ: Фильтр по дате (включая весь день "to")
    if (!empty($validated['from'])) {
        // Начало дня "from" в UTC
        $fromDate = Carbon::parse($validated['from'])->startOfDay()->utc();
        $query->where('ordered_at', '>=', $fromDate);
    }

    if (!empty($validated['to'])) {
        // ВАЖНО: Конец дня "to" в UTC (23:59:59.999999)
        $toDate = Carbon::parse($validated['to'])->endOfDay()->utc();
        $query->where('ordered_at', '<=', $toDate);
    }

    // Если даты не указаны, показываем заказы за последние 30 дней
    if (empty($validated['from']) && empty($validated['to'])) {
        $query->where('ordered_at', '>=', now()->subDays(30));
    }

    // Фильтр по поиску
    if (!empty($validated['search'])) {
        $query->where(function ($q) use ($validated) {
            $q->where('external_order_id', 'like', '%' . $validated['search'] . '%')
              ->orWhere('wb_article', 'like', '%' . $validated['search'] . '%')
              ->orWhere('wb_nm_id', 'like', '%' . $validated['search'] . '%');
        });
    }

    // Сортировка по дате заказа (новые первые)
    $query->orderBy('ordered_at', 'desc');

    // Лимит
    $limit = $validated['limit'] ?? 100;
    $orders = $query->limit($limit)->get();

    return response()->json([
        'orders' => $orders,
        'meta' => [
            'total' => $orders->count(),
            'filters' => [
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'status' => $validated['status'] ?? null,
            ],
            'timezone' => 'UTC',
        ],
    ]);
}
```

**2. Добавить информацию о временной зоне на фронтенде:**

```javascript
// В методе loadOrders()
async loadOrders() {
    this.loading = true;
    try {
        const params = {
            company_id: this.$store.auth.currentCompany.id,
            marketplace_account_id: {{ $accountId }},
            limit: 500
        };

        if (this.dateFrom) {
            // Преобразуем локальную дату в UTC для запроса
            params.from = this.dateFrom;
        }

        if (this.dateTo) {
            // Преобразуем локальную дату в UTC для запроса
            params.to = this.dateTo;
        }

        // ... остальной код ...

        console.log('Loading orders with params:', params);
        console.log('Current timezone:', Intl.DateTimeFormat().resolvedOptions().timeZone);
    } catch (error) {
        // ...
    }
}
```

**3. Улучшить компонент выбора даты:**

```html
<!-- Фильтр по датам -->
<div class="flex items-center space-x-2">
    <input type="date"
           x-model="dateFrom"
           :max="dateTo || new Date().toISOString().split('T')[0]"
           class="px-3 py-2 border border-gray-300 rounded-lg">
    <span class="text-gray-500">—</span>
    <input type="date"
           x-model="dateTo"
           :min="dateFrom"
           :max="new Date().toISOString().split('T')[0]"
           class="px-3 py-2 border border-gray-300 rounded-lg">
    <button @click="loadOrders(); loadStats()"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Применить
    </button>
    <button @click="dateFrom = ''; dateTo = ''; loadOrders(); loadStats()"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
        Сбросить
    </button>
</div>

<!-- Hint о временной зоне -->
<p class="text-xs text-gray-500 mt-1">
    * Время заказов указано в UTC. Ваша временная зона: <span x-text="Intl.DateTimeFormat().resolvedOptions().timeZone"></span>
</p>
```

**4. Добавить кнопку "Сегодня" для быстрого доступа:**

```html
<div class="flex items-center space-x-2 mb-2">
    <button @click="setToday()"
            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
        Сегодня
    </button>
    <button @click="setYesterday()"
            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
        Вчера
    </button>
    <button @click="setLastWeek()"
            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
        Последние 7 дней
    </button>
    <button @click="setLastMonth()"
            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
        Последние 30 дней
    </button>
</div>
```

```javascript
setToday() {
    const today = new Date().toISOString().split('T')[0];
    this.dateFrom = today;
    this.dateTo = today;
    this.loadOrders();
    this.loadStats();
},

setYesterday() {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayStr = yesterday.toISOString().split('T')[0];
    this.dateFrom = yesterdayStr;
    this.dateTo = yesterdayStr;
    this.loadOrders();
    this.loadStats();
},

setLastWeek() {
    const today = new Date().toISOString().split('T')[0];
    const weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 7);
    this.dateFrom = weekAgo.toISOString().split('T')[0];
    this.dateTo = today;
    this.loadOrders();
    this.loadStats();
},

setLastMonth() {
    const today = new Date().toISOString().split('T')[0];
    const monthAgo = new Date();
    monthAgo.setDate(monthAgo.getDate() - 30);
    this.dateFrom = monthAgo.toISOString().split('T')[0];
    this.dateTo = today;
    this.loadOrders();
    this.loadStats();
}
```

**5. Отключить кеширование для API заказов:**

В контроллере добавить заголовки:
```php
return response()->json([
    'orders' => $orders,
    // ...
])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
  ->header('Pragma', 'no-cache')
  ->header('Expires', '0');
```

---

## ПРИОРИТЕТЫ РЕАЛИЗАЦИИ

### Фаза 1 (Критическая) - Срочно:
1. ✅ **Проблема #1**: Поставка исчезает после обновления
2. ✅ **Проблема #7**: API не показывает последние данные

### Фаза 2 (Высокий приоритет) - В течение 1-2 дней:
3. ✅ **Проблема #2**: Автоматическая синхронизация с WB при создании
4. ✅ **Проблема #4**: Синхронизация поставок из WB
5. ✅ **Проблема #5**: Удаление пустых поставок

### Фаза 3 (Средний приоритет) - В течение недели:
6. ✅ **Проблема #3**: UX оптимизация панели управления
7. ✅ **Проблема #6**: Перемещение заказов между поставками

---

## ТЕСТИРОВАНИЕ

После реализации каждой фазы необходимо протестировать:

### Тест-кейсы Фаза 1:
- [ ] Создать поставку через UI
- [ ] Обновить страницу (F5)
- [ ] Убедиться, что поставка отображается
- [ ] Установить фильтр "Сегодня"
- [ ] Убедиться, что отображаются заказы текущего дня

### Тест-кейсы Фаза 2:
- [ ] Создать поставку через UI
- [ ] Проверить, что она создалась в WB (через API или ЛК WB)
- [ ] Создать поставку через ЛК WB
- [ ] Нажать "Синхронизировать с WB"
- [ ] Убедиться, что поставка из WB появилась в системе
- [ ] Создать пустую поставку
- [ ] Удалить её через кнопку "Удалить"
- [ ] Проверить, что она удалилась из системы и из WB

### Тест-кейсы Фаза 3:
- [ ] Открыть вкладку "На сборке" без поставок
- [ ] Убедиться, что отображается полная панель
- [ ] Создать поставку
- [ ] Убедиться, что панель стала компактной
- [ ] Добавить заказ в поставку A
- [ ] Переместить заказ в поставку B
- [ ] Проверить, что заказ переместился в обеих системах (локальная БД и WB)

---

## ДОПОЛНИТЕЛЬНЫЕ УЛУЧШЕНИЯ (опционально)

### 1. Уведомления о статусе синхронизации
Добавить toast-уведомления для:
- Успешной синхронизации с WB
- Ошибок синхронизации
- Завершения фоновой синхронизации поставок

### 2. Индикатор загрузки
Добавить spinner/skeleton для:
- Загрузки списка поставок
- Синхронизации с WB
- Перемещения заказов

### 3. Логирование и мониторинг
- Логировать все операции с поставками
- Создать dashboard для отслеживания ошибок синхронизации
- Добавить Sentry/Bugsnag для отслеживания ошибок в production

### 4. Оптимизация производительности
- Добавить индексы в БД для быстрой фильтрации
- Кешировать список поставок (с инвалидацией при изменениях)
- Использовать lazy loading для больших списков заказов

---

## РИСКИ И ОГРАНИЧЕНИЯ

### Риски:
1. **Rate Limiting WB API**: Wildberries может ограничивать количество запросов
   - Решение: Добавить rate limiting на уровне приложения, использовать очереди

2. **Конфликты при одновременном редактировании**: Пользователь может изменить поставку в WB и в системе одновременно
   - Решение: Добавить проверку версий, показывать предупреждение о конфликте

3. **Потеря данных при ошибке синхронизации**: Если запрос к WB API упал, данные могут рассинхронизироваться
   - Решение: Использовать транзакции, повторные попытки, логирование

### Ограничения WB API:
- Максимум 1000 поставок за один запрос
- Нельзя удалить поставку через API (только отменить)
- Некоторые операции доступны только для определённых статусов поставок

---

## ИТОГОВАЯ ОЦЕНКА

**Общее время разработки**: 3-5 рабочих дней

- Фаза 1: 4-6 часов
- Фаза 2: 8-12 часов
- Фаза 3: 6-8 часов
- Тестирование и отладка: 4-6 часов

**Сложность**: Средняя-Высокая

**Зависимости**:
- Wildberries API должен быть доступен
- У пользователя должны быть валидные токены WB
- Laravel Queue должен быть настроен для фоновых задач

---

## КОНТРОЛЬНЫЙ СПИСОК ГОТОВНОСТИ

### Backend:
- [ ] Обновлён `SupplyController::open()` для показа всех поставок
- [ ] Обновлён `SupplyController::store()` для автосинхронизации с WB
- [ ] Добавлен `SupplyController::syncWithWb()` для ручной синхронизации
- [ ] Добавлен `SupplyController::syncFromWb()` для загрузки поставок из WB
- [ ] Улучшен `SupplyController::destroy()` для удаления из WB и БД
- [ ] Добавлен `SupplyController::moveOrder()` для перемещения заказов
- [ ] Создан `SyncWildberriesSupplies` Job
- [ ] Создана команда `wb:sync-supplies`
- [ ] Добавлена задача в Kernel для автосинхронизации
- [ ] Исправлена фильтрация по датам в `MarketplaceOrderController`
- [ ] Добавлены все необходимые маршруты

### Frontend:
- [ ] Обновлена панель управления поставками (адаптивная)
- [ ] Добавлена кнопка синхронизации с WB
- [ ] Добавлена кнопка удаления пустых поставок
- [ ] Добавлен UI для перемещения заказов
- [ ] Добавлены быстрые фильтры по датам (Сегодня, Вчера, и т.д.)
- [ ] Добавлены индикаторы статуса синхронизации
- [ ] Обновлены методы JavaScript
- [ ] Добавлено логирование в консоль для отладки

### Тестирование:
- [ ] Все тест-кейсы Фазы 1 пройдены
- [ ] Все тест-кейсы Фазы 2 пройдены
- [ ] Все тест-кейсы Фазы 3 пройдены
- [ ] Проверена работа на production данных
- [ ] Проверена работа при ошибках WB API

---

**Дата составления**: 2025-12-05
**Автор**: AI Assistant (Claude)
**Версия**: 1.0
