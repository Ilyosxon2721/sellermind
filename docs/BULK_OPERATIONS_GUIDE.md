# Руководство по массовым операциям с товарами

**Версия:** 1.0
**Дата:** 2026-01-10

---

## Обзор

Массовые операции позволяют быстро обновить сотни или тысячи товаров одним действием. Это экономит часы работы и снижает вероятность ошибок.

**Что можно делать:**
- ✅ Экспортировать товары в Excel/CSV
- ✅ Массово обновлять цены через Excel
- ✅ Массово изменять остатки
- ✅ Активировать/деактивировать товары
- ✅ Изменять категории
- ✅ Preview изменений перед применением

**Экономия времени:**
- Обновление 1000 товаров: 5 минут вместо 10 часов
- ROI: 500%+

---

## API Endpoints

### 1. Экспорт товаров (Export)

**Endpoint:** `POST /api/products/bulk/export`

**Описание:** Экспортирует товары с вариантами в CSV файл

**Request:**
```json
{
  "product_ids": [1, 2, 3],  // Опционально: конкретные товары
  "category_id": 5,           // Опционально: только из категории
  "include_archived": false   // Опционально: включить архивные
}
```

**Response:** CSV файл для скачивания

**Пример использования:**
```bash
curl -X POST https://your-domain.com/api/products/bulk/export \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"category_id": 5}' \
  --output products.csv
```

**Формат CSV:**
```csv
Product ID;Product Name;Article;Category;Variant ID;SKU;Barcode;Purchase Price;Retail Price;Old Price;Stock;Is Active;Variant Options
1;T-shirt Basic;TSH-001;Clothing;10;SKU-001;123456789;500;1200;1500;100;Yes;Red, L
```

---

### 2. Preview изменений (Preview Import)

**Endpoint:** `POST /api/products/bulk/import/preview`

**Описание:** Показывает какие изменения будут применены БЕЗ сохранения в БД

**Request:**
```
Content-Type: multipart/form-data

file: products.csv (макс 10MB)
```

**Response:**
```json
{
  "total_rows": 1000,
  "changes_count": 450,
  "errors_count": 2,
  "preview": [
    {
      "row": 2,
      "variant_id": 10,
      "sku": "SKU-001",
      "product_name": "T-shirt Basic",
      "changes": {
        "retail_price": {
          "old": 1200,
          "new": 1100
        },
        "stock_default": {
          "old": 100,
          "new": 150
        }
      }
    }
  ],
  "errors": [
    "Row 5: Variant not found (ID: 999)"
  ]
}
```

**Пример:**
```bash
curl -X POST https://your-domain.com/api/products/bulk/import/preview \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@products_updated.csv"
```

---

### 3. Применить изменения (Apply Import)

**Endpoint:** `POST /api/products/bulk/import/apply`

**Описание:** Применяет изменения из CSV файла (асинхронно через очередь)

**Request:**
```
Content-Type: multipart/form-data

file: products.csv
```

**Response:**
```json
{
  "message": "Bulk update queued for processing. You will receive a notification when completed."
}
```

**Как это работает:**
1. Файл загружается на сервер
2. Создаётся Job в очереди
3. Job обрабатывает файл асинхронно
4. Пользователь получает уведомление по завершению

**Пример:**
```bash
curl -X POST https://your-domain.com/api/products/bulk/import/apply \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@products_updated.csv"
```

---

### 4. Bulk Update (выбранные товары)

**Endpoint:** `POST /api/products/bulk/update`

**Описание:** Массовое обновление выбранных вариантов товаров

**Request:**
```json
{
  "variant_ids": [10, 11, 12, 13],
  "action": "activate",  // activate, deactivate, update_prices, update_stock, update_category
  "data": {}  // Опционально: данные для обновления
}
```

**Действия (actions):**

#### 4.1 Activate (активировать)
```json
{
  "variant_ids": [10, 11, 12],
  "action": "activate"
}
```

#### 4.2 Deactivate (деактивировать)
```json
{
  "variant_ids": [10, 11, 12],
  "action": "deactivate"
}
```

#### 4.3 Update Prices (обновить цены)
```json
{
  "variant_ids": [10, 11, 12],
  "action": "update_prices",
  "data": {
    "retail_price": 1500,     // Опционально
    "purchase_price": 600,    // Опционально
    "old_price": 1800         // Опционально
  }
}
```

