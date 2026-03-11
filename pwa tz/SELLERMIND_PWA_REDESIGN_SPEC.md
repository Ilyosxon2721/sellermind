# ТЗ: Полная переработка мобильной версии SellerMind

**Проект:** SellerMind PWA
**Версия:** 2.0
**Дата:** Февраль 2026

---

## 📋 Содержание

1. [Обзор проекта](#1-обзор-проекта)
2. [Дизайн-система](#2-дизайн-система)
3. [Авторизация и безопасность](#3-авторизация-и-безопасность)
4. [Offline-поддержка и кэширование](#4-offline-поддержка-и-кэширование)
5. [Структура страниц](#5-структура-страниц)
6. [UI компоненты](#6-ui-компоненты)
7. [Порядок реализации](#7-порядок-реализации)

---

## 1. Обзор проекта

### 1.1 Цели
- Создать **нативный мобильный опыт** (как родное приложение)
- Быстрая авторизация через **PIN/Face ID/Touch ID**
- **Мгновенная загрузка** страниц с кэшированными данными
- **Offline-режим** для базовых операций
- Современный **iOS/Android-like дизайн**

### 1.2 Принципы
- **Mobile-first** — дизайн сначала для телефона
- **Skeleton loading** — shimmer-эффект при загрузке
- **Optimistic UI** — показываем кэшированные данные сразу
- **Gesture-first** — свайпы, pull-to-refresh
- **Haptic feedback** — вибрация при действиях

### 1.3 Ограничения
- ❌ НЕ трогаем бизнес-логику
- ❌ НЕ меняем API endpoints
- ❌ НЕ меняем десктоп версию
- ✅ Только PWA/мобильная часть (.pwa-only секции)

---

## 2. Дизайн-система

### 2.1 Цветовая палитра

```css
:root {
  /* Primary */
  --color-primary: #007AFF;      /* iOS Blue */
  --color-primary-light: #E5F1FF;
  --color-primary-dark: #0056B3;
  
  /* Background */
  --bg-primary: #F2F2F7;         /* iOS Gray */
  --bg-secondary: #FFFFFF;
  --bg-tertiary: #E5E5EA;
  
  /* Text */
  --text-primary: #1C1C1E;
  --text-secondary: #8E8E93;
  --text-tertiary: #C7C7CC;
  
  /* Status */
  --color-success: #34C759;
  --color-warning: #FF9500;
  --color-error: #FF3B30;
  --color-info: #5856D6;
  
  /* Marketplace Colors */
  --color-uzum: #7B3FF2;
  --color-wb: #CB11AB;
  --color-ozon: #005BFF;
}
```

### 2.2 Типографика

```css
/* Заголовки */
.text-title-large { font-size: 34px; font-weight: 700; letter-spacing: -0.5px; }
.text-title { font-size: 28px; font-weight: 700; letter-spacing: -0.3px; }
.text-headline { font-size: 17px; font-weight: 600; }

/* Текст */
.text-body { font-size: 17px; font-weight: 400; }
.text-callout { font-size: 16px; font-weight: 400; }
.text-subhead { font-size: 15px; font-weight: 400; }
.text-footnote { font-size: 13px; font-weight: 400; }
.text-caption { font-size: 12px; font-weight: 400; color: var(--text-secondary); }
```

### 2.3 Отступы и радиусы

```css
/* Spacing */
--spacing-xs: 4px;
--spacing-sm: 8px;
--spacing-md: 12px;
--spacing-lg: 16px;
--spacing-xl: 24px;
--spacing-2xl: 32px;

/* Radius */
--radius-sm: 8px;
--radius-md: 12px;
--radius-lg: 16px;
--radius-xl: 20px;
--radius-full: 9999px;
```

### 2.4 Тени

```css
/* Shadows */
--shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
--shadow-md: 0 4px 12px rgba(0,0,0,0.08);
--shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
--shadow-card: 0 2px 8px rgba(0,0,0,0.04), 0 0 1px rgba(0,0,0,0.08);
```

---

## 3. Авторизация и безопасность

### 3.1 Биометрическая авторизация

**Поток:**
```
1. Первый вход → Логин/Пароль → Успешно
2. Предложение → "Включить Face ID / Touch ID?"
3. Пользователь соглашается → Сохраняем токен в Keychain
4. Следующий вход → Face ID / Touch ID → Автоматический вход
5. Если биометрия недоступна → Резервный PIN-код
```

**Экран PIN-кода:**
```
┌─────────────────────────────┐
│                             │
│         🔒 SellerMind       │
│                             │
│      Введите PIN-код        │
│                             │
│        ● ● ● ○              │
│                             │
│      ┌───┬───┬───┐          │
│      │ 1 │ 2 │ 3 │          │
│      ├───┼───┼───┤          │
│      │ 4 │ 5 │ 6 │          │
│      ├───┼───┼───┤          │
│      │ 7 │ 8 │ 9 │          │
│      ├───┼───┼───┤          │
│      │ 👆│ 0 │ ⌫ │          │
│      └───┴───┴───┘          │
│                             │
│    Забыли PIN? Войти иначе  │
│                             │
└─────────────────────────────┘

👆 = Face ID / Touch ID кнопка
```

### 3.2 Технические детали

```javascript
// Web Credentials API + WebAuthn
class BiometricAuth {
  // Проверка поддержки
  async isAvailable() {
    return window.PublicKeyCredential !== undefined;
  }
  
  // Регистрация биометрии
  async register(userId, token) {
    // Сохраняем в IndexedDB зашифрованный токен
  }
  
  // Аутентификация
  async authenticate() {
    // Запрашиваем биометрию
    // Возвращаем сохранённый токен
  }
}
```

---

## 4. Offline-поддержка и кэширование

### 4.1 Стратегия кэширования

```
┌─────────────────────────────────────────────────────────────┐
│                    CACHE STRATEGY                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │   STATIC    │    │    API      │    │   IMAGES    │     │
│  │   ASSETS    │    │    DATA     │    │             │     │
│  ├─────────────┤    ├─────────────┤    ├─────────────┤     │
│  │ Cache-First │    │ Stale-While │    │ Cache-First │     │
│  │             │    │ -Revalidate │    │             │     │
│  └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                             │
│  CSS, JS, fonts      API responses     Product images       │
│  Manifest, icons     Dashboard data    Avatars              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 IndexedDB структура

```javascript
// Таблицы для offline-данных
const DB_SCHEMA = {
  // Основные данные
  'dashboard': { keyPath: 'company_id', ttl: 5 * 60 }, // 5 мин
  'products': { keyPath: 'id', ttl: 30 * 60 },         // 30 мин
  'warehouses': { keyPath: 'id', ttl: 60 * 60 },       // 1 час
  'balance': { keyPath: 'sku_id', ttl: 5 * 60 },       // 5 мин
  
  // Справочники (редко меняются)
  'categories': { keyPath: 'id', ttl: 24 * 60 * 60 },  // 24 часа
  'counterparties': { keyPath: 'id', ttl: 60 * 60 },   // 1 час
  
  // Пользовательские настройки
  'settings': { keyPath: 'key', ttl: null },           // Без TTL
  'pin_hash': { keyPath: 'user_id', ttl: null },
};
```

### 4.3 Shimmer Loading

```html
<!-- Skeleton компонент -->
<template x-if="loading">
  <div class="skeleton-card">
    <div class="skeleton-avatar shimmer"></div>
    <div class="skeleton-lines">
      <div class="skeleton-line shimmer" style="width: 70%"></div>
      <div class="skeleton-line shimmer" style="width: 50%"></div>
    </div>
  </div>
</template>
```

```css
/* Shimmer эффект */
.shimmer {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e0e0e0 50%,
    #f0f0f0 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

### 4.4 Optimistic UI

```javascript
// Показываем кэшированные данные сразу
async function loadPage() {
  // 1. Сначала показываем кэш
  const cached = await cache.get('dashboard');
  if (cached) {
    this.data = cached;
    this.showCacheIndicator = true;
  } else {
    this.showSkeleton = true;
  }
  
  // 2. Параллельно загружаем свежие данные
  try {
    const fresh = await api.fetch('/dashboard');
    this.data = fresh;
    await cache.set('dashboard', fresh);
    this.showCacheIndicator = false;
  } catch (e) {
    if (!cached) {
      this.showOfflineMessage = true;
    }
  } finally {
    this.showSkeleton = false;
  }
}
```

---

## 5. Структура страниц

### 5.1 Главная (Dashboard)

```
┌─────────────────────────────┐
│ ← Сегодня              👤   │ ← Header
├─────────────────────────────┤
│                             │
│ 💰 Доход сегодня            │
│ ████████████████████        │
│ 12,450,000 сум     +12%     │
│                             │
├─────────────────────────────┤
│ ┌─────────┐ ┌─────────┐    │
│ │ 📦 45   │ │ 🛒 23   │    │ ← Метрики
│ │ Заказов │ │ Продаж  │    │
│ └─────────┘ └─────────┘    │
│ ┌─────────┐ ┌─────────┐    │
│ │ 📊 89%  │ │ ⚠️ 3    │    │
│ │ В наличии│ │ Мало   │    │
│ └─────────┘ └─────────┘    │
├─────────────────────────────┤
│ Быстрые действия           │
│ ┌─────┐ ┌─────┐ ┌─────┐   │
│ │ 📥  │ │ 📤  │ │ 📋  │   │
│ │Приём│ │Отгр.│ │Инвен│   │
│ └─────┘ └─────┘ └─────┘   │
├─────────────────────────────┤
│ Последние заказы           │
│ ┌───────────────────────┐  │
│ │ 🟣 Uzum #12345       │  │
│ │ iPhone Case • 45,000 │  │
│ │ Сегодня, 14:30       │  │
│ └───────────────────────┘  │
│ ┌───────────────────────┐  │
│ │ 🟣 WB #67890         │  │
│ │ Чехол Samsung • 35k  │  │
│ │ Сегодня, 13:15       │  │
│ └───────────────────────┘  │
│                             │
├─────────────────────────────┤
│ 🏠   📦   ⚡   🛒   •••   │ ← Tab Bar
└─────────────────────────────┘
```

### 5.2 Склад (Warehouse)

```
┌─────────────────────────────┐
│ ← Склад               🏭    │
├─────────────────────────────┤
│                             │
│ ┌─────────────────────────┐│
│ │ 🔍 Поиск SKU...         ││ ← Поиск
│ └─────────────────────────┘│
│                             │
├─────────────────────────────┤
│ Разделы                     │
│                             │
│ ┌───────────────────────┐  │
│ │ 📊 Остатки        →   │  │
│ │ 1,234 позиции         │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ 📥 Приёмки        →   │  │
│ │ 3 документа в работе  │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ 📋 Журнал         →   │  │
│ │ Все движения          │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ 🔒 Резервы        →   │  │
│ │ 45 позиций            │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ 🗑️ Списания       →   │  │
│ │ Брак и потери         │  │
│ └───────────────────────┘  │
│                             │
├─────────────────────────────┤
│ 🏠   📦   ⚡   🛒   •••   │
└─────────────────────────────┘
```

### 5.3 Остатки (Balance)

```
┌─────────────────────────────┐
│ ← Остатки             🔄    │
├─────────────────────────────┤
│                             │
│ ┌───────────┐ ┌───────────┐│
│ │ Склад     ▼│ │Категория ▼││ ← Фильтры
│ └───────────┘ └───────────┘│
│                             │
│ ┌─────────────────────────┐│
│ │ 🔍 SKU или штрихкод     ││
│ └─────────────────────────┘│
│                             │
├─────────────────────────────┤
│ 1,234 позиции • 567,890 шт │
├─────────────────────────────┤
│                             │
│ ┌───────────────────────┐  │
│ │ iPhone Case Black     │  │
│ │ SKU: IP-CASE-001      │  │
│ │ ████████░░  120 шт    │  │ ← Progress bar
│ │ Резерв: 15            │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ Samsung Charger       │  │
│ │ SKU: SAM-CHR-002      │  │
│ │ ██░░░░░░░░  8 шт ⚠️   │  │ ← Мало
│ │ Резерв: 3             │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ USB Cable Type-C      │  │
│ │ SKU: USB-TC-003       │  │
│ │ ██████████  500+ шт   │  │
│ │ Резерв: 45            │  │
│ └───────────────────────┘  │
│                             │
│          [Загрузить ещё]    │
│                             │
├─────────────────────────────┤
│ 🏠   📦   ⚡   🛒   •••   │
└─────────────────────────────┘
```

### 5.4 Заказы (Marketplace)

```
┌─────────────────────────────┐
│ ← Маркетплейс         🔔    │
├─────────────────────────────┤
│                             │
│ ┌─────┐┌─────┐┌─────┐┌────┐│
│ │ Все ││Uzum ││ WB  ││Ozon││ ← Tabs
│ └─────┘└─────┘└─────┘└────┘│
│                             │
│ ┌─────────────────────────┐│
│ │ 🔍 Номер заказа...      ││
│ └─────────────────────────┘│
│                             │
├─────────────────────────────┤
│ Сегодня • 23 заказа        │
├─────────────────────────────┤
│                             │
│ ┌───────────────────────┐  │
│ │ 🟣 #SM-123456         │  │
│ │ ┌────┐                │  │
│ │ │ 📷 │ iPhone Case    │  │
│ │ └────┘ × 2 шт         │  │
│ │                       │  │
│ │ 💰 89,000 сум         │  │
│ │ 📍 Ташкент            │  │
│ │ ⏱️ Новый • 14:30      │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ 🟣 #SM-123455         │  │
│ │ ┌────┐                │  │
│ │ │ 📷 │ Samsung Case   │  │
│ │ └────┘ × 1 шт         │  │
│ │                       │  │
│ │ 💰 45,000 сум         │  │
│ │ 📍 Самарканд          │  │
│ │ ✅ Отгружен • 12:00   │  │
│ └───────────────────────┘  │
│                             │
├─────────────────────────────┤
│ 🏠   📦   ⚡   🛒   •••   │
└─────────────────────────────┘
```

### 5.5 Карточка заказа

```
┌─────────────────────────────┐
│ ← Заказ #SM-123456    📋    │
├─────────────────────────────┤
│                             │
│      🟣 UZUM MARKET         │
│      Заказ #SM-123456       │
│                             │
│  ┌─────────────────────┐   │
│  │     ⏳ НОВЫЙ        │   │ ← Status badge
│  └─────────────────────┘   │
│                             │
├─────────────────────────────┤
│ Товары                      │
│                             │
│ ┌─────────────────────────┐│
│ │ ┌────┐ iPhone Case     ││
│ │ │ 📷 │ SKU: IP-001     ││
│ │ └────┘ 44,500 × 2 шт   ││
│ │        = 89,000 сум    ││
│ └─────────────────────────┘│
│                             │
├─────────────────────────────┤
│ Доставка                    │
│                             │
│ 📍 Ташкент, Чиланзар       │
│    ул. Катартал, 15/23     │
│                             │
│ 👤 Иванов Иван             │
│ 📞 +998 90 123 45 67       │
│                             │
├─────────────────────────────┤
│ Итого                       │
│                             │
│ Товары:         89,000 сум │
│ Доставка:       15,000 сум │
│ ─────────────────────────  │
│ ИТОГО:         104,000 сум │
│                             │
├─────────────────────────────┤
│                             │
│ ┌─────────────────────────┐│
│ │    ✅ Собрать заказ     ││ ← Primary action
│ └─────────────────────────┘│
│                             │
│ ┌───────────┐ ┌───────────┐│
│ │ 📞 Звонок │ │ ❌ Отмена ││ ← Secondary
│ └───────────┘ └───────────┘│
│                             │
└─────────────────────────────┘
```

### 5.6 Продажи (Sales)

```
┌─────────────────────────────┐
│ ← Продажи         ＋ Новая  │
├─────────────────────────────┤
│                             │
│ ┌──────────────────────────┐
│ │ 💰 Сегодня: 2,450,000   │
│ │ 📦 12 продаж            │
│ └──────────────────────────┘
│                             │
│ ┌─────┐┌─────┐┌─────┐┌────┐│
│ │Все  ││МП   ││Инст ││Опт ││
│ └─────┘└─────┘└─────┘└────┘│
│                             │
├─────────────────────────────┤
│                             │
│ ┌───────────────────────┐  │
│ │ 🟣 Маркетплейс        │  │
│ │ #SALE-001 • Uzum      │  │
│ │ 💰 89,000 сум         │  │
│ │ ✅ Оплачено • 14:30   │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ 📸 Инстаграм          │  │
│ │ #SALE-002 • @client   │  │
│ │ 💰 125,000 сум        │  │
│ │ ⏳ Ожидает • 13:00    │  │
│ └───────────────────────┘  │
│                             │
│ ┌───────────────────────┐  │
│ │ 🏪 Оптовая            │  │
│ │ #SALE-003 • ООО "Рога"│  │
│ │ 💰 1,500,000 сум      │  │
│ │ 💳 В долг • 12:00     │  │
│ └───────────────────────┘  │
│                             │
├─────────────────────────────┤
│ 🏠   📦   ⚡   🛒   •••   │
└─────────────────────────────┘
```

### 5.7 Настройки / Профиль

```
┌─────────────────────────────┐
│ ← Настройки                 │
├─────────────────────────────┤
│                             │
│      ┌────────────┐        │
│      │    👤      │        │
│      │   Фото     │        │
│      └────────────┘        │
│      Иван Иванов           │
│      ivan@company.uz       │
│                             │
├─────────────────────────────┤
│ БЕЗОПАСНОСТЬ               │
│                             │
│ ┌───────────────────────┐  │
│ │ 🔐 Face ID        ✅  │  │
│ └───────────────────────┘  │
│ ┌───────────────────────┐  │
│ │ 🔢 PIN-код       →    │  │
│ └───────────────────────┘  │
│ ┌───────────────────────┐  │
│ │ 🔑 Сменить пароль →   │  │
│ └───────────────────────┘  │
│                             │
├─────────────────────────────┤
│ ПРИЛОЖЕНИЕ                 │
│                             │
│ ┌───────────────────────┐  │
│ │ 🌙 Тёмная тема   ○    │  │
│ └───────────────────────┘  │
│ ┌───────────────────────┐  │
│ │ 🔔 Уведомления   ✅   │  │
│ └───────────────────────┘  │
│ ┌───────────────────────┐  │
│ │ 📱 Offline-режим →    │  │
│ └───────────────────────┘  │
│                             │
├─────────────────────────────┤
│ КОМПАНИЯ                   │
│                             │
│ ┌───────────────────────┐  │
│ │ 🏢 Профиль компании → │  │
│ └───────────────────────┘  │
│ ┌───────────────────────┐  │
│ │ 👥 Сотрудники     →   │  │
│ └───────────────────────┘  │
│ ┌───────────────────────┐  │
│ │ 🔗 Интеграции     →   │  │
│ └───────────────────────┘  │
│                             │
├─────────────────────────────┤
│                             │
│ ┌───────────────────────┐  │
│ │  🚪 Выйти из аккаунта │  │
│ └───────────────────────┘  │
│                             │
│       Версия 2.0.0         │
│                             │
└─────────────────────────────┘
```

---

## 6. UI компоненты

### 6.1 Карточки

```html
<!-- Базовая карточка -->
<div class="sm-card">
  <div class="sm-card-content">
    <!-- Content -->
  </div>
</div>

<!-- Карточка с иконкой -->
<div class="sm-card sm-card-icon">
  <div class="sm-card-icon-wrapper bg-blue-100">
    <svg class="text-blue-600">...</svg>
  </div>
  <div class="sm-card-body">
    <p class="sm-card-title">Заголовок</p>
    <p class="sm-card-subtitle">Подзаголовок</p>
  </div>
  <svg class="sm-card-chevron">→</svg>
</div>

<!-- Метрика -->
<div class="sm-metric-card">
  <div class="sm-metric-icon bg-green-100">
    <svg class="text-green-600">...</svg>
  </div>
  <p class="sm-metric-value">1,234</p>
  <p class="sm-metric-label">Заказов</p>
  <span class="sm-metric-badge sm-badge-success">+12%</span>
</div>
```

### 6.2 Списки

```html
<!-- Список с разделителями -->
<div class="sm-list">
  <div class="sm-list-item">
    <div class="sm-list-icon">📦</div>
    <div class="sm-list-content">
      <p class="sm-list-title">Название</p>
      <p class="sm-list-subtitle">Описание</p>
    </div>
    <div class="sm-list-accessory">→</div>
  </div>
</div>

<!-- Список товаров -->
<div class="sm-product-list">
  <div class="sm-product-item">
    <img class="sm-product-image" src="..." />
    <div class="sm-product-info">
      <p class="sm-product-name">iPhone Case</p>
      <p class="sm-product-sku">SKU: IP-001</p>
      <p class="sm-product-price">45,000 сум</p>
    </div>
    <div class="sm-product-quantity">×2</div>
  </div>
</div>
```

### 6.3 Кнопки

```html
<!-- Primary -->
<button class="sm-btn sm-btn-primary">Подтвердить</button>

<!-- Secondary -->
<button class="sm-btn sm-btn-secondary">Отмена</button>

<!-- Danger -->
<button class="sm-btn sm-btn-danger">Удалить</button>

<!-- Icon Button -->
<button class="sm-btn-icon">
  <svg>...</svg>
</button>

<!-- FAB (Floating Action Button) -->
<button class="sm-fab">
  <svg>+</svg>
</button>
```

### 6.4 Формы

```html
<!-- Input -->
<div class="sm-input-group">
  <label class="sm-label">Email</label>
  <input class="sm-input" type="email" placeholder="example@mail.com" />
  <p class="sm-input-error">Некорректный email</p>
</div>

<!-- Select -->
<div class="sm-select-group">
  <label class="sm-label">Склад</label>
  <select class="sm-select">
    <option>Основной склад</option>
  </select>
</div>

<!-- Search -->
<div class="sm-search">
  <svg class="sm-search-icon">🔍</svg>
  <input class="sm-search-input" placeholder="Поиск..." />
  <button class="sm-search-clear">×</button>
</div>
```

### 6.5 Навигация

```html
<!-- Header -->
<header class="sm-header">
  <button class="sm-header-back">←</button>
  <h1 class="sm-header-title">Заголовок</h1>
  <button class="sm-header-action">⚙️</button>
</header>

<!-- Tab Bar -->
<nav class="sm-tabbar">
  <a class="sm-tabbar-item active">
    <svg>🏠</svg>
    <span>Главная</span>
  </a>
  <a class="sm-tabbar-item">
    <svg>📦</svg>
    <span>Склад</span>
  </a>
</nav>

<!-- Segment Control -->
<div class="sm-segment">
  <button class="sm-segment-item active">Все</button>
  <button class="sm-segment-item">Uzum</button>
  <button class="sm-segment-item">WB</button>
</div>
```

### 6.6 Skeleton Loading

```html
<!-- Skeleton Card -->
<div class="sm-skeleton-card">
  <div class="sm-skeleton-avatar shimmer"></div>
  <div class="sm-skeleton-content">
    <div class="sm-skeleton-line shimmer" style="width: 70%"></div>
    <div class="sm-skeleton-line shimmer" style="width: 50%"></div>
  </div>
</div>

<!-- Skeleton List -->
<div class="sm-skeleton-list">
  <div class="sm-skeleton-item shimmer"></div>
  <div class="sm-skeleton-item shimmer"></div>
  <div class="sm-skeleton-item shimmer"></div>
</div>
```

### 6.7 Модальные окна

```html
<!-- Bottom Sheet -->
<div class="sm-bottom-sheet">
  <div class="sm-bottom-sheet-handle"></div>
  <div class="sm-bottom-sheet-content">
    <!-- Content -->
  </div>
</div>

<!-- Alert -->
<div class="sm-alert">
  <div class="sm-alert-content">
    <h3 class="sm-alert-title">Удалить?</h3>
    <p class="sm-alert-message">Это действие нельзя отменить</p>
  </div>
  <div class="sm-alert-actions">
    <button class="sm-alert-btn">Отмена</button>
    <button class="sm-alert-btn sm-alert-btn-danger">Удалить</button>
  </div>
</div>

<!-- Toast -->
<div class="sm-toast sm-toast-success">
  <svg>✅</svg>
  <span>Сохранено успешно</span>
</div>
```

---

## 7. Порядок реализации

### Фаза 1: Основа (1-2 недели)
1. ✅ Дизайн-система (CSS переменные, компоненты)
2. ✅ Skeleton компоненты
3. ✅ Новый Header и Tab Bar
4. ✅ Базовые карточки и списки

### Фаза 2: Авторизация (1 неделя)
1. ✅ PIN-код экран
2. ✅ Биометрическая авторизация
3. ✅ Сохранение сессии
4. ✅ Безопасное хранение токена

### Фаза 3: Offline (1 неделя)
1. ✅ Service Worker настройка
2. ✅ IndexedDB структура
3. ✅ Кэширование API
4. ✅ Optimistic UI

### Фаза 4: Страницы (2-3 недели)
1. ✅ Dashboard
2. ✅ Warehouse (все подстраницы)
3. ✅ Marketplace / Заказы
4. ✅ Sales / Продажи
5. ✅ Products / Товары
6. ✅ Settings / Настройки

### Фаза 5: Полировка (1 неделя)
1. ✅ Анимации и переходы
2. ✅ Pull-to-refresh
3. ✅ Haptic feedback
4. ✅ Тестирование на устройствах

---

## 📎 Приложения

### A. Файловая структура

```
resources/
├── css/
│   ├── pwa/
│   │   ├── variables.css      # CSS переменные
│   │   ├── components.css     # UI компоненты
│   │   ├── skeleton.css       # Shimmer эффекты
│   │   ├── animations.css     # Анимации
│   │   └── utilities.css      # Утилиты
│   └── pwa-native.css         # Главный файл (импорты)
├── js/
│   ├── pwa/
│   │   ├── auth.js            # Биометрия и PIN
│   │   ├── cache.js           # IndexedDB и кэш
│   │   ├── api.js             # API с offline
│   │   └── haptic.js          # Вибрация
│   ├── pwa-detector.js
│   └── pwa.js
└── views/
    └── components/
        ├── pwa/
        │   ├── header.blade.php
        │   ├── tabbar.blade.php
        │   ├── card.blade.php
        │   ├── skeleton.blade.php
        │   └── bottom-sheet.blade.php
        └── ...
```

### B. Ссылки на референсы

- Apple Human Interface Guidelines: https://developer.apple.com/design/human-interface-guidelines/
- Material Design 3: https://m3.material.io/
- iOS UI Kit (Figma): https://www.figma.com/community/file/ios-ui-kit

---

## ✅ Чеклист для утверждения

- [ ] Цветовая палитра одобрена
- [ ] Типографика одобрена
- [ ] Структура страниц одобрена
- [ ] Компоненты одобрены
- [ ] Поток авторизации одобрен
- [ ] Стратегия кэширования одобрена

---

**После утверждения дизайна** → Создам детальное ТЗ для реализации каждой страницы.
