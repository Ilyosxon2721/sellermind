# 📊 ТЗ: Модуль расчёта цен для маркетплейсов — SellerMind

## 1. Обзор модуля

### 1.1 Цель
Создать модуль автоматического расчёта оптимальной цены продажи товара на каждом маркетплейсе с учётом:
- Себестоимости товара
- Комиссий маркетплейса (по категориям)
- Логистики и хранения
- Эквайринга
- Желаемой маржи продавца
- Налогов

### 1.2 Маркетплейсы
| Маркетплейс | Регион | Валюта |
|-------------|--------|--------|
| Wildberries | Россия | RUB |
| Ozon | Россия | RUB |
| Yandex Market | Россия | RUB |
| Uzum Market | Узбекистан | UZS |

### 1.3 Ключевые возможности
- Калькулятор цены с учётом всех расходов
- Обратный расчёт: "какую цену поставить, чтобы получить X маржи?"
- Сравнение прибыльности между маркетплейсами
- Автообновление комиссий из API маркетплейсов
- Массовый перерасчёт цен при изменении тарифов
- Уведомления при изменении комиссий

---

## 2. Структура комиссий маркетплейсов

### 2.1 Wildberries (Россия)

#### Комиссия за продажу (кВВ)
| Категория | FBO/FBS % (с 31.10.2025) |
|-----------|--------------------------|
| Одежда | 34.5% |
| Обувь | 34.5% |
| Электроника | 27.5% |
| Бытовая техника | 22.5% |
| Товары для дома | 24.5% |
| Косметика | 29.5% |
| Продукты | 19.5% |
| Детские товары | 24.5% |
| Спорт | 26.5% |
| Автотовары | 26.5% |
| Книги | 19.5% |
| Зоотовары | 21.5% |

#### Дополнительные расходы
| Статья расходов | Стоимость |
|-----------------|-----------|
| Логистика до ПВЗ | от 40 ₽ (зависит от объёма и региона) |
| Хранение (FBO) | от 0.07 ₽/литр/день |
| Обратная логистика | 50 ₽ |
| Эквайринг | 1.5-2.5% (включён в комиссию) |
| СПП (скидка постоянного покупателя) | 0-30% (компенсирует WB) |
| Штрафы | от 100 ₽ за нарушения |

#### Формула расчёта прибыли WB:
```
Прибыль = Цена × (1 - кВВ%) - Логистика - Хранение - Себестоимость
```

---

### 2.2 Ozon (Россия)

#### Комиссия за продажу
| Категория | FBO % | FBS % |
|-----------|-------|-------|
| Одежда/Fashion | 22-28% | 26-32% |
| Электроника | 10-15% | 14-19% |
| Бытовая техника | 12-18% | 16-22% |
| Товары для дома | 16-20% | 20-24% |
| Косметика | 18-24% | 22-28% |
| Продукты | 10-14% | 14-18% |
| Детские товары | 14-18% | 18-22% |
| Зоотовары | 14-16% | 18-20% |
| Книги | 14% | 18% |

#### Специальные тарифы для дешёвых товаров
| Цена товара | Комиссия |
|-------------|----------|
| До 100 ₽ | 14% |
| 101-300 ₽ | 20% |
| Более 300 ₽ | По категории |

#### Дополнительные расходы
| Статья расходов | FBO | FBS |
|-----------------|-----|-----|
| Логистика до покупателя | от 50 ₽ (по объёму в литрах) | от 80 ₽ |
| Обработка отправления | 20-60 ₽ | — |
| Хранение | от 0.08 ₽/литр/день (365 дней бесплатно для новых) | — |
| Эквайринг | 0.6-1.4% | 0.6-1.4% |
| Обратная логистика | = прямой логистике | |
| Среднее время доставки (СВД) | +% к комиссии при долгой доставке | — |

#### Формула расчёта прибыли Ozon:
```
Прибыль = Цена × (1 - Комиссия% - Эквайринг%) - Логистика - Обработка - Хранение - Себестоимость
```

---

### 2.3 Yandex Market (Россия)

#### Комиссия за размещение
| Категория | FBY % | FBS % | DBS % |
|-----------|-------|-------|-------|
| Одежда и обувь | 4-8% | 8-12% | 8-12% |
| Электроника | 3-7% | 7-11% | 7-11% |
| Бытовая техника | 4-11% | 8-15% | 8-15% |
| Товары для дома | 4-9% | 8-13% | 8-13% |
| Косметика | 5-10% | 9-14% | 9-14% |
| Продукты | 4% | 8% | 8% |
| Детские товары | 4% | 8% | 8% |
| Книги | 14% | 18% | 18% |
| Спорт и отдых | 7% | 11% | 11% |

#### Дополнительные расходы
| Статья расходов | Стоимость |
|-----------------|-----------|
| Логистика (FBY) | от 45 ₽ (сортировочный центр) |
| Складская обработка | 3% от цены (мин. 20 ₽, макс. 60 ₽) |
| Эквайринг | 1.5-2.1% (зависит от частоты выплат) |
| Хранение | платно после 60 дней |
| Штраф за отмену/опоздание | % от стоимости размещения |

#### Бонусы
- Скидка 4% при отгрузке FBS за 36 часов
- Скидка 7% при отгрузке FBS за 28 часов
- Скидки для FBY при поставках в регионы

---

### 2.4 Uzum Market (Узбекистан)

#### Комиссия за продажу (с 1 мая 2024)
| Категория | Комиссия % |
|-----------|------------|
| Электроника | 3-8% |
| Бытовая техника крупная | 8% |
| Одежда | 15-20% |
| Обувь | 15-18% |
| Косметика | 12-18% |
| Товары для дома | 10-15% |
| Детские товары | 12-18% |
| Продукты | 8-12% |
| Книги | 8-12% |
| Спорт | 12-15% |
| Зоотовары | 10-15% |
| Ювелирные изделия | 25-35% |

