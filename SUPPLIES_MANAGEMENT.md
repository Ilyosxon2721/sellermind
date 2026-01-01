# Система управления поставками

## Описание

Добавлена функциональность для управления поставками заказов. Система позволяет:
- Создавать поставки
- Добавлять заказы в поставки
- Убирать заказы из поставок
- Отслеживать статус поставок
- Автоматически рассчитывать количество заказов и общую сумму

## Структура базы данных

### Таблица `supplies`

```sql
CREATE TABLE supplies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    marketplace_account_id BIGINT UNSIGNED NOT NULL,
    external_supply_id VARCHAR(255) NULL,  -- ID поставки в WB (например, WB-GI-12345678)
    name VARCHAR(255) NOT NULL,  -- Название поставки
    status ENUM('draft', 'in_assembly', 'ready', 'sent', 'delivered', 'cancelled') DEFAULT 'draft',
    description TEXT NULL,
    orders_count INT DEFAULT 0,  -- Количество заказов
    total_amount DECIMAL(15,2) DEFAULT 0,  -- Общая сумма
    closed_at TIMESTAMP NULL,  -- Когда закрыта для добавления заказов
    sent_at TIMESTAMP NULL,  -- Когда отправлена
    delivered_at TIMESTAMP NULL,  -- Когда доставлена
    metadata JSON NULL,  -- Дополнительные данные
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (marketplace_account_id) REFERENCES marketplace_accounts(id) ON DELETE CASCADE,
    INDEX idx_marketplace_account_id (marketplace_account_id),
    INDEX idx_status (status),
    INDEX idx_external_supply_id (external_supply_id),
    INDEX idx_created_at (created_at)
);
```

### Связь с заказами

Заказы связываются с поставками через поле `supply_id` в таблице `marketplace_orders`:
- Если у поставки есть `external_supply_id`, используется оно
- Если нет, используется внутренний ID в формате `SUPPLY-{id}`

## API Endpoints

### Получить список поставок
```
GET /api/marketplace/supplies
Params: company_id, marketplace_account_id, status (optional)
```

### Получить открытые поставки (доступные для добавления заказов)
```
GET /api/marketplace/supplies/open
Params: company_id, marketplace_account_id
```

### Создать поставку
```
POST /api/marketplace/supplies
Body: {
    marketplace_account_id: number,
    company_id: number,
    name: string,
    description?: string,
    external_supply_id?: string
}
```

### Получить информацию о поставке
```
GET /api/marketplace/supplies/{supply}
```

### Обновить поставку
```
PUT /api/marketplace/supplies/{supply}
Body: {
    name?: string,
    description?: string,
    status?: string
}
```

### Добавить заказ в поставку
```
POST /api/marketplace/supplies/{supply}/orders
Body: {
    order_id: number
}
```

### Убрать заказ из поставки
```
DELETE /api/marketplace/supplies/{supply}/orders
Body: {
    order_id: number
}
```

### Закрыть поставку
```
POST /api/marketplace/supplies/{supply}/close
```

### Удалить поставку
```
DELETE /api/marketplace/supplies/{supply}
Note: Можно удалить только пустую поставку (без заказов)
```

## Модель Supply

### Статусы поставки

- `draft` - Черновик (можно добавлять заказы)
- `in_assembly` - На сборке (можно добавлять заказы)
- `ready` - Готова к отправке (нельзя добавлять заказы)
- `sent` - Отправлена (нельзя редактировать)
- `delivered` - Доставлена (нельзя редактировать)
- `cancelled` - Отменена

### Методы модели

```php
// Проверить, можно ли добавлять заказы
$supply->canAddOrders(): bool

// Проверить, можно ли редактировать
$supply->canEdit(): bool

// Пересчитать статистику
$supply->recalculateStats(): void

// Закрыть для добавления заказов
$supply->close(): void

// Отметить как отправленную
$supply->markAsSent(): void

// Отметить как доставленную
$supply->markAsDelivered(): void
```

### Scopes

```php
// Открытые поставки
Supply::open()->get()

// Поставки конкретного аккаунта
Supply::forAccount($accountId)->get()
```

### Атрибуты

```php
$supply->status_label  // "Черновик", "На сборке", и т.д.
$supply->status_color  // "gray", "blue", "green", и т.д. для UI
```

## UI Компоненты

### Кнопка "Создать поставку"

Расположена в шапке страницы заказов, справа от кнопки Live Monitoring.

### Модальное окно создания поставки

Открывается по нажатию на кнопку "Создать поставку". Содержит:
- Поле "Название поставки" (обязательное)
- Поле "Описание" (необязательное)
- Кнопки "Отмена" и "Создать"

### Модальное окно добавления заказа в поставку

Открывается при клике на кнопку "Добавить в поставку" у заказа. Содержит:
- Информацию о выбранном заказе
- Список доступных открытых поставок
- Кнопку "Создать новую" поставку
- Информацию о каждой поставке:
  - Название и описание
  - Количество заказов
  - Общая сумма
  - Статус
- Кнопки "Отмена" и "Добавить"

## Бизнес-логика

### Добавление заказа в поставку

