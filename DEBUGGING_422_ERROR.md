# Решение ошибки 422 при добавлении аккаунта маркетплейса

## Проблема

При попытке добавить аккаунт маркетплейса через POST запрос возникает ошибка:

```
POST http://127.0.0.1:8000/api/marketplace/accounts 422 (Unprocessable Content)
```

## Причина

Код ошибки **422 (Unprocessable Entity)** означает, что запрос синтаксически правильный, но сервер не может его обработать из-за **ошибок валидации данных**.

## Наиболее вероятные причины

### 1. ❌ Отсутствие Bearer токена авторизации

**Endpoint требует авторизации!**

В файле `routes/api.php` (строка 189):
```php
Route::post('accounts', [MarketplaceAccountController::class, 'store']);
```

Этот маршрут находится внутри группы с middleware `auth:sanctum`, что означает обязательную авторизацию.

В контроллере (строка 66):
```php
if (!$request->user()->isOwnerOf($request->company_id)) {
    return response()->json(['message' => 'Только владелец может подключать маркетплейсы.'], 403);
}
```

**Решение:** Добавьте Bearer токен в заголовки запроса:

```javascript
fetch('/api/marketplace/accounts', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer YOUR_TOKEN_HERE'  // ← ОБЯЗАТЕЛЬНО!
    },
    body: JSON.stringify({...})
})
```

### 2. ❌ Неверный формат данных

Сервер ожидает:
```json
{
  "company_id": 1,
  "marketplace": "wb",
  "credentials": {
    "api_token": "eyJhbGc..."
  }
}
```

**Частые ошибки:**
- ✗ `credentials` передан как строка вместо объекта
- ✗ `credentials` пустой объект `{}`
- ✗ `company_id` передан как строка `"1"` вместо числа `1`
- ✗ `marketplace` содержит неверное значение (не `wb`, `uzum`, `ozon` или `ym`)

### 3. ❌ Несуществующий company_id

Валидация проверяет:
```php
'company_id' => ['required', 'exists:companies,id']
```

Если company с таким ID не существует в БД, запрос будет отклонён.

### 4. ❌ Неверные credentials для выбранного маркетплейса

Для каждого маркетплейса свои требования:

**Wildberries:**
- Минимум один из токенов: `api_token`, `wb_content_token`, `wb_marketplace_token`, `wb_prices_token`, `wb_statistics_token`

**Uzum Market:**
- Обязательно: `api_token` + `shop_ids` (массив чисел)

**Ozon:**
- Обязательно: `client_id` + `api_key`

**Яндекс.Маркет:**
- Обязательно: `oauth_token` + `campaign_id`

## Инструменты для диагностики

### 1. Полная тестовая страница
```
http://127.0.0.1:8000/test-marketplace-complete.html
```

**Возможности:**
- ✅ Пошаговый процесс от загрузки требований до создания аккаунта
- ✅ Визуальный конструктор credentials
- ✅ Предпросмотр JSON перед отправкой
- ✅ Детальная отладочная информация
- ✅ Показывает все запросы и ответы

### 2. Страница диагностики ошибки 422
```
http://127.0.0.1:8000/diagnose-422.html
```

**Возможности:**
- ✅ Чек-лист всех возможных причин
- ✅ Тест загрузки требований (без авторизации)
- ✅ Тест создания аккаунта (с авторизацией)
- ✅ Возможность отправить точный JSON от вашего frontend
- ✅ Примеры правильных запросов для всех маркетплейсов

### 3. Простая тестовая форма
```
http://127.0.0.1:8000/test-add-account.html
```

**Возможности:**
- ✅ Простая форма для быстрого теста
- ✅ Показывает отправленные данные и полученные ошибки

### 4. Debug скрипт
```
http://127.0.0.1:8000/debug-request.php
```

**Возможности:**
- ✅ Показывает что именно получает сервер
- ✅ Отображает все заголовки запроса
- ✅ Парсит JSON body

## Пошаговая инструкция для решения

### Шаг 1: Получите Bearer токен

```bash
# Авторизуйтесь в системе и получите токен
POST /api/auth/login
{
  "email": "your@email.com",
  "password": "password"
}

# В ответе будет:
{
  "token": "1|abc123..."
}
```

### Шаг 2: Откройте страницу диагностики

Перейдите на `http://127.0.0.1:8000/diagnose-422.html`

### Шаг 3: Вставьте ваш Bearer токен

Вставьте полученный токен в поле "Bearer Token"

### Шаг 4: Проверьте требования (Тест 1)

Нажмите кнопку "Проверить GET /api/marketplace/accounts/requirements"

Должен вернуться статус 200 с данными о требованиях.

### Шаг 5: Заполните форму и протестируйте (Тест 2)

- Укажите `company_id` (обычно 1)
- Выберите маркетплейс
- Вставьте реальный API токен маркетплейса
- Нажмите "Проверить POST /api/marketplace/accounts"

