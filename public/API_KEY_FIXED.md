# ✅ Исправлена ошибка сохранения WB токена

**Дата:** 28 ноября 2025, 20:50
**Проблема:** SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'api_key' at row 1

---

## 🔍 Причина ошибки

### Ошибка в базе данных
Поле `api_key` в таблице `marketplace_accounts` имело тип `varchar(255)`:

```sql
api_key: varchar(255)  ← Слишком мало!
```

### Почему не хватало места?

1. **Wildberries токены** имеют длину ~200-300 символов
2. **Laravel Crypt** шифрует токены перед сохранением
3. **Зашифрованный токен** получается длиной ~600+ символов
4. **varchar(255)** не может вместить 600+ символов

### Пример зашифрованного токена:
```
eyJpdiI6ImpQS0NBT2c2MXJjZE5nRGNkcGlrenc9PSIsInZhbHVlIjoiWEpFT0UxNURkN3pLNmRoZlBIaUxaV3FCdWFzTDRPUWNCaGxpeE1iN3hXaHVFLy9UaFpoQWEwdHJvL1JQcGVrNGxYQWtLVU43VTNPelFrNm1VNGxwZlhIcmhhdGNpTmdRZ3NnazhIbDlWcXEvSllwZzRlYUx2M0dHMzg3amlkMHlWL1lkcVhyQ1l6R3VRWUxyZWtZT3JrSjc0TGNrYkJnaTFnTHJGa3VzN3lyWmxyMmxmcU5qVVRKbnY4Q3p6Y2Y4U2UwbFNkZkJkZC9wcGpoRWJPLzVXNm1LTlhBV0tXL0ZORVNkRFdOMjF4MkhHcHg4b0lvaFlFZUROQzQ0eUprWHdKajNIdnk5RGllSUhSK2VVbERqZ2FLbW1qRHNWRDVrUzN2MjRZOGhwcz0iLCJtYWMiOiI0YTQ1MGI1NDFiMjJlZDk1YmY4MTcyNWU1YWQwOWRjZDI1ZmI5MzQ4YTY5MjcyNGMxMDNjYTBiZjRkNGExYjhmIiwidGFnIjoiIn0=
```

**Длина:** ~600 символов (вместо исходных ~200)

---

## ✅ Решение

### 1. Создана миграция
```bash
php artisan make:migration change_api_key_to_text_in_marketplace_accounts
```

### 2. Изменён тип поля с varchar(255) на text
```php
// database/migrations/2025_11_28_204751_change_api_key_to_text_in_marketplace_accounts.php

public function up(): void
{
    Schema::table('marketplace_accounts', function (Blueprint $table) {
        $table->text('api_key')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('marketplace_accounts', function (Blueprint $table) {
        $table->string('api_key', 255)->nullable()->change();
    });
}
```

### 3. Миграция выполнена
```bash
php artisan migrate

INFO  Running migrations.
2025_11_28_204751_change_api_key_to_text_in_marketplace_accounts  143.11ms DONE
```

### 4. Результат
```
api_key: text ✅
wb_content_token: text ✅
wb_marketplace_token: text ✅
wb_prices_token: text ✅
wb_statistics_token: text ✅
```

Теперь все WB токены имеют тип `text` и могут вместить зашифрованные значения любой длины.

---

## 📊 Валидация

Контроллер `WildberriesSettingsController.php` уже использует правильную валидацию:

```php
$validated = $request->validate([
    'api_key' => ['nullable', 'string', 'max:4000'],
    'wb_content_token' => ['nullable', 'string', 'max:4000'],
    'wb_marketplace_token' => ['nullable', 'string', 'max:4000'],
    'wb_prices_token' => ['nullable', 'string', 'max:4000'],
    'wb_statistics_token' => ['nullable', 'string', 'max:4000'],
]);
```

**max:4000** достаточно для любых зашифрованных токенов.

---

## 🎯 Результат

✅ **Поле api_key изменено на TEXT** - теперь вмещает зашифрованные токены
✅ **Валидация настроена** - max:4000 символов
✅ **Миграция выполнена** - изменения применены к базе данных
✅ **Обратная совместимость** - down() возвращает varchar(255)

---

## 📝 Как проверить

### Шаг 1: Открыть WB Settings
```
http://127.0.0.1:8000/marketplace
→ Выбрать WB аккаунт
→ Нажать "WB Settings"
```

### Шаг 2: Добавить WB токен
```
1. Вставить ваш реальный WB токен в поле "Основной API Key"
2. Нажать "Сохранить токены"
3. ✅ Должно показать "Токены успешно обновлены"
4. Токен будет зашифрован и сохранён в БД
```

### Шаг 3: Проверить API
```
1. Нажать "Проверить API"
2. ✅ Должны появиться результаты для каждой категории
```

---

## 🔍 Отладка

Если проблема повторяется, проверьте:

### 1. База данных
```bash
php artisan tinker --execute="
\$table = DB::select('DESCRIBE marketplace_accounts');
foreach (\$table as \$column) {
    if (\$column->Field === 'api_key') {
        echo \$column->Field . ': ' . \$column->Type . PHP_EOL;
    }
}
"
```

**Ожидаемый результат:**
```
api_key: text
```

### 2. Проверка миграции
```bash
php artisan migrate:status
```

Должна быть строка:
```
[2025_11_28_204751] Ran  change_api_key_to_text_in_marketplace_accounts
```

### 3. Проверка сохранённого токена
```bash
php artisan tinker --execute="
\$account = DB::table('marketplace_accounts')->where('id', 2)->first();
echo 'api_key length: ' . strlen(\$account->api_key) . PHP_EOL;
echo 'api_key (first 100 chars): ' . substr(\$account->api_key, 0, 100) . PHP_EOL;
"
```

---

## 📚 Связанные файлы

- [Migration](database/migrations/2025_11_28_204751_change_api_key_to_text_in_marketplace_accounts.php) - миграция изменения типа
- [WildberriesSettingsController.php](app/Http/Controllers/Api/WildberriesSettingsController.php) - контроллер с валидацией
- [wb-settings.blade.php](resources/views/pages/marketplace/wb-settings.blade.php) - страница настроек WB
- [test-wb-token.html](http://127.0.0.1:8000/test-wb-token.html) - тестовая страница

---

## 📊 До и После

| Параметр | До | После |
|----------|-----|--------|
| Тип поля | varchar(255) | text |
| Макс. длина | 255 символов | ~65,535 символов |
| Вмещает обычный токен | ✅ Да (~200 chars) | ✅ Да |
| Вмещает зашифрованный токен | ❌ Нет (~600 chars) | ✅ Да |
| Ошибка при сохранении | ❌ String data truncated | ✅ Сохраняется успешно |

---

**Последнее обновление:** 28.11.2025, 20:50
**Разработчик:** Claude (Anthropic)
**Статус:** ✅ ИСПРАВЛЕНО

---

## ✨ Итоги

Теперь можно сохранять WB токены любой длины. Laravel автоматически шифрует токены перед сохранением в базу, и тип `text` позволяет хранить зашифрованные значения без ограничений.

**Проблема полностью решена!** 🎉