#### Дополнительные расходы
| Статья расходов | Стоимость |
|-----------------|-----------|
| Логистический сбор (FBO) | 2,000 - 20,000 сум (по габаритам) |
| Логистический сбор (FBS) | 5,000 сум (МГТ) |
| Хранение | Информация в ЛК |
| Комиссия за возврат | По тарифу |

---

## 3. Структура базы данных

### 3.1 Миграции

```php
<?php
// database/migrations/xxxx_create_pricing_module_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Категории маркетплейсов
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace'); // wildberries, ozon, yandex, uzum
            $table->string('category_id'); // ID категории на маркетплейсе
            $table->string('name');
            $table->string('parent_id')->nullable();
            $table->string('path')->nullable(); // Полный путь: Одежда > Мужская > Куртки
            $table->timestamps();
            
            $table->unique(['marketplace', 'category_id']);
            $table->index(['marketplace', 'name']);
        });

        // Комиссии маркетплейсов
        Schema::create('marketplace_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace');
            $table->foreignId('category_id')->constrained('marketplace_categories')->cascadeOnDelete();
            $table->enum('fulfillment_type', ['fbo', 'fbs', 'dbs', 'express'])->default('fbo');
            $table->decimal('commission_percent', 5, 2); // Базовая комиссия
            $table->decimal('commission_min', 10, 2)->nullable(); // Минимальная комиссия в валюте
            $table->decimal('commission_max', 10, 2)->nullable(); // Максимальная комиссия
            $table->json('price_ranges')->nullable(); // Для разных ценовых диапазонов
            $table->date('effective_from'); // Дата начала действия
            $table->date('effective_to')->nullable(); // Дата окончания (null = бессрочно)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['marketplace', 'is_active', 'effective_from']);
        });

        // Тарифы логистики
        Schema::create('marketplace_logistics', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace');
            $table->enum('fulfillment_type', ['fbo', 'fbs', 'dbs', 'express']);
            $table->enum('logistics_type', ['delivery', 'return', 'processing', 'storage']);
            $table->string('region')->nullable(); // Регион доставки
            $table->decimal('volume_from', 10, 2)->nullable(); // Объём от (литры)
            $table->decimal('volume_to', 10, 2)->nullable(); // Объём до
            $table->decimal('weight_from', 10, 2)->nullable(); // Вес от (кг)
            $table->decimal('weight_to', 10, 2)->nullable(); // Вес до
            $table->decimal('rate', 12, 2); // Ставка
            $table->enum('rate_type', ['fixed', 'per_liter', 'per_kg', 'percent'])->default('fixed');
            $table->string('currency', 3)->default('RUB');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['marketplace', 'fulfillment_type', 'logistics_type', 'is_active']);
        });

        // Эквайринг
        Schema::create('marketplace_acquiring', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace');
            $table->enum('payout_frequency', ['daily', 'weekly', 'biweekly', 'monthly'])->nullable();
            $table->decimal('rate_percent', 5, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Продукты пользователя с ценами
        Schema::create('product_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace');
            $table->string('marketplace_sku')->nullable(); // SKU на маркетплейсе
            $table->foreignId('marketplace_category_id')->nullable()->constrained('marketplace_categories');
            $table->enum('fulfillment_type', ['fbo', 'fbs', 'dbs', 'express'])->default('fbo');
            
            // Себестоимость
            $table->decimal('cost_price', 12, 2); // Закупочная цена
            $table->decimal('packaging_cost', 10, 2)->default(0); // Упаковка
            $table->decimal('delivery_to_warehouse', 10, 2)->default(0); // Доставка до склада МП
            $table->decimal('other_costs', 10, 2)->default(0); // Прочие расходы
            
            // Габариты для расчёта логистики
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            
            // Расчётные поля
            $table->decimal('total_cost', 12, 2)->nullable(); // Полная себестоимость
            $table->decimal('commission_amount', 12, 2)->nullable(); // Сумма комиссии
            $table->decimal('logistics_cost', 12, 2)->nullable(); // Стоимость логистики
            $table->decimal('acquiring_amount', 12, 2)->nullable(); // Эквайринг
            $table->decimal('storage_cost', 12, 2)->nullable(); // Хранение
            $table->decimal('total_expenses', 12, 2)->nullable(); // Все расходы
            
            // Цены
            $table->decimal('recommended_price', 12, 2)->nullable(); // Рекомендуемая цена
            $table->decimal('current_price', 12, 2)->nullable(); // Текущая цена на МП
            $table->decimal('min_price', 12, 2)->nullable(); // Минимальная цена (в 0)
            
            // Маржа
            $table->decimal('target_margin_percent', 5, 2)->default(30); // Желаемая маржа %
            $table->decimal('actual_margin_percent', 5, 2)->nullable(); // Фактическая маржа %
            $table->decimal('actual_margin_amount', 12, 2)->nullable(); // Фактическая маржа в валюте
            $table->decimal('roi_percent', 6, 2)->nullable(); // ROI
            
            $table->string('currency', 3)->default('RUB');
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'marketplace']);
            $table->index(['user_id', 'marketplace']);
        });

        // История изменения комиссий (для уведомлений)
        Schema::create('commission_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace');
            $table->foreignId('category_id')->nullable()->constrained('marketplace_categories');
            $table->string('change_type'); // commission, logistics, acquiring
            $table->decimal('old_value', 10, 2)->nullable();
            $table->decimal('new_value', 10, 2);
            $table->date('effective_from');
            $table->text('description')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();
            
            $table->index(['marketplace', 'created_at']);
        });

        // Шаблоны расходов пользователя
        Schema::create('expense_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('marketplace')->nullable(); // null = для всех
            $table->decimal('packaging_cost', 10, 2)->default(0);
            $table->decimal('delivery_to_warehouse', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);
            $table->decimal('target_margin_percent', 5, 2)->default(30);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Налоговые настройки пользователя
        Schema::create('user_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('tax_system', ['osn', 'usn_income', 'usn_income_expense', 'patent', 'npd', 'no_vat']);
            $table->decimal('tax_rate', 5, 2)->default(0); // % налога
            $table->boolean('include_in_price')->default(true); // Учитывать в расчёте цены
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tax_settings');
        Schema::dropIfExists('expense_templates');
        Schema::dropIfExists('commission_change_logs');
        Schema::dropIfExists('product_pricings');
        Schema::dropIfExists('marketplace_acquiring');
        Schema::dropIfExists('marketplace_logistics');
        Schema::dropIfExists('marketplace_commissions');
        Schema::dropIfExists('marketplace_categories');
    }
};
```

