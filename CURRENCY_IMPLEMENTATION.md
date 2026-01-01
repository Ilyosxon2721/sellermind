# Реализация отображения валют заказов

## Дата: 2025-12-15

## Проблема

Система отображала все валюты как "рубли", хотя заказы могут быть в разных валютах (белорусский рубль, узбекский сум и т.д.). При этом API Wildberries предоставляет данные о валютах в формате ISO 4217 (числовые коды).

## Требования

- Правильно определять валюту заказа по числовому коду ISO 4217
- Правильно называть "Конвертированную валюту" (в данном случае - узбекский сум)
- Учитывать, что разные компании работают с разными валютами

## Решение

### 1. Создан CurrencyHelper

**Файл**: `app/Helpers/CurrencyHelper.php`

Хелпер для работы с валютами на основе ISO 4217:

```php
<?php

namespace App\Helpers;

class CurrencyHelper
{
    private const CURRENCY_CODES = [
        '643' => ['code' => 'RUB', 'name' => 'Российский рубль', 'symbol' => '₽'],
        '933' => ['code' => 'BYN', 'name' => 'Белорусский рубль', 'symbol' => 'Br'],
        '860' => ['code' => 'UZS', 'name' => 'Узбекский сум', 'symbol' => 'сўм'],
        '398' => ['code' => 'KZT', 'name' => 'Казахстанский тенге', 'symbol' => '₸'],
        '417' => ['code' => 'KGS', 'name' => 'Киргизский сом', 'symbol' => 'с'],
        '051' => ['code' => 'AMD', 'name' => 'Армянский драм', 'symbol' => '֏'],
        '944' => ['code' => 'AZN', 'name' => 'Азербайджанский манат', 'symbol' => '₼'],
        '840' => ['code' => 'USD', 'name' => 'Доллар США', 'symbol' => '$'],
        '978' => ['code' => 'EUR', 'name' => 'Евро', 'symbol' => '€'],
    ];

    /**
     * Получить информацию о валюте по числовому коду ISO 4217
     */
    public static function getCurrencyByCode($numericCode): array
    {
        $code = (string) $numericCode;

        return self::CURRENCY_CODES[$code] ?? [
            'code' => 'UNKNOWN',
            'name' => 'Неизвестная валюта',
            'symbol' => '',
        ];
    }

    /**
     * Получить название валюты
     */
    public static function getCurrencyName($numericCode): string
    {
        return self::getCurrencyByCode($numericCode)['name'];
    }

    /**
     * Получить буквенный код валюты (RUB, USD и т.д.)
     */
    public static function getCurrencyCode($numericCode): string
    {
        return self::getCurrencyByCode($numericCode)['code'];
    }

    /**
     * Получить символ валюты
     */
    public static function getCurrencySymbol($numericCode): string
    {
        return self::getCurrencyByCode($numericCode)['symbol'];
    }

    /**
     * Форматировать цену с валютой
     */
    public static function formatPrice($amount, $currencyCode = null, bool $withSymbol = true): string
    {
        if ($amount === null) {
            return '';
        }

        $formatted = number_format($amount, 2, '.', ' ');

        if (!$withSymbol || $currencyCode === null) {
            return $formatted;
        }

        $symbol = self::getCurrencySymbol($currencyCode);

        return $formatted . ' ' . $symbol;
    }

    /**
     * Конвертировать копейки в рубли/основную валюту
     */
    public static function fromKopecks(?int $kopecks): float
    {
        if ($kopecks === null) {
            return 0.0;
        }

        return $kopecks / 100;
    }
}
```

### 2. Обновлён MarketplaceOrderController

**Файл**: `app/Http/Controllers/Api/MarketplaceOrderController.php`

**Добавлен импорт** (строка 6):
```php
use App\Helpers\CurrencyHelper;
```

**Обновлены строки 116-122**:

#### Было:
```php
// ФИНАНСЫ
'Сумма заказа' => number_format($wbOrder->total_amount, 2, '.', ' ') . ' ' . $wbOrder->currency,
'Цена' => $wbOrder->price ? (number_format($wbOrder->price / 100, 2, '.', ' ') . ' руб') : null,
'Цена сканирования' => $wbOrder->scan_price ? (number_format($wbOrder->scan_price / 100, 2, '.', ' ') . ' руб') : null,
'Конвертированная цена' => $wbOrder->converted_price ? (number_format($wbOrder->converted_price / 100, 2, '.', ' ') . ' руб') : null,
'Валюта' => $wbOrder->currency,
'Код валюты' => $wbOrder->currency_code,
'Код конвертированной валюты' => $wbOrder->converted_currency_code,
```

#### Стало:
```php
// ФИНАНСЫ
'Сумма заказа' => number_format($wbOrder->total_amount, 2, '.', ' ') . ' ' . $wbOrder->currency,
'Цена' => $wbOrder->price ? CurrencyHelper::formatPrice(CurrencyHelper::fromKopecks($wbOrder->price), $wbOrder->currency_code) : null,
'Цена сканирования' => $wbOrder->scan_price ? CurrencyHelper::formatPrice(CurrencyHelper::fromKopecks($wbOrder->scan_price), $wbOrder->currency_code) : null,
'Конвертированная цена' => $wbOrder->converted_price ? CurrencyHelper::formatPrice(CurrencyHelper::fromKopecks($wbOrder->converted_price), $wbOrder->converted_currency_code) : null,
'Валюта' => CurrencyHelper::getCurrencyName($wbOrder->currency_code) . ' (' . CurrencyHelper::getCurrencyCode($wbOrder->currency_code) . ')',
'Конвертированная валюта' => $wbOrder->converted_currency_code ? (CurrencyHelper::getCurrencyName($wbOrder->converted_currency_code) . ' (' . CurrencyHelper::getCurrencyCode($wbOrder->converted_currency_code) . ')') : null,
```