#### 4.4 Update Stock (обновить остатки)
```json
{
  "variant_ids": [10, 11, 12],
  "action": "update_stock",
  "data": {
    "stock_default": 200
  }
}
```

#### 4.5 Update Category (изменить категорию)
```json
{
  "variant_ids": [10, 11, 12],
  "action": "update_category",
  "data": {
    "category_id": 5
  }
}
```

**Response:**
```json
{
  "message": "Bulk update completed successfully.",
  "updated_count": 3
}
```

---

## Использование через Excel

### Шаг 1: Экспорт товаров

1. Зайдите в раздел "Товары"
2. Выберите товары или категорию
3. Нажмите "Экспортировать в Excel"
4. Скачайте файл `products_export_2026-01-10_143000.csv`

### Шаг 2: Редактирование в Excel

1. Откройте файл в Excel/LibreOffice Calc
2. **Важно:** Не удаляйте столбцы и не меняйте их порядок
3. **Важно:** Не изменяйте ID товаров и вариантов

**Что можно редактировать:**
- Purchase Price (закупочная цена)
- Retail Price (розничная цена)
- Old Price (старая цена для зачёркивания)
- Stock (остатки)
- Is Active (Yes/No)

**Пример редактирования:**

**Было:**
```
SKU-001 | ... | 1200 | 1500 | 100 | Yes
SKU-002 | ... | 1300 | 1600 | 50  | Yes
```

**Стало (скидка 10%):**
```
SKU-001 | ... | 1080 | 1500 | 100 | Yes
SKU-002 | ... | 1170 | 1600 | 50  | Yes
```

### Шаг 3: Preview изменений

1. Нажмите "Импортировать товары"
2. Загрузите отредактированный CSV
3. Система покажет preview:
   - Сколько товаров будет обновлено
   - Какие именно изменения
   - Есть ли ошибки

4. Проверьте preview внимательно!

### Шаг 4: Применить изменения

1. Если всё правильно → нажмите "Применить"
2. Изменения обрабатываются в фоне (через очередь)
3. Получите уведомление по завершению

---

## Логирование

Все bulk операции логируются в `storage/logs/laravel.log`:

```
[2026-01-10 14:30:00] local.INFO: Bulk product update {"user_id":1,"action":"update_prices","variant_ids":[10,11,12],"updated":3}
```

**Что логируется:**
- user_id - кто сделал операцию
- action - какое действие
- variant_ids - какие варианты
- updated - сколько обновлено
- errors - ошибки (если есть)

---

## Безопасность

### Валидация

1. **Проверка прав доступа**
   - Можно редактировать только товары своей компании
   - Проверяется на уровне БД

2. **Валидация данных**
   - Цены: положительные числа
   - Остатки: целые неотрицательные числа
   - Is Active: только Yes/No

3. **Лимиты**
   - Максимальный размер файла: 10MB
   - Максимум вариантов в bulk update: без лимита (но рекомендуется < 10000)

### Откат изменений

**Если допустили ошибку:**

1. **Сразу после применения:**
   - Экспортируйте товары снова
   - Сравните с предыдущей версией
   - Импортируйте старые значения

2. **Через некоторое время:**
   - Проверьте логи: `storage/logs/laravel.log`
   - Найдите что изменилось
   - Вручную откатите через bulk update или import

**Рекомендация:** Всегда делайте backup экспорта перед массовыми изменениями!

---

## Best Practices

### 1. Всегда делайте Preview

```bash
# ❌ Плохо: сразу применять
curl -X POST /api/products/bulk/import/apply -F "file=@products.csv"

# ✅ Хорошо: сначала preview
curl -X POST /api/products/bulk/import/preview -F "file=@products.csv"
# Проверить результат
# Только потом apply
curl -X POST /api/products/bulk/import/apply -F "file=@products.csv"
```

### 2. Сохраняйте копии экспортов

```bash
# Экспорт перед изменениями
products_backup_2026-01-10.csv

# Отредактированная версия
products_updated_2026-01-10.csv
```

### 3. Тестируйте на малой выборке

Перед массовым обновлением 1000 товаров:
1. Экспортируйте 10 товаров
2. Отредактируйте
3. Примените
4. Проверьте результат
5. Только потом делайте массовое обновление

### 4. Используйте правильные действия