### Шаг 6: Проверьте ответ

**Если успешно (201):**
```json
{
  "message": "Маркетплейс успешно подключён!",
  "account": {
    "id": 3,
    "marketplace": "wb",
    "is_active": true
  }
}
```

**Если ошибка (422):**
```json
{
  "message": "Ошибка валидации данных",
  "errors": {
    "company_id": ["The company id field is required."]
  }
}
```

Ответ покажет **точную** причину ошибки.

## Проверка на уровне frontend

Убедитесь что ваш frontend отправляет:

```javascript
const response = await fetch('/api/marketplace/accounts', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${userToken}`  // ← Проверьте!
    },
    body: JSON.stringify({
        company_id: parseInt(companyId),  // ← Число, не строка!
        marketplace: marketplace,          // ← wb, uzum, ozon или ym
        name: name || undefined,           // ← Опционально
        credentials: credentialsObject     // ← Объект, не пустой!
    })
});
```

## Проверка в DevTools браузера

1. Откройте DevTools (F12)
2. Перейдите на вкладку **Network**
3. Отправьте запрос создания аккаунта
4. Найдите запрос `accounts` в списке
5. Проверьте:

**Вкладка Headers:**
```
Request Headers:
  Authorization: Bearer 1|abc123...  ← Есть ли токен?
  Content-Type: application/json     ← Правильный тип?
```

**Вкладка Payload/Request:**
```json
{
  "company_id": 1,           ← Число?
  "marketplace": "wb",       ← Правильное значение?
  "credentials": {           ← Объект, не пустой?
    "api_token": "..."
  }
}
```

**Вкладка Response:**
```json
{
  "message": "Ошибка валидации данных",
  "errors": {
    // Здесь будет точная причина!
  }
}
```

## Частые ошибки и их решения

| Ошибка | Причина | Решение |
|--------|---------|---------|
| `Unauthenticated` | Нет Bearer токена | Добавьте заголовок `Authorization: Bearer TOKEN` |
| `The company id field is required` | Не передан `company_id` | Добавьте `company_id: 1` в body |
| `The company id must exist in companies` | Company не существует | Проверьте ID компании в БД |
| `The marketplace field is required` | Не передан `marketplace` | Добавьте `marketplace: "wb"` |
| `The selected marketplace is invalid` | Неверное значение marketplace | Используйте: `wb`, `uzum`, `ozon` или `ym` |
| `The credentials field is required` | Нет объекта credentials | Добавьте `credentials: {...}` |
| `Для Wildberries необходимо указать хотя бы один API токен` | Пустой credentials для WB | Добавьте хотя бы `api_token` в credentials |
| `Токен имеет неправильный формат` | Короткий или невалидный токен | Проверьте формат токена (base64, минимум 20 символов) |

## Пример правильного запроса для каждого маркетплейса

### Wildberries (вариант 1 - универсальный токен)
```json
{
  "company_id": 1,
  "marketplace": "wb",
  "name": "Мой магазин WB",
  "credentials": {
    "api_token": "eyJhbGciOiJFUzI1NiIsImtpZCI6IjIwMjQxMTE1djEiLCJ0eXAiOiJKV1QifQ..."
  }
}
```

### Wildberries (вариант 2 - отдельные токены)
```json
{
  "company_id": 1,
  "marketplace": "wb",
  "credentials": {
    "wb_content_token": "eyJhbGc...",
    "wb_marketplace_token": "eyJhbGc...",
    "wb_prices_token": "eyJhbGc...",
    "wb_statistics_token": "eyJhbGc..."
  }
}
```

### Uzum Market
```json
{
  "company_id": 1,
  "marketplace": "uzum",
  "credentials": {
    "api_token": "w/77NI6IG8xzWK5sUj4An8...",
    "shop_ids": [12345, 67890]
  }
}
```

### Ozon
```json
{
  "company_id": 1,
  "marketplace": "ozon",
  "credentials": {
    "client_id": "123456",
    "api_key": "abc123-def456-ghi789"
  }
}
```

### Яндекс.Маркет
```json
{
  "company_id": 1,
  "marketplace": "ym",
  "credentials": {
    "oauth_token": "AQAAAABcdefgh12345",
    "campaign_id": "12345678"
  }
}
```

## Если ничего не помогает

1. Используйте `debug-request.php` чтобы увидеть что именно отправляет frontend
2. Проверьте логи Laravel: `storage/logs/laravel.log`
3. Включите детальную отладку в `.env`:
   ```
   APP_DEBUG=true
   ```
4. Проверьте что БД содержит записи в таблице `companies`

## Контакты для поддержки

При возникновении проблем предоставьте:
- Скриншот вкладки Network в DevTools (Headers + Payload + Response)
- Bearer токен (первые 20 символов)
- ID компании
- Выбранный маркетплейс
- Полный текст ошибки из Response