---

## 4. Модели

### 4.1 MarketplaceCommission

```php
<?php

namespace App\Domain\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceCommission extends Model
{
    protected $fillable = [
        'marketplace',
        'category_id',
        'fulfillment_type',
        'commission_percent',
        'commission_min',
        'commission_max',
        'price_ranges',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'commission_min' => 'decimal:2',
        'commission_max' => 'decimal:2',
        'price_ranges' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    // Отношения
    public function category()
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    // Скоупы
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now());
            });
    }

    public function scopeForMarketplace(Builder $query, string $marketplace): Builder
    {
        return $query->where('marketplace', $marketplace);
    }

    // Расчёт комиссии для цены
    public function calculateCommission(float $price): float
    {
        // Проверяем диапазоны цен
        if ($this->price_ranges) {
            foreach ($this->price_ranges as $range) {
                if ($price >= ($range['from'] ?? 0) && $price <= ($range['to'] ?? PHP_INT_MAX)) {
                    return $price * ($range['percent'] / 100);
                }
            }
        }

        $commission = $price * ($this->commission_percent / 100);

        // Ограничения min/max
        if ($this->commission_min && $commission < $this->commission_min) {
            $commission = $this->commission_min;
        }
        if ($this->commission_max && $commission > $this->commission_max) {
            $commission = $this->commission_max;
        }

        return round($commission, 2);
    }
}
```

### 4.2 ProductPricing

```php
<?php

namespace App\Domain\Pricing\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductPricing extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'marketplace',
        'marketplace_sku',
        'marketplace_category_id',
        'fulfillment_type',
        'cost_price',
        'packaging_cost',
        'delivery_to_warehouse',
        'other_costs',
        'length_cm',
        'width_cm',
        'height_cm',
        'weight_kg',
        'total_cost',
        'commission_amount',
        'logistics_cost',
        'acquiring_amount',
        'storage_cost',
        'total_expenses',
        'recommended_price',
        'current_price',
        'min_price',
        'target_margin_percent',
        'actual_margin_percent',
        'actual_margin_amount',
        'roi_percent',
        'currency',
        'last_calculated_at',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'packaging_cost' => 'decimal:2',
        'delivery_to_warehouse' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'logistics_cost' => 'decimal:2',
        'acquiring_amount' => 'decimal:2',
        'storage_cost' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'recommended_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'target_margin_percent' => 'decimal:2',
        'actual_margin_percent' => 'decimal:2',
        'actual_margin_amount' => 'decimal:2',
        'roi_percent' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    // Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function marketplaceCategory()
    {
        return $this->belongsTo(MarketplaceCategory::class);
    }

    // Объём товара в литрах
    public function getVolumeLitersAttribute(): ?float
    {
        if (!$this->length_cm || !$this->width_cm || !$this->height_cm) {
            return null;
        }
        return ($this->length_cm * $this->width_cm * $this->height_cm) / 1000;
    }

    // Полная себестоимость (закупка + расходы)
    public function getTotalCostPriceAttribute(): float
    {
        return $this->cost_price 
            + $this->packaging_cost 
            + $this->delivery_to_warehouse 
            + $this->other_costs;
    }

    // Прибыльный ли товар
    public function isProfitable(): bool
    {
        return $this->actual_margin_amount > 0;
    }

    // Цвет маржинальности для UI
    public function getMarginColorAttribute(): string
    {
        if ($this->actual_margin_percent === null) return 'gray';
        if ($this->actual_margin_percent < 0) return 'red';
        if ($this->actual_margin_percent < 15) return 'orange';
        if ($this->actual_margin_percent < 30) return 'yellow';
        return 'green';
    }
}
```

---

## 5. Сервисы

### 5.1 PricingCalculatorService