## Как это работает

### Данные из WB API

WB API возвращает заказы с двумя валютами:

```json
{
  "currencyCode": 933,          // Валюта заказа (BYN - Белорусский рубль)
  "convertedCurrencyCode": 860, // Конвертированная валюта (UZS - Узбекский сум)
  "price": 10333,               // Цена в копейках
  "convertedPrice": 42223132    // Конвертированная цена в копейках
}
```

### Процесс отображения

1. **Конвертация из копеек**:
   ```php
   CurrencyHelper::fromKopecks($wbOrder->price) // 10333 → 103.33
   ```

2. **Форматирование с символом валюты**:
   ```php
   CurrencyHelper::formatPrice(103.33, '933') // "103.33 Br"
   ```

3. **Отображение названия валюты**:
   ```php
   CurrencyHelper::getCurrencyName('933') // "Белорусский рубль"
   CurrencyHelper::getCurrencyCode('933') // "BYN"
   // Результат: "Белорусский рубль (BYN)"
   ```

## Пример отображения

### Заказ с белорусским рублём и узбекским сумом

**До изменений**:
```
Цена: 103.33 руб
Конвертированная цена: 422 231.32 руб
Валюта: BYN
Код валюты: 933
Код конвертированной валюты: 860
```

**После изменений**:
```
Цена: 103.33 Br
Конвертированная цена: 422 231.32 сўм
Валюта: Белорусский рубль (BYN)
Конвертированная валюта: Узбекский сум (UZS)
```

## Поддерживаемые валюты

| Код ISO | Буквенный код | Название | Символ |
|---------|---------------|----------|--------|
| 643 | RUB | Российский рубль | ₽ |
| 933 | BYN | Белорусский рубль | Br |
| 860 | UZS | Узбекский сум | сўм |
| 398 | KZT | Казахстанский тенге | ₸ |
| 417 | KGS | Киргизский сом | с |
| 051 | AMD | Армянский драм | ֏ |
| 944 | AZN | Азербайджанский манат | ₼ |
| 840 | USD | Доллар США | $ |
| 978 | EUR | Евро | € |

## Расширение списка валют

Чтобы добавить новую валюту, просто добавьте её в массив `CURRENCY_CODES` в `CurrencyHelper.php`:

```php
private const CURRENCY_CODES = [
    // ...
    'XXX' => ['code' => 'ABC', 'name' => 'Название валюты', 'symbol' => 'Символ'],
];
```

Где:
- `XXX` - числовой код ISO 4217 (строка)
- `code` - буквенный код валюты (3 символа)
- `name` - полное название валюты
- `symbol` - символ валюты

## Справка по ISO 4217

ISO 4217 - международный стандарт кодов валют, содержащий:
- 3-буквенные коды валют (USD, EUR, RUB и т.д.)
- 3-цифровые числовые коды (840, 978, 643 и т.д.)

WB API использует именно числовые коды.

## Тестирование

Для проверки работы:

1. Откройте страницу заказов маркетплейса
2. Выберите заказ со статусом "На сборке" или другой
3. Откройте детали заказа
4. Проверьте, что:
   - Цены отображаются с правильными символами валют
   - "Валюта" показывает название + код (например, "Белорусский рубль (BYN)")
   - "Конвертированная валюта" показывает название + код (например, "Узбекский сум (UZS)")

## Логирование

При необходимости можно добавить логирование в MarketplaceOrderController:

```php
Log::info('Currency display', [
    'order_id' => $wbOrder->id,
    'currency_code' => $wbOrder->currency_code,
    'currency_name' => CurrencyHelper::getCurrencyName($wbOrder->currency_code),
    'converted_currency_code' => $wbOrder->converted_currency_code,
    'converted_currency_name' => CurrencyHelper::getCurrencyName($wbOrder->converted_currency_code),
]);
```

## Статус

✅ **Реализация завершена**
- Создан CurrencyHelper с поддержкой 9 валют
- Обновлён MarketplaceOrderController для использования CurrencyHelper
- Цены конвертируются из копеек в основную валюту
- Валюты отображаются с правильными названиями и символами
- "Конвертированная валюта" правильно названа и отображается

## Дополнительная информация

Связанные файлы:
- [app/Helpers/CurrencyHelper.php](app/Helpers/CurrencyHelper.php) - хелпер для работы с валютами
- [app/Http/Controllers/Api/MarketplaceOrderController.php](app/Http/Controllers/Api/MarketplaceOrderController.php) - контроллер заказов
- [app/Models/WbOrder.php](app/Models/WbOrder.php) - модель заказа WB

Связанная документация:
- [STICKER_PRINTING_GUIDE.md](STICKER_PRINTING_GUIDE.md) - руководство по печати стикеров
- [TARE_FIX_SUMMARY.md](TARE_FIX_SUMMARY.md) - исправление работы с коробами