```javascript
// Активировать 1000 товаров
// ❌ Плохо: Excel import (долго)
// ✅ Хорошо: Bulk Update с action=activate

POST /api/products/bulk/update
{
  "variant_ids": [1,2,3,...,1000],
  "action": "activate"
}
```

### 5. Мониторьте очередь

```bash
# Проверить статус очереди
php artisan queue:work --once

# Мониторинг в реальном времени
watch -n 5 'php artisan queue:work --once'
```

---

## Troubleshooting

### Проблема 1: "File too large"

**Ошибка:** Файл больше 10MB

**Решение:**
- Разбейте файл на части
- Импортируйте частями
- Или используйте Bulk Update API вместо Excel

### Проблема 2: "Variant not found"

**Ошибка:** Row 5: Variant not found (ID: 999)

**Причина:**
- Товар был удалён
- ID неправильный
- Товар принадлежит другой компании

**Решение:**
- Удалите эту строку из CSV
- Или экспортируйте товары заново

### Проблема 3: "Changes not applied"

**Проблема:** Preview показывает изменения, но они не применяются

**Причины:**
1. Queue worker не запущен
2. Job failed

**Решение:**
```bash
# Проверить queue worker
php artisan queue:work

# Проверить failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job_id>
```

### Проблема 4: Кодировка (кракозябры)

**Проблема:** Русские буквы отображаются неправильно

**Решение:**
1. Открывайте CSV в Excel
2. При импорте выбирайте кодировку UTF-8 с BOM
3. Или используйте LibreOffice Calc (лучше с UTF-8)

---

## Примеры использования

### Пример 1: Скидка 10% на все товары категории

```bash
# 1. Экспорт товаров категории
curl -X POST https://api.example.com/api/products/bulk/export \
  -H "Authorization: Bearer TOKEN" \
  -d '{"category_id": 5}' \
  --output products_category_5.csv

# 2. Редактировать в Excel (снизить retail_price на 10%)

# 3. Preview
curl -X POST https://api.example.com/api/products/bulk/import/preview \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@products_category_5_updated.csv"

# 4. Apply
curl -X POST https://api.example.com/api/products/bulk/import/apply \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@products_category_5_updated.csv"
```

### Пример 2: Деактивировать все товары с нулевым остатком

```javascript
// 1. Получить все товары с stock = 0
const response = await fetch('/api/products?stock=0');
const products = await response.json();

// 2. Собрать variant_ids
const variantIds = products.data.flatMap(p => p.variants.map(v => v.id));

// 3. Bulk deactivate
await fetch('/api/products/bulk/update', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer TOKEN'
  },
  body: JSON.stringify({
    variant_ids: variantIds,
    action: 'deactivate'
  })
});
```

### Пример 3: Установить одинаковую цену для всех размеров

```bash
# Получить все варианты товара
curl https://api.example.com/api/products/123 -H "Authorization: Bearer TOKEN"

# Bulk update цен
curl -X POST https://api.example.com/api/products/bulk/update \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "variant_ids": [10, 11, 12, 13, 14],
    "action": "update_prices",
    "data": {
      "retail_price": 1500,
      "old_price": 2000
    }
  }'
```

---

## FAQ

**Q: Можно ли откатить изменения?**
A: Нет автоматического отката. Сохраняйте экспорт перед изменениями и импортируйте старые значения если нужно откатить.

**Q: Сколько времени обрабатывается импорт?**
A: ~100-200 товаров в секунду. 1000 товаров ≈ 5-10 секунд.

**Q: Можно ли импортировать новые товары?**
A: Нет, только обновление существующих. Для создания используйте API `POST /api/products`.

**Q: Работает ли это для marketplace товаров?**
A: Это для локальных товаров. Для marketplace товаров используйте соответствующие API (WB, Ozon, etc.).

**Q: Можно ли изменить SKU через bulk?**
A: Нет, SKU неизменяемый. Можно только изменить цены, остатки, статус.

---

## Заключение

Массовые операции - мощный инструмент для быстрого управления товарами. Используйте с осторожностью и всегда делайте preview перед применением изменений!

**Экономия времени:**
- Без bulk: 10 часов на 1000 товаров
- С bulk: 5 минут на 1000 товаров
- **Экономия: 99.2%** ⚡

**Следующие шаги:**
1. Прочитайте эту документацию
2. Попробуйте на 10 товарах
3. Используйте для массовых обновлений

**Поддержка:** [support@sellermind.ai](mailto:support@sellermind.ai)
