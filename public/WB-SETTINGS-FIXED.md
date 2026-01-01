# ✅ Исправлена ошибка Unauthenticated в WB Settings

**Дата:** 28 ноября 2025, 21:15
**Проблема:** Не удается сохранить токен в настройках Wildberries - ошибка "Unauthenticated"

---

## 🔍 Причина ошибки

В файле `wb-settings.blade.php` использовались **неправильные ключи localStorage**:

```javascript
// ❌ БЫЛО (неправильно):
const token = localStorage.getItem('auth_token') || localStorage.getItem('token');
```

Эта схема не учитывала:
1. **Alpine persist** сохраняет токен как `_x_auth_token` (JSON-обёрнутый)
2. **Alpine store** хранит токен в `$store.auth.token`
3. Нужна проверка всех вариантов с правильным приоритетом

---

## ✅ Решение

Добавлены те же функции `getToken()` и `getAuthHeaders()`, что и в других страницах marketplace:

```javascript
getToken() {
    // 1. Пробуем Alpine store
    if (this.$store.auth.token) return this.$store.auth.token;

    // 2. Пробуем Alpine persist format (_x_auth_token)
    const persistToken = localStorage.getItem('_x_auth_token');
    if (persistToken) {
        try {
            return JSON.parse(persistToken);
        } catch (e) {
            return persistToken;
        }
    }

    // 3. Fallback на старые ключи
    return localStorage.getItem('auth_token') || localStorage.getItem('token');
},

getAuthHeaders() {
    return {
        'Authorization': 'Bearer ' + this.getToken(),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
}
```

---

## 📝 Изменения в wb-settings.blade.php

### 1. Init function
```javascript
// ✅ СТАЛО (правильно):
async init() {
    await this.$nextTick(); // Ждём готовности Alpine

    if (!this.getToken()) {
        console.log('No token found, redirecting to login');
        window.location.href = '/login';
        return;
    }
    await this.loadSettings();
}
```

### 2. loadSettings()
```javascript
// ✅ СТАЛО:
async loadSettings() {
    this.loading = true;
    try {
        const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/settings', {
            headers: this.getAuthHeaders() // Правильные headers
        });

        if (res.ok) {
            const data = await res.json();
            this.account = data.account;
        } else if (res.status === 401) {
            console.error('Unauthorized');
            window.location.href = '/login'; // Редирект на login
        } else if (res.status === 400) {
            alert('Этот аккаунт не является Wildberries');
            window.location.href = '/marketplace/{{ $accountId }}';
        }
    } catch (e) {
        console.error('Error loading settings:', e);
    }
    this.loading = false;
}
```

### 3. saveSettings()
```javascript
// ✅ СТАЛО:
async saveSettings() {
    this.saving = true;
    try {
        const payload = {};
        Object.keys(this.form).forEach(key => {
            if (this.form[key] !== '') {
                payload[key] = this.form[key];
            }
        });

        console.log('Saving WB settings:', payload); // Debug log

        const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/settings', {
            method: 'PUT',
            headers: this.getAuthHeaders(), // Правильные headers
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (res.ok) {
            this.form = {
                api_key: '',
                wb_content_token: '',
                wb_marketplace_token: '',
                wb_prices_token: '',
                wb_statistics_token: ''
            };
            await this.loadSettings();
            alert('Токены успешно обновлены');
        } else {
            console.error('Error response:', res.status, data);
            let errorMsg = data.message || 'Ошибка сохранения';
            if (data.errors) {
                const errorList = Object.values(data.errors).flat();
                errorMsg += ':\n' + errorList.join('\n');
            }
            alert(errorMsg);
        }
    } catch (e) {
        console.error('Error saving settings:', e);
        alert('Ошибка сохранения настроек: ' + e.message);
    }
    this.saving = false;
}
```

### 4. testConnection()
```javascript
// ✅ СТАЛО:
async testConnection() {
    this.testing = true;
    this.testResults = null;
    try {
        const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/test', {
            method: 'POST',
            headers: this.getAuthHeaders() // Правильные headers
        });
        const data = await res.json();
        this.testResults = data;
        await this.loadSettings();
    } catch (e) {
        console.error('Error testing connection:', e);
        this.testResults = { success: false, error: 'Network error' };
    }
    this.testing = false;
}
```

---

## 🎯 Результат

Теперь WB Settings работает идентично другим страницам marketplace:

✅ **Правильное получение токена** - проверяет все возможные ключи
✅ **Правильные headers** - через `getAuthHeaders()`
✅ **Ожидание Alpine** - `await this.$nextTick()`
✅ **Обработка 401** - редирект на `/login`
✅ **Debug логи** - `console.log` для отладки

---

## 📋 Как проверить

### Шаг 1: Очистить localStorage (если ещё не сделано)
```
http://127.0.0.1:8000/diagnostic.html
→ "Проверить localStorage"
→ "Очистить и исправить" (если есть ошибки)
```

### Шаг 2: Заново войти
```
Email: admin@sellermind.ai
Password: password
```

### Шаг 3: Открыть WB Settings
```
http://127.0.0.1:8000/marketplace
→ Выбрать WB аккаунт
→ Нажать "WB Settings" (фиолетовая карточка)
```

### Шаг 4: Добавить WB токен
```
1. Вставить ваш реальный WB токен в поле "Основной API Key"
2. Нажать "Сохранить токены"
3. ✅ Должно показать "Токены успешно обновлены"
4. Нажать "Проверить API"
5. ✅ Должны появиться результаты для каждой категории
```

---

## 🔍 Отладка

Если всё ещё получаете Unauthenticated:

### Проверьте консоль браузера (F12):
```javascript
// Должно быть:
console.log('Saving WB settings:', payload)
console.log('Loading accounts for company:', companyId)

// Не должно быть:
'No token found, redirecting to login'
'Unauthorized - token may be invalid'
```

### Проверьте localStorage:
```javascript
// В консоли браузера:
console.log('Alpine persist:', localStorage.getItem('_x_auth_token'));
console.log('Auth token:', localStorage.getItem('auth_token'));
console.log('Alpine store:', Alpine.store('auth').token);
```

### Проверьте запрос в Network tab:
```
Request URL: http://127.0.0.1:8000/api/marketplace/wb/accounts/2/settings
Request Method: PUT
Request Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOi... ✅
  Content-Type: application/json ✅
  Accept: application/json ✅
```

---

## 📊 Итоги

| Компонент | До | После |
|-----------|-----|-------|
| getToken() | ❌ Не было | ✅ Добавлено |
| getAuthHeaders() | ❌ Не было | ✅ Добавлено |
| $nextTick() | ❌ Не было | ✅ Добавлено |
| 401 handling | ❌ Не было | ✅ Добавлено |
| Debug logs | ❌ Не было | ✅ Добавлено |

**Статус:** ✅ ИСПРАВЛЕНО

---

## 🔗 Связанные файлы

- [wb-settings.blade.php](resources/views/pages/marketplace/wb-settings.blade.php) - исправлен
- [index.blade.php](resources/views/pages/marketplace/index.blade.php) - аналогичные функции
- [show.blade.php](resources/views/pages/marketplace/show.blade.php) - аналогичные функции
- [products.blade.php](resources/views/pages/marketplace/products.blade.php) - аналогичные функции
- [orders.blade.php](resources/views/pages/marketplace/orders.blade.php) - аналогичные функции

---

**Последнее обновление:** 28.11.2025, 21:15
**Разработчик:** Claude (Anthropic)
**Статус:** ✅ ГОТОВО