```php
<?php

namespace App\Domain\Pricing\Services;

use App\Domain\Pricing\Models\ProductPricing;
use App\Domain\Pricing\Models\MarketplaceCommission;
use App\Domain\Pricing\Models\MarketplaceLogistics;
use App\Domain\Pricing\Models\MarketplaceAcquiring;
use App\Domain\Pricing\DTOs\PricingCalculationResult;

class PricingCalculatorService
{
    /**
     * Рассчитать все расходы и рекомендуемую цену
     */
    public function calculate(ProductPricing $pricing, ?float $price = null): PricingCalculationResult
    {
        $price = $price ?? $pricing->current_price ?? $pricing->recommended_price ?? 0;
        
        $result = new PricingCalculationResult();
        $result->price = $price;
        $result->marketplace = $pricing->marketplace;
        $result->fulfillmentType = $pricing->fulfillment_type;

        // 1. Полная себестоимость
        $result->totalCost = $pricing->total_cost_price;

        // 2. Комиссия маркетплейса
        $result->commission = $this->calculateCommission(
            $pricing->marketplace,
            $pricing->marketplace_category_id,
            $pricing->fulfillment_type,
            $price
        );

        // 3. Логистика
        $result->logistics = $this->calculateLogistics(
            $pricing->marketplace,
            $pricing->fulfillment_type,
            $pricing->volume_liters,
            $pricing->weight_kg
        );

        // 4. Эквайринг
        $result->acquiring = $this->calculateAcquiring($pricing->marketplace, $price);

        // 5. Хранение (опционально, если указан период)
        $result->storage = $pricing->storage_cost ?? 0;

        // 6. Итого расходы
        $result->totalExpenses = $result->totalCost 
            + $result->commission 
            + $result->logistics 
            + $result->acquiring 
            + $result->storage;

        // 7. Маржа
        $result->marginAmount = $price - $result->totalExpenses;
        $result->marginPercent = $price > 0 
            ? round(($result->marginAmount / $price) * 100, 2) 
            : 0;

        // 8. ROI (возврат на инвестиции)
        $result->roi = $result->totalCost > 0 
            ? round(($result->marginAmount / $result->totalCost) * 100, 2) 
            : 0;

        // 9. Минимальная цена (в ноль)
        $result->minPrice = $this->calculateMinPrice($pricing);

        return $result;
    }

    /**
     * Рассчитать цену для достижения желаемой маржи
     */
    public function calculatePriceForMargin(ProductPricing $pricing, float $targetMarginPercent): float
    {
        $totalCost = $pricing->total_cost_price;
        
        // Получаем примерные ставки
        $commission = $this->getCommissionRate($pricing->marketplace, $pricing->marketplace_category_id, $pricing->fulfillment_type);
        $acquiring = $this->getAcquiringRate($pricing->marketplace);
        
        // Базовая логистика (для первого приближения)
        $baseLogistics = $this->calculateLogistics(
            $pricing->marketplace,
            $pricing->fulfillment_type,
            $pricing->volume_liters,
            $pricing->weight_kg
        );

        // Формула: Price = (TotalCost + Logistics) / (1 - Commission% - Acquiring% - Margin%)
        $divisor = 1 - ($commission / 100) - ($acquiring / 100) - ($targetMarginPercent / 100);
        
        if ($divisor <= 0) {
            // Невозможно достичь такой маржи
            return 0;
        }

        $price = ($totalCost + $baseLogistics + ($pricing->storage_cost ?? 0)) / $divisor;

        return round($price, 2);
    }

    /**
     * Сравнить прибыльность на разных маркетплейсах
     */
    public function compareMarketplaces(ProductPricing $basePricing, array $marketplaces): array
    {
        $results = [];

        foreach ($marketplaces as $marketplace) {
            $pricing = clone $basePricing;
            $pricing->marketplace = $marketplace;
            
            // Получаем категорию для этого маркетплейса (если настроена)
            $pricing->marketplace_category_id = $this->findSimilarCategory(
                $basePricing->marketplace_category_id,
                $marketplace
            );

            // Рассчитываем рекомендуемую цену для целевой маржи
            $recommendedPrice = $this->calculatePriceForMargin($pricing, $pricing->target_margin_percent);
            
            // Рассчитываем показатели
            $result = $this->calculate($pricing, $recommendedPrice);
            $result->marketplace = $marketplace;
            $result->recommendedPrice = $recommendedPrice;
            
            $results[$marketplace] = $result;
        }

        // Сортируем по маржинальности
        uasort($results, fn($a, $b) => $b->marginPercent <=> $a->marginPercent);

        return $results;
    }

    /**
     * Массовый перерасчёт при изменении тарифов
     */
    public function recalculateForMarketplace(string $marketplace): int
    {
        $count = 0;

        ProductPricing::where('marketplace', $marketplace)
            ->chunk(100, function ($pricings) use (&$count) {
                foreach ($pricings as $pricing) {
                    $result = $this->calculate($pricing, $pricing->current_price);
                    
                    $pricing->update([
                        'commission_amount' => $result->commission,
                        'logistics_cost' => $result->logistics,
                        'acquiring_amount' => $result->acquiring,
                        'total_expenses' => $result->totalExpenses,
                        'actual_margin_percent' => $result->marginPercent,
                        'actual_margin_amount' => $result->marginAmount,
                        'roi_percent' => $result->roi,
                        'min_price' => $result->minPrice,
                        'recommended_price' => $this->calculatePriceForMargin($pricing, $pricing->target_margin_percent),
                        'last_calculated_at' => now(),
                    ]);
                    
                    $count++;
                }
            });

        return $count;
    }

    // Приватные методы расчёта
    
    protected function calculateCommission(string $marketplace, ?int $categoryId, string $fulfillmentType, float $price): float
    {
        $commission = MarketplaceCommission::query()
            ->forMarketplace($marketplace)
            ->active()
            ->where('category_id', $categoryId)
            ->where('fulfillment_type', $fulfillmentType)
            ->first();

        if (!$commission) {
            // Fallback: средняя комиссия по маркетплейсу
            return $price * $this->getDefaultCommissionRate($marketplace) / 100;
        }

        return $commission->calculateCommission($price);
    }

    protected function calculateLogistics(string $marketplace, string $fulfillmentType, ?float $volumeLiters, ?float $weightKg): float
    {
        $logistics = MarketplaceLogistics::query()
            ->where('marketplace', $marketplace)
            ->where('fulfillment_type', $fulfillmentType)
            ->where('logistics_type', 'delivery')
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->get();

        if ($logistics->isEmpty()) {
            return $this->getDefaultLogisticsRate($marketplace);
        }

        // Находим подходящий тариф по объёму/весу
        foreach ($logistics as $tariff) {
            $volumeMatch = true;
            $weightMatch = true;

            if ($tariff->volume_from !== null && $volumeLiters !== null) {
                $volumeMatch = $volumeLiters >= $tariff->volume_from 
                    && ($tariff->volume_to === null || $volumeLiters <= $tariff->volume_to);
            }

            if ($tariff->weight_from !== null && $weightKg !== null) {
                $weightMatch = $weightKg >= $tariff->weight_from 
                    && ($tariff->weight_to === null || $weightKg <= $tariff->weight_to);
            }

            if ($volumeMatch && $weightMatch) {
                return match ($tariff->rate_type) {
                    'fixed' => $tariff->rate,
                    'per_liter' => $tariff->rate * ($volumeLiters ?? 1),
                    'per_kg' => $tariff->rate * ($weightKg ?? 1),
                    default => $tariff->rate,
                };
            }
        }

        return $this->getDefaultLogisticsRate($marketplace);
    }

    protected function calculateAcquiring(string $marketplace, float $price): float
    {
        $acquiring = MarketplaceAcquiring::query()
            ->where('marketplace', $marketplace)
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->first();

        $rate = $acquiring?->rate_percent ?? $this->getDefaultAcquiringRate($marketplace);

        return round($price * $rate / 100, 2);
    }

    protected function calculateMinPrice(ProductPricing $pricing): float
    {
        // Минимальная цена = все расходы / (1 - комиссия% - эквайринг%)
        $totalCost = $pricing->total_cost_price;
        $logistics = $this->calculateLogistics(
            $pricing->marketplace,
            $pricing->fulfillment_type,
            $pricing->volume_liters,
            $pricing->weight_kg
        );
        $storage = $pricing->storage_cost ?? 0;
        
        $commissionRate = $this->getCommissionRate($pricing->marketplace, $pricing->marketplace_category_id, $pricing->fulfillment_type);
        $acquiringRate = $this->getAcquiringRate($pricing->marketplace);
        
        $divisor = 1 - ($commissionRate / 100) - ($acquiringRate / 100);
        
        if ($divisor <= 0) {
            return $totalCost + $logistics + $storage; // Fallback
        }

        return round(($totalCost + $logistics + $storage) / $divisor, 2);
    }

    // Дефолтные ставки
    protected function getDefaultCommissionRate(string $marketplace): float
    {
        return match ($marketplace) {
            'wildberries' => 25,
            'ozon' => 18,
            'yandex' => 10,
            'uzum' => 15,
            default => 20,
        };
    }

    protected function getDefaultLogisticsRate(string $marketplace): float
    {
        return match ($marketplace) {
            'wildberries' => 50,
            'ozon' => 100,
            'yandex' => 75,
            'uzum' => 5000, // UZS
            default => 100,
        };
    }

    protected function getDefaultAcquiringRate(string $marketplace): float
    {
        return match ($marketplace) {
            'wildberries' => 2.0,
            'ozon' => 1.0,
            'yandex' => 1.5,
            'uzum' => 0, // Включён в комиссию
            default => 1.5,
        };
    }

    protected function getCommissionRate(string $marketplace, ?int $categoryId, string $fulfillmentType): float
    {
        $commission = MarketplaceCommission::query()
            ->forMarketplace($marketplace)
            ->active()
            ->where('category_id', $categoryId)
            ->where('fulfillment_type', $fulfillmentType)
            ->first();

        return $commission?->commission_percent ?? $this->getDefaultCommissionRate($marketplace);
    }

    protected function getAcquiringRate(string $marketplace): float
    {
        $acquiring = MarketplaceAcquiring::query()
            ->where('marketplace', $marketplace)
            ->where('is_active', true)
            ->orderByDesc('effective_from')
            ->first();

        return $acquiring?->rate_percent ?? $this->getDefaultAcquiringRate($marketplace);
    }

    protected function findSimilarCategory(?int $baseCategoryId, string $marketplace): ?int
    {
        // TODO: Логика маппинга категорий между маркетплейсами
        return null;
    }
}
```