1. Проверяется, что поставка открыта (`status` = `draft` или `in_assembly`, `closed_at` = NULL)
2. Проверяется, что заказ принадлежит тому же аккаунту маркетплейса
3. Проверяется, что заказ ещё не в другой поставке
4. Проверяется статус заказа (только `new` или `in_assembly`)
5. Устанавливается `supply_id` у заказа
6. Автоматически пересчитывается статистика поставки

### Удаление заказа из поставки

1. Проверяется, что поставку можно редактировать
2. Проверяется, что заказ действительно в этой поставке
3. Очищается `supply_id` у заказа
4. Автоматически пересчитывается статистика поставки

### Закрытие поставки

1. Устанавливается `closed_at` = текущее время
2. Изменяется `status` на `ready`
3. После этого нельзя добавлять/удалять заказы

## JavaScript API

### Переменные состояния

```javascript
supplies: [],  // Все поставки
openSupplies: [],  // Только открытые поставки
showCreateSupplyModal: false,
showAddToSupplyModal: false,
selectedOrderForSupply: null,
newSupply: { name: '', description: '' },
selectedSupplyId: null,
suppliesLoading: false,
```

### Методы

```javascript
// Загрузить все поставки
await loadSupplies()

// Загрузить открытые поставки
await loadOpenSupplies()

// Открыть модальное окно создания
openCreateSupplyModal()

// Создать новую поставку
await createSupply()

// Открыть модальное окно добавления заказа
openAddToSupplyModal(order)

// Добавить заказ в поставку
await addOrderToSupply()

// Убрать заказ из поставки
await removeOrderFromSupply(order)
```

## Примеры использования

### Создание поставки

```javascript
// Открыть модальное окно
openCreateSupplyModal()

// Заполнить данные
newSupply.name = 'Поставка 05.12.2025'
newSupply.description = 'Заказы для склада в Подольске'

// Создать
await createSupply()
```

### Добавление заказа в поставку

```javascript
// Открыть модальное окно с выбранным заказом
openAddToSupplyModal(order)

// Выбрать поставку
selectedSupplyId = 5

// Добавить заказ
await addOrderToSupply()
```

## Валидация

### На уровне контроллера

- Проверка прав доступа (только владельцы компании)
- Проверка существования аккаунта маркетплейса
- Проверка статуса поставки перед операциями
- Проверка статуса заказа перед добавлением
- Проверка наличия заказов перед удалением поставки

### На уровне UI

- Обязательное поле "Название поставки"
- Выбор поставки перед добавлением заказа
- Блокировка кнопок во время загрузки
- Предупреждения перед удалением

## Интеграция с WB API

### Реализовано:

#### Синхронизация поставки с WB
```
POST /api/marketplace/supplies/{supply}/sync-wb
```

Создаёт поставку в Wildberries и синхронизирует с внутренней системой:
1. Создаёт поставку в WB через API
2. Сохраняет WB ID в поле `external_supply_id`
3. Автоматически добавляет все заказы из внутренней поставки в WB поставку
4. Обновляет `supply_id` у всех заказов на WB ID

#### Получение баркода поставки
```
GET /api/marketplace/supplies/{supply}/barcode?type=png
```

Получает баркод/QR-код поставки из WB API:
- Требует, чтобы поставка была предварительно синхронизирована с WB
- Возвращает PNG или другой формат изображения
- Автоматически скачивается как файл

### UI для интеграции

На странице управления поставками ([/marketplace/{accountId}/supplies](resources/views/pages/marketplace/supplies.blade.php)):

1. **Кнопка "Sync WB"** - появляется для поставок, которые ещё не синхронизированы
   - При нажатии создаёт поставку в WB
   - Автоматически добавляет все заказы
   - После успешной синхронизации кнопка исчезает

2. **Кнопка "QR"** - появляется только для синхронизированных поставок
   - Скачивает баркод поставки в формате PNG
   - Используется для печати и наклейки на упаковку

### Как это работает

1. Пользователь создаёт поставку в системе (кнопка "Создать поставку")
2. Добавляет заказы в поставку через UI заказов
3. Когда готов отправлять на склад WB, нажимает "Sync WB"
4. Система создаёт поставку в WB и получает её ID (например, `WB-GI-12345678`)
5. Все заказы автоматически добавляются в WB поставку
6. Пользователь может скачать QR-код и наклеить на коробку
7. При сканировании на складе WB поставка автоматически принимается

### Технические детали

Используются методы из `WildberriesOrderService`:
- `createSupply(account, name)` - создание поставки
- `addOrdersToSupply(account, supplyId, orderIds)` - добавление заказов
- `getSupplyBarcode(account, supplyId, type)` - получение баркода

Поле `external_supply_id` хранит ID поставки в WB для связи между системами.

## Безопасность

- Все эндпоинты защищены middleware `auth:sanctum`
- Проверка принадлежности компании через `hasCompanyAccess()`
- Foreign key constraints на уровне БД
- Cascade delete при удалении аккаунта маркетплейса

## Производительность

- Индексы на часто используемых полях
- Eager loading для связанных моделей
- Кэширование счётчиков в таблице поставок
- Ограничение выборки только открытыми поставками

## Дата создания

05.12.2025