### 5.2 DTO для результата

```php
<?php

namespace App\Domain\Pricing\DTOs;

class PricingCalculationResult
{
    public string $marketplace;
    public string $fulfillmentType;
    public float $price;
    
    public float $totalCost;      // Себестоимость
    public float $commission;      // Комиссия МП
    public float $logistics;       // Логистика
    public float $acquiring;       // Эквайринг
    public float $storage;         // Хранение
    public float $totalExpenses;   // Все расходы
    
    public float $marginAmount;    // Маржа в валюте
    public float $marginPercent;   // Маржа %
    public float $roi;             // ROI %
    public float $minPrice;        // Минимальная цена
    public ?float $recommendedPrice = null;

    public function toArray(): array
    {
        return [
            'marketplace' => $this->marketplace,
            'fulfillment_type' => $this->fulfillmentType,
            'price' => $this->price,
            'total_cost' => $this->totalCost,
            'commission' => $this->commission,
            'logistics' => $this->logistics,
            'acquiring' => $this->acquiring,
            'storage' => $this->storage,
            'total_expenses' => $this->totalExpenses,
            'margin_amount' => $this->marginAmount,
            'margin_percent' => $this->marginPercent,
            'roi' => $this->roi,
            'min_price' => $this->minPrice,
            'recommended_price' => $this->recommendedPrice,
        ];
    }

    public function isProfitable(): bool
    {
        return $this->marginAmount > 0;
    }

    public function getMarginColor(): string
    {
        if ($this->marginPercent < 0) return 'red';
        if ($this->marginPercent < 15) return 'orange';
        if ($this->marginPercent < 30) return 'yellow';
        return 'green';
    }
}
```

---

## 6. Livewire компоненты

### 6.1 Калькулятор цены

```php
<?php

namespace App\Livewire\Pricing;

use App\Domain\Pricing\Models\ProductPricing;
use App\Domain\Pricing\Models\MarketplaceCategory;
use App\Domain\Pricing\Services\PricingCalculatorService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Calculator extends Component
{
    // Входные данные
    public string $marketplace = 'wildberries';
    public string $fulfillmentType = 'fbo';
    public ?int $categoryId = null;
    
    public float $costPrice = 0;
    public float $packagingCost = 0;
    public float $deliveryToWarehouse = 0;
    public float $otherCosts = 0;
    
    public ?float $lengthCm = null;
    public ?float $widthCm = null;
    public ?float $heightCm = null;
    public ?float $weightKg = null;
    
    public float $targetMarginPercent = 30;
    public ?float $currentPrice = null;
    
    // Результаты
    public ?array $result = null;
    public ?array $comparison = null;

    protected PricingCalculatorService $calculator;

    public function boot(PricingCalculatorService $calculator): void
    {
        $this->calculator = $calculator;
    }

    #[Computed]
    public function marketplaces(): array
    {
        return [
            'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            'yandex' => 'Yandex Market',
            'uzum' => 'Uzum Market',
        ];
    }

    #[Computed]
    public function fulfillmentTypes(): array
    {
        return [
            'fbo' => 'FBO (Склад маркетплейса)',
            'fbs' => 'FBS (Свой склад)',
            'dbs' => 'DBS (Своя доставка)',
            'express' => 'Экспресс',
        ];
    }

    #[Computed]
    public function categories()
    {
        return MarketplaceCategory::where('marketplace', $this->marketplace)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function volumeLiters(): ?float
    {
        if (!$this->lengthCm || !$this->widthCm || !$this->heightCm) {
            return null;
        }
        return ($this->lengthCm * $this->widthCm * $this->heightCm) / 1000;
    }

    #[Computed]
    public function totalCost(): float
    {
        return $this->costPrice + $this->packagingCost + $this->deliveryToWarehouse + $this->otherCosts;
    }

    public function calculate(): void
    {
        $pricing = new ProductPricing([
            'marketplace' => $this->marketplace,
            'fulfillment_type' => $this->fulfillmentType,
            'marketplace_category_id' => $this->categoryId,
            'cost_price' => $this->costPrice,
            'packaging_cost' => $this->packagingCost,
            'delivery_to_warehouse' => $this->deliveryToWarehouse,
            'other_costs' => $this->otherCosts,
            'length_cm' => $this->lengthCm,
            'width_cm' => $this->widthCm,
            'height_cm' => $this->heightCm,
            'weight_kg' => $this->weightKg,
            'target_margin_percent' => $this->targetMarginPercent,
            'current_price' => $this->currentPrice,
        ]);

        // Расчёт рекомендуемой цены
        $recommendedPrice = $this->calculator->calculatePriceForMargin($pricing, $this->targetMarginPercent);
        
        // Расчёт для текущей цены (если указана) или рекомендуемой
        $price = $this->currentPrice ?: $recommendedPrice;
        $calculationResult = $this->calculator->calculate($pricing, $price);
        $calculationResult->recommendedPrice = $recommendedPrice;

        $this->result = $calculationResult->toArray();
    }

    public function compareMarketplaces(): void
    {
        $pricing = new ProductPricing([
            'marketplace' => $this->marketplace,
            'fulfillment_type' => $this->fulfillmentType,
            'marketplace_category_id' => $this->categoryId,
            'cost_price' => $this->costPrice,
            'packaging_cost' => $this->packagingCost,
            'delivery_to_warehouse' => $this->deliveryToWarehouse,
            'other_costs' => $this->otherCosts,
            'length_cm' => $this->lengthCm,
            'width_cm' => $this->widthCm,
            'height_cm' => $this->heightCm,
            'weight_kg' => $this->weightKg,
            'target_margin_percent' => $this->targetMarginPercent,
        ]);

        $results = $this->calculator->compareMarketplaces($pricing, array_keys($this->marketplaces));
        
        $this->comparison = array_map(fn($r) => $r->toArray(), $results);
    }

    public function updatedMarketplace(): void
    {
        $this->categoryId = null;
        $this->result = null;
    }

    public function render()
    {
        return view('livewire.pricing.calculator');
    }
}
```

---

## 7. API эндпоинты

### 7.1 Роуты

```php
// routes/api.php
Route::prefix('v1/pricing')->middleware('auth:sanctum')->group(function () {
    // Калькулятор
    Route::post('/calculate', [PricingController::class, 'calculate']);
    Route::post('/calculate-for-margin', [PricingController::class, 'calculateForMargin']);
    Route::post('/compare', [PricingController::class, 'compare']);
    
    // Категории
    Route::get('/categories/{marketplace}', [PricingController::class, 'categories']);
    
    // Комиссии
    Route::get('/commissions/{marketplace}', [PricingController::class, 'commissions']);
    Route::get('/commissions/{marketplace}/{categoryId}', [PricingController::class, 'categoryCommission']);
    
    // Продукты пользователя
    Route::get('/products', [ProductPricingController::class, 'index']);
    Route::post('/products/{product}/pricing', [ProductPricingController::class, 'store']);
    Route::put('/products/{product}/pricing/{marketplace}', [ProductPricingController::class, 'update']);
    Route::post('/products/recalculate', [ProductPricingController::class, 'recalculate']);
});
```

### 7.2 Контроллер

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Pricing\Services\PricingCalculatorService;
use App\Domain\Pricing\Models\ProductPricing;
use App\Domain\Pricing\Models\MarketplaceCategory;
use App\Domain\Pricing\Models\MarketplaceCommission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    public function __construct(
        protected PricingCalculatorService $calculator
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace' => 'required|string|in:wildberries,ozon,yandex,uzum',
            'fulfillment_type' => 'required|string|in:fbo,fbs,dbs,express',
            'category_id' => 'nullable|exists:marketplace_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'delivery_to_warehouse' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
        ]);

        $pricing = new ProductPricing($validated);
        $result = $this->calculator->calculate($pricing, $validated['price'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
        ]);
    }

    public function calculateForMargin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace' => 'required|string',
            'fulfillment_type' => 'required|string',
            'category_id' => 'nullable|exists:marketplace_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'delivery_to_warehouse' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'target_margin_percent' => 'required|numeric|min:0|max:100',
        ]);

        $pricing = new ProductPricing($validated);
        $recommendedPrice = $this->calculator->calculatePriceForMargin(
            $pricing, 
            $validated['target_margin_percent']
        );

        $result = $this->calculator->calculate($pricing, $recommendedPrice);

        return response()->json([
            'success' => true,
            'data' => [
                'recommended_price' => $recommendedPrice,
                'calculation' => $result->toArray(),
            ],
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cost_price' => 'required|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'delivery_to_warehouse' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'target_margin_percent' => 'nullable|numeric|min:0|max:100',
            'marketplaces' => 'nullable|array',
            'marketplaces.*' => 'string|in:wildberries,ozon,yandex,uzum',
        ]);

        $pricing = new ProductPricing($validated);
        $pricing->target_margin_percent = $validated['target_margin_percent'] ?? 30;

        $marketplaces = $validated['marketplaces'] ?? ['wildberries', 'ozon', 'yandex', 'uzum'];
        $results = $this->calculator->compareMarketplaces($pricing, $marketplaces);

        return response()->json([
            'success' => true,
            'data' => array_map(fn($r) => $r->toArray(), $results),
        ]);
    }

    public function categories(string $marketplace): JsonResponse
    {
        $categories = MarketplaceCategory::where('marketplace', $marketplace)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function commissions(string $marketplace): JsonResponse
    {
        $commissions = MarketplaceCommission::forMarketplace($marketplace)
            ->active()
            ->with('category')
            ->get()
            ->groupBy('category.name');

        return response()->json([
            'success' => true,
            'data' => $commissions,
        ]);
    }
}
```

---

## 8. Blade Views

### 8.1 Калькулятор (livewire/pricing/calculator.blade.php)

```blade
<div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto">
        {{-- Заголовок --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">💰 Калькулятор цены</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Рассчитайте оптимальную цену для каждого маркетплейса</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Форма ввода --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Маркетплейс и категория --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📦 Маркетплейс</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Площадка</label>
                            <select wire:model.live="marketplace" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500">
                                @foreach($this->marketplaces as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Схема работы</label>
                            <select wire:model.live="fulfillmentType" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500">
                                @foreach($this->fulfillmentTypes as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Категория товара</label>
                        <select wire:model="categoryId" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500">
                            <option value="">Выберите категорию</option>
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @foreach($category->children ?? [] as $child)
                                    <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Себестоимость --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💵 Себестоимость</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Закупочная цена *</label>
                            <input type="number" wire:model="costPrice" step="0.01" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500"
                                placeholder="1000">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Упаковка</label>
                            <input type="number" wire:model="packagingCost" step="0.01" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="50">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Доставка до склада МП</label>
                            <input type="number" wire:model="deliveryToWarehouse" step="0.01" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="100">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Прочие расходы</label>
                            <input type="number" wire:model="otherCosts" step="0.01" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="0">
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <div class="flex justify-between items-center">
                            <span class="text-blue-700 dark:text-blue-300 font-medium">Итого себестоимость:</span>
                            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($this->totalCost, 0, '', ' ') }}
                                {{ $marketplace === 'uzum' ? 'сум' : '₽' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Габариты --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📐 Габариты (для расчёта логистики)</h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Длина, см</label>
                            <input type="number" wire:model="lengthCm" step="0.1" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="30">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ширина, см</label>
                            <input type="number" wire:model="widthCm" step="0.1" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="20">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Высота, см</label>
                            <input type="number" wire:model="heightCm" step="0.1" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="10">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Вес, кг</label>
                            <input type="number" wire:model="weightKg" step="0.001" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="0.5">
                        </div>
                    </div>

                    @if($this->volumeLiters)
                        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            Объём: {{ number_format($this->volumeLiters, 2) }} литров
                        </div>
                    @endif
                </div>

                {{-- Цена и маржа --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎯 Цена и маржа</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Желаемая маржа, %</label>
                            <input type="number" wire:model="targetMarginPercent" step="1" min="0" max="100"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500"
                                placeholder="30">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Текущая цена (опционально)</label>
                            <input type="number" wire:model="currentPrice" step="1" min="0"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                                placeholder="Для расчёта фактической маржи">
                        </div>
                    </div>

                    <div class="mt-6 flex gap-4">
                        <button wire:click="calculate" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Рассчитать
                        </button>
                        
                        <button wire:click="compareMarketplaces" class="px-6 py-3 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Сравнить МП
                        </button>
                    </div>
                </div>
            </div>

            {{-- Результаты --}}
            <div class="space-y-6">
                @if($result)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Результат расчёта</h2>
                        
                        {{-- Рекомендуемая цена --}}
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl mb-4">
                            <p class="text-sm text-green-600 dark:text-green-400">Рекомендуемая цена</p>
                            <p class="text-3xl font-bold text-green-700 dark:text-green-300">
                                {{ number_format($result['recommended_price'] ?? 0, 0, '', ' ') }}
                                {{ $marketplace === 'uzum' ? 'сум' : '₽' }}
                            </p>
                        </div>

                        {{-- Детализация расходов --}}
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Себестоимость</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($result['total_cost'], 0, '', ' ') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Комиссия МП</span>
                                <span class="font-medium text-red-600">-{{ number_format($result['commission'], 0, '', ' ') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Логистика</span>
                                <span class="font-medium text-red-600">-{{ number_format($result['logistics'], 0, '', ' ') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Эквайринг</span>
                                <span class="font-medium text-red-600">-{{ number_format($result['acquiring'], 0, '', ' ') }}</span>
                            </div>
                            @if($result['storage'] > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Хранение</span>
                                    <span class="font-medium text-red-600">-{{ number_format($result['storage'], 0, '', ' ') }}</span>
                                </div>
                            @endif
                            
                            <hr class="border-gray-200 dark:border-gray-700">
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-900 dark:text-white">Итого расходы</span>
                                <span class="font-bold text-red-600">{{ number_format($result['total_expenses'], 0, '', ' ') }}</span>
                            </div>
                        </div>

                        {{-- Маржа --}}
                        <div class="mt-4 p-4 rounded-xl {{ $result['margin_amount'] >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                            <div class="flex justify-between items-center">
                                <span class="{{ $result['margin_amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">Прибыль</span>
                                <span class="text-2xl font-bold {{ $result['margin_amount'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                    {{ number_format($result['margin_amount'], 0, '', ' ') }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Маржинальность</span>
                                <span class="font-bold {{ $result['margin_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $result['margin_percent'] }}%
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">ROI</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $result['roi'] }}%</span>
                            </div>
                        </div>

                        {{-- Минимальная цена --}}
                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Минимальная цена (в 0)</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($result['min_price'], 0, '', ' ') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Сравнение маркетплейсов --}}
                @if($comparison)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 Сравнение маркетплейсов</h2>
                        
                        <div class="space-y-3">
                            @foreach($comparison as $mp => $data)
                                <div class="p-4 rounded-xl border {{ $loop->first ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $this->marketplaces[$mp] ?? $mp }}
                                            @if($loop->first)
                                                <span class="ml-2 text-xs bg-green-500 text-white px-2 py-0.5 rounded-full">Лучший</span>
                                            @endif
                                        </span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ number_format($data['recommended_price'], 0, '', ' ') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center mt-2 text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Маржа</span>
                                        <span class="{{ $data['margin_percent'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                            {{ $data['margin_percent'] }}% ({{ number_format($data['margin_amount'], 0, '', ' ') }})
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
```

---

## 9. Автообновление комиссий

### 9.1 Команда для парсинга комиссий

```php
<?php

namespace App\Console\Commands;

use App\Domain\Pricing\Models\MarketplaceCommission;
use App\Domain\Pricing\Models\CommissionChangeLog;
use App\Domain\Pricing\Services\PricingCalculatorService;
use App\Notifications\CommissionChangedNotification;
use Illuminate\Console\Command;

class UpdateMarketplaceCommissions extends Command
{
    protected $signature = 'pricing:update-commissions {marketplace?}';
    protected $description = 'Update marketplace commissions from API or manual sources';

    public function handle(PricingCalculatorService $calculator): int
    {
        $marketplaces = $this->argument('marketplace') 
            ? [$this->argument('marketplace')] 
            : ['wildberries', 'ozon', 'yandex', 'uzum'];

        foreach ($marketplaces as $marketplace) {
            $this->info("Updating commissions for {$marketplace}...");
            
            $changes = $this->updateCommissions($marketplace);
            
            if ($changes > 0) {
                $this->info("Found {$changes} changes. Recalculating prices...");
                $recalculated = $calculator->recalculateForMarketplace($marketplace);
                $this->info("Recalculated {$recalculated} products.");
                
                // Отправляем уведомления
                $this->notifyUsers($marketplace);
            }
        }

        return Command::SUCCESS;
    }

    protected function updateCommissions(string $marketplace): int
    {
        // TODO: Реализовать парсинг комиссий из API маркетплейсов
        // Для WB, Ozon, YM есть API с тарифами
        // Для Uzum — парсинг документации
        
        return 0;
    }

    protected function notifyUsers(string $marketplace): void
    {
        $logs = CommissionChangeLog::where('marketplace', $marketplace)
            ->where('notification_sent', false)
            ->get();

        // TODO: Отправить уведомления пользователям с товарами на этом МП
    }
}
```

### 9.2 Планировщик

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Обновлять комиссии каждый день в 6:00
    $schedule->command('pricing:update-commissions')
        ->dailyAt('06:00')
        ->withoutOverlapping();
}
```

---

## 10. Роуты

```php
// routes/web.php
Route::middleware('auth')->prefix('pricing')->name('pricing.')->group(function () {
    Route::get('/calculator', \App\Livewire\Pricing\Calculator::class)->name('calculator');
    Route::get('/products', \App\Livewire\Pricing\Products::class)->name('products');
    Route::get('/commissions', \App\Livewire\Pricing\Commissions::class)->name('commissions');
    Route::get('/settings', \App\Livewire\Pricing\Settings::class)->name('settings');
});
```

---

## 11. Навигация (добавить в сайдбар)

```blade
{{-- В app.blade.php --}}
<a href="{{ route('pricing.calculator') }}" class="...">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
    </svg>
    Калькулятор цен
</a>
```

---

## 12. Чеклист реализации

### Этап 1: База данных (1 день)
- [ ] Создать миграции
- [ ] Создать модели
- [ ] Создать сидеры с базовыми комиссиями

### Этап 2: Сервисы (2 дня)
- [ ] PricingCalculatorService
- [ ] DTO для результатов
- [ ] Unit-тесты

### Этап 3: UI (2 дня)
- [ ] Livewire Calculator
- [ ] Blade views
- [ ] Адаптивный дизайн

### Этап 4: API (1 день)
- [ ] API эндпоинты
- [ ] Документация API

### Этап 5: Интеграция (2 дня)
- [ ] Привязка к товарам пользователя
- [ ] Массовый перерасчёт
- [ ] Уведомления об изменении комиссий

### Этап 6: Автообновление (1 день)
- [ ] Парсинг комиссий из API МП
- [ ] Планировщик задач

**Итого: ~9 дней**

---

## 13. Команда для Claude Code

```
Прочитай файл PRICING_MODULE_TZ.md и реализуй модуль расчёта цен:

cat PRICING_MODULE_TZ.md

1. Начни с миграций (раздел 3)
2. Создай модели (раздел 4)
3. Реализуй PricingCalculatorService (раздел 5)
4. Создай Livewire калькулятор (раздел 6)
5. Добавь API эндпоинты (раздел 7)
6. Создай blade views (раздел 8)

После каждого этапа пиши что готово.
```
