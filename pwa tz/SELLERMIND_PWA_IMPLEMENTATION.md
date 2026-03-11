# ТЗ для Claude Code: Реализация PWA SellerMind v2.0

**Проект:** SellerMind (sellermind.uz)
**Тип:** PWA мобильная версия
**Статус:** ✅ Дизайн утверждён

---

## ⚠️ ВАЖНЫЕ ОГРАНИЧЕНИЯ

```
❌ НЕ трогать бизнес-логику и API
❌ НЕ менять десктоп версию (.browser-only)
❌ НЕ ломать существующий функционал
✅ Только .pwa-only секции в blade файлах
✅ Только CSS/JS для PWA режима
```

---

## 📁 Структура файлов для создания/изменения

```
resources/
├── css/
│   └── pwa/
│       ├── variables.css       # CSS переменные (СОЗДАТЬ)
│       ├── base.css            # Базовые стили (СОЗДАТЬ)
│       ├── components.css      # UI компоненты (СОЗДАТЬ)
│       ├── skeleton.css        # Shimmer эффекты (СОЗДАТЬ)
│       └── pages.css           # Стили страниц (СОЗДАТЬ)
├── js/
│   └── pwa/
│       ├── auth.js             # PIN/Biometric (СОЗДАТЬ)
│       ├── cache.js            # IndexedDB кэш (СОЗДАТЬ)
│       ├── offline.js          # Offline логика (СОЗДАТЬ)
│       └── haptic.js           # Вибрация (СОЗДАТЬ)
└── views/
    └── components/
        └── pwa/
            ├── header.blade.php      # Новый header (СОЗДАТЬ)
            ├── tabbar.blade.php      # Tab bar (СОЗДАТЬ)
            ├── card.blade.php        # Карточки (СОЗДАТЬ)
            ├── skeleton.blade.php    # Skeleton (СОЗДАТЬ)
            ├── bottom-sheet.blade.php # Bottom sheet (СОЗДАТЬ)
            └── pin-screen.blade.php  # PIN экран (СОЗДАТЬ)
```

---

## 🎨 ЧАСТЬ 1: CSS Дизайн-система

### Файл: `resources/css/pwa/variables.css`

```css
/* =============================================
   SellerMind PWA Design System - Variables
   ============================================= */

:root {
    /* === Primary Colors === */
    --sm-primary: #007AFF;
    --sm-primary-light: #E5F1FF;
    --sm-primary-dark: #0056B3;
    
    /* === Background === */
    --sm-bg-primary: #F2F2F7;
    --sm-bg-secondary: #FFFFFF;
    --sm-bg-tertiary: #E5E5EA;
    
    /* === Text === */
    --sm-text-primary: #1C1C1E;
    --sm-text-secondary: #8E8E93;
    --sm-text-tertiary: #C7C7CC;
    
    /* === Status Colors === */
    --sm-success: #34C759;
    --sm-warning: #FF9500;
    --sm-error: #FF3B30;
    --sm-info: #5856D6;
    
    /* === Marketplace Colors === */
    --sm-uzum: #7B3FF2;
    --sm-wb: #CB11AB;
    --sm-ozon: #005BFF;
    --sm-ym: #FFCC00;
    
    /* === Spacing === */
    --sm-space-xs: 4px;
    --sm-space-sm: 8px;
    --sm-space-md: 12px;
    --sm-space-lg: 16px;
    --sm-space-xl: 24px;
    --sm-space-2xl: 32px;
    
    /* === Border Radius === */
    --sm-radius-sm: 8px;
    --sm-radius-md: 12px;
    --sm-radius-lg: 16px;
    --sm-radius-xl: 20px;
    --sm-radius-full: 9999px;
    
    /* === Shadows === */
    --sm-shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
    --sm-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --sm-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
    --sm-shadow-card: 0 2px 8px rgba(0,0,0,0.04), 0 0 1px rgba(0,0,0,0.08);
    
    /* === Safe Areas === */
    --sm-safe-top: env(safe-area-inset-top, 0px);
    --sm-safe-bottom: env(safe-area-inset-bottom, 0px);
    --sm-safe-left: env(safe-area-inset-left, 0px);
    --sm-safe-right: env(safe-area-inset-right, 0px);
    
    /* === Header/TabBar Heights === */
    --sm-header-height: 44px;
    --sm-tabbar-height: 56px;
}
```

### Файл: `resources/css/pwa/base.css`

```css
/* =============================================
   SellerMind PWA - Base Styles
   ============================================= */

/* Apply only in PWA mode */
.pwa-mode {
    -webkit-user-select: none;
    user-select: none;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: none;
}

.pwa-mode body {
    background: var(--sm-bg-primary);
    color: var(--sm-text-primary);
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', Roboto, sans-serif;
    padding-top: calc(var(--sm-header-height) + var(--sm-safe-top));
    padding-bottom: calc(var(--sm-tabbar-height) + var(--sm-safe-bottom));
    min-height: 100vh;
}

/* Allow text selection in inputs */
.pwa-mode input,
.pwa-mode textarea,
.pwa-mode [contenteditable] {
    -webkit-user-select: text;
    user-select: text;
}

/* Remove default margins in PWA */
.pwa-mode .container {
    max-width: 100% !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}

/* Main content area */
.pwa-mode .sm-main {
    padding: var(--sm-space-lg);
}
```

### Файл: `resources/css/pwa/components.css`

```css
/* =============================================
   SellerMind PWA - UI Components
   ============================================= */

/* === HEADER === */
.pwa-mode .sm-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: calc(var(--sm-header-height) + var(--sm-safe-top));
    padding-top: var(--sm-safe-top);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-left: var(--sm-space-lg);
    padding-right: var(--sm-space-lg);
    z-index: 100;
}

.pwa-mode .sm-header-back {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--sm-primary);
    font-size: 24px;
    text-decoration: none;
    margin-left: -12px;
}

.pwa-mode .sm-header-title {
    font-size: 17px;
    font-weight: 600;
    color: var(--sm-text-primary);
}

.pwa-mode .sm-header-action {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--sm-primary);
    font-size: 20px;
    background: none;
    border: none;
    margin-right: -12px;
}

.pwa-mode .sm-header-badge {
    background: var(--sm-error);
    color: white;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
}

/* === TAB BAR === */
.pwa-mode .sm-tabbar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(var(--sm-tabbar-height) + var(--sm-safe-bottom));
    padding-bottom: var(--sm-safe-bottom);
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 0.5px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: flex-start;
    justify-content: space-around;
    padding-top: 6px;
    z-index: 100;
}

.pwa-mode .sm-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    padding: 4px 12px;
    position: relative;
    transition: transform 0.1s;
}

.pwa-mode .sm-tab:active {
    transform: scale(0.92);
}

.pwa-mode .sm-tab-icon {
    font-size: 24px;
    margin-bottom: 2px;
    opacity: 0.4;
    transition: opacity 0.15s;
}

.pwa-mode .sm-tab.active .sm-tab-icon {
    opacity: 1;
}

.pwa-mode .sm-tab-label {
    font-size: 10px;
    color: var(--sm-text-tertiary);
    transition: color 0.15s;
}

.pwa-mode .sm-tab.active .sm-tab-label {
    color: var(--sm-primary);
    font-weight: 500;
}

.pwa-mode .sm-tab-badge {
    position: absolute;
    top: 0;
    right: 4px;
    background: var(--sm-error);
    color: white;
    font-size: 10px;
    font-weight: 600;
    min-width: 16px;
    height: 16px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}

/* === CARDS === */
.pwa-mode .sm-card {
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg);
    padding: var(--sm-space-lg);
    box-shadow: var(--sm-shadow-card);
    transition: transform 0.15s;
}

.pwa-mode .sm-card:active {
    transform: scale(0.98);
}

.pwa-mode .sm-card-gradient {
    border-radius: var(--sm-radius-xl);
    padding: var(--sm-space-xl);
    color: white;
}

.pwa-mode .sm-card-gradient.primary {
    background: linear-gradient(135deg, var(--sm-primary), #5856D6);
    box-shadow: 0 8px 32px rgba(0, 122, 255, 0.25);
}

.pwa-mode .sm-card-gradient.success {
    background: linear-gradient(135deg, var(--sm-success), #30D158);
}

/* === METRIC CARDS === */
.pwa-mode .sm-metrics {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--sm-space-md);
}

.pwa-mode .sm-metric-card {
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg);
    padding: var(--sm-space-lg);
}

.pwa-mode .sm-metric-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--sm-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--sm-space-md);
    font-size: 20px;
}

.pwa-mode .sm-metric-icon.blue { background: #E5F1FF; }
.pwa-mode .sm-metric-icon.green { background: #E8F9ED; }
.pwa-mode .sm-metric-icon.orange { background: #FFF3E5; }
.pwa-mode .sm-metric-icon.red { background: #FFEBE9; }
.pwa-mode .sm-metric-icon.purple { background: #F3E8FF; }

.pwa-mode .sm-metric-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--sm-text-primary);
}

.pwa-mode .sm-metric-label {
    font-size: 13px;
    color: var(--sm-text-secondary);
}

/* === BUTTONS === */
.pwa-mode .sm-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: var(--sm-radius-md);
    font-size: 16px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.pwa-mode .sm-btn:active {
    transform: scale(0.95);
    opacity: 0.8;
}

.pwa-mode .sm-btn-primary {
    background: var(--sm-primary);
    color: white;
}

.pwa-mode .sm-btn-secondary {
    background: var(--sm-bg-primary);
    color: var(--sm-text-primary);
}

.pwa-mode .sm-btn-danger {
    background: #FFEBE9;
    color: var(--sm-error);
}

.pwa-mode .sm-btn-block {
    width: 100%;
}

/* === SEARCH === */
.pwa-mode .sm-search {
    position: relative;
    margin-bottom: var(--sm-space-lg);
}

.pwa-mode .sm-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 16px;
    color: var(--sm-text-tertiary);
}

.pwa-mode .sm-search-input {
    width: 100%;
    padding: 12px 14px 12px 42px;
    background: var(--sm-bg-secondary);
    border: 1px solid var(--sm-bg-tertiary);
    border-radius: var(--sm-radius-md);
    font-size: 16px;
    color: var(--sm-text-primary);
}

.pwa-mode .sm-search-input::placeholder {
    color: var(--sm-text-tertiary);
}

.pwa-mode .sm-search-input:focus {
    outline: none;
    border-color: var(--sm-primary);
}

/* === FILTERS/TABS === */
.pwa-mode .sm-filter-row {
    display: flex;
    gap: var(--sm-space-sm);
    overflow-x: auto;
    margin-bottom: var(--sm-space-lg);
    padding-bottom: var(--sm-space-sm);
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.pwa-mode .sm-filter-row::-webkit-scrollbar {
    display: none;
}

.pwa-mode .sm-filter-btn {
    flex: 0 0 auto;
    padding: 8px 16px;
    background: var(--sm-bg-secondary);
    border: 1px solid var(--sm-bg-tertiary);
    border-radius: 20px;
    font-size: 14px;
    color: var(--sm-text-secondary);
    display: flex;
    align-items: center;
    gap: 6px;
}

.pwa-mode .sm-filter-btn.active {
    background: var(--sm-text-primary);
    border-color: var(--sm-text-primary);
    color: white;
}

/* === LIST ITEMS === */
.pwa-mode .sm-list {
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg);
    overflow: hidden;
}

.pwa-mode .sm-list-item {
    display: flex;
    align-items: center;
    gap: var(--sm-space-md);
    padding: var(--sm-space-lg);
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid var(--sm-bg-tertiary);
    transition: background 0.15s;
}

.pwa-mode .sm-list-item:last-child {
    border-bottom: none;
}

.pwa-mode .sm-list-item:active {
    background: var(--sm-bg-tertiary);
}

.pwa-mode .sm-list-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.pwa-mode .sm-list-content {
    flex: 1;
    min-width: 0;
}

.pwa-mode .sm-list-title {
    font-size: 16px;
    font-weight: 500;
}

.pwa-mode .sm-list-subtitle {
    font-size: 13px;
    color: var(--sm-text-secondary);
}

.pwa-mode .sm-list-chevron {
    color: var(--sm-text-tertiary);
    font-size: 18px;
}

/* === PROGRESS BAR === */
.pwa-mode .sm-progress {
    height: 6px;
    background: var(--sm-bg-tertiary);
    border-radius: 3px;
    overflow: hidden;
}

.pwa-mode .sm-progress-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}

.pwa-mode .sm-progress-bar.high { background: var(--sm-success); }
.pwa-mode .sm-progress-bar.medium { background: var(--sm-warning); }
.pwa-mode .sm-progress-bar.low { background: var(--sm-error); }

/* === STATUS BADGES === */
.pwa-mode .sm-badge {
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
}

.pwa-mode .sm-badge-new { background: #E5F1FF; color: var(--sm-primary); }
.pwa-mode .sm-badge-processing { background: #FFF3E5; color: var(--sm-warning); }
.pwa-mode .sm-badge-success { background: #E8F9ED; color: var(--sm-success); }
.pwa-mode .sm-badge-error { background: #FFEBE9; color: var(--sm-error); }

/* === SECTION TITLE === */
.pwa-mode .sm-section-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--sm-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--sm-space-md);
    padding-left: var(--sm-space-sm);
}

/* === FAB === */
.pwa-mode .sm-fab {
    position: fixed;
    bottom: calc(var(--sm-tabbar-height) + var(--sm-safe-bottom) + 16px);
    right: var(--sm-space-lg);
    width: 56px;
    height: 56px;
    background: var(--sm-primary);
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.4);
    z-index: 50;
    transition: transform 0.15s;
}

.pwa-mode .sm-fab:active {
    transform: scale(0.9);
}

/* === BOTTOM SHEET === */
.pwa-mode .sm-bottom-sheet {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s;
}

.pwa-mode .sm-bottom-sheet.visible {
    opacity: 1;
    pointer-events: auto;
}

.pwa-mode .sm-bottom-sheet-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
}

.pwa-mode .sm-bottom-sheet-content {
    position: relative;
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg) var(--sm-radius-lg) 0 0;
    padding: var(--sm-space-lg);
    padding-bottom: calc(var(--sm-space-lg) + var(--sm-safe-bottom));
    max-height: 80vh;
    overflow-y: auto;
    transform: translateY(100%);
    transition: transform 0.3s;
}

.pwa-mode .sm-bottom-sheet.visible .sm-bottom-sheet-content {
    transform: translateY(0);
}

.pwa-mode .sm-bottom-sheet-handle {
    width: 36px;
    height: 4px;
    background: var(--sm-bg-tertiary);
    border-radius: 2px;
    margin: 0 auto var(--sm-space-lg);
}

/* === MARKETPLACE COLORS === */
.pwa-mode .sm-mp-bar.uzum { background: var(--sm-uzum); }
.pwa-mode .sm-mp-bar.wb { background: var(--sm-wb); }
.pwa-mode .sm-mp-bar.ozon { background: var(--sm-ozon); }

.pwa-mode .sm-mp-icon {
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: white;
}

.pwa-mode .sm-mp-icon.uzum { background: var(--sm-uzum); }
.pwa-mode .sm-mp-icon.wb { background: var(--sm-wb); }
.pwa-mode .sm-mp-icon.ozon { background: var(--sm-ozon); }
```

### Файл: `resources/css/pwa/skeleton.css`

```css
/* =============================================
   SellerMind PWA - Skeleton Loading
   ============================================= */

/* Shimmer Animation */
@keyframes sm-shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.pwa-mode .sm-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: sm-shimmer 1.5s infinite;
}

/* Skeleton Card */
.pwa-mode .sm-skeleton-card {
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg);
    padding: var(--sm-space-lg);
    display: flex;
    gap: var(--sm-space-md);
}

.pwa-mode .sm-skeleton-avatar {
    width: 56px;
    height: 56px;
    border-radius: var(--sm-radius-md);
    flex-shrink: 0;
}

.pwa-mode .sm-skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--sm-space-sm);
}

.pwa-mode .sm-skeleton-line {
    height: 14px;
    border-radius: 4px;
}

/* Skeleton Metric */
.pwa-mode .sm-skeleton-metric {
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg);
    padding: var(--sm-space-lg);
}

.pwa-mode .sm-skeleton-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--sm-radius-md);
    margin-bottom: var(--sm-space-md);
}

.pwa-mode .sm-skeleton-value {
    height: 24px;
    width: 60%;
    border-radius: 4px;
    margin-bottom: var(--sm-space-xs);
}

.pwa-mode .sm-skeleton-label {
    height: 12px;
    width: 40%;
    border-radius: 4px;
}

/* Skeleton List */
.pwa-mode .sm-skeleton-list {
    display: flex;
    flex-direction: column;
    gap: var(--sm-space-md);
}

.pwa-mode .sm-skeleton-list-item {
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg);
    padding: var(--sm-space-lg);
    height: 80px;
}

/* Pull to Refresh */
.pwa-mode .sm-refresh-indicator {
    position: fixed;
    top: calc(var(--sm-header-height) + var(--sm-safe-top) + 10px);
    left: 50%;
    transform: translateX(-50%);
    background: var(--sm-bg-secondary);
    border-radius: 20px;
    padding: 8px 16px;
    box-shadow: var(--sm-shadow-lg);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--sm-text-secondary);
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 50;
}

.pwa-mode .sm-refresh-indicator.visible {
    opacity: 1;
}

.pwa-mode .sm-refresh-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid var(--sm-bg-tertiary);
    border-top-color: var(--sm-primary);
    border-radius: 50%;
    animation: sm-spin 0.8s linear infinite;
}

@keyframes sm-spin {
    to { transform: rotate(360deg); }
}

/* Cache Indicator */
.pwa-mode .sm-cache-badge {
    position: fixed;
    top: calc(var(--sm-header-height) + var(--sm-safe-top) + 10px);
    right: 16px;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
    z-index: 50;
}
```

---

## 🔐 ЧАСТЬ 2: Авторизация (PIN/Biometric)

### Файл: `resources/js/pwa/auth.js`

```javascript
/**
 * SellerMind PWA - Biometric Authentication
 */

class SmAuth {
    constructor() {
        this.PIN_KEY = 'sm_pin_hash';
        this.TOKEN_KEY = 'sm_auth_token';
        this.BIOMETRIC_KEY = 'sm_biometric_enabled';
    }

    // Check if biometric is available
    async isBiometricAvailable() {
        if (!window.PublicKeyCredential) return false;
        try {
            return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch {
            return false;
        }
    }

    // Hash PIN (simple hash for demo, use bcrypt in production)
    async hashPin(pin) {
        const encoder = new TextEncoder();
        const data = encoder.encode(pin + 'sm_salt_2024');
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    // Set PIN
    async setPin(pin) {
        const hash = await this.hashPin(pin);
        localStorage.setItem(this.PIN_KEY, hash);
        return true;
    }

    // Verify PIN
    async verifyPin(pin) {
        const stored = localStorage.getItem(this.PIN_KEY);
        if (!stored) return false;
        const hash = await this.hashPin(pin);
        return hash === stored;
    }

    // Check if PIN is set
    hasPinSet() {
        return !!localStorage.getItem(this.PIN_KEY);
    }

    // Enable biometric
    enableBiometric(token) {
        localStorage.setItem(this.BIOMETRIC_KEY, 'true');
        localStorage.setItem(this.TOKEN_KEY, token);
    }

    // Check if biometric enabled
    isBiometricEnabled() {
        return localStorage.getItem(this.BIOMETRIC_KEY) === 'true';
    }

    // Authenticate with biometric
    async authenticateWithBiometric() {
        if (!this.isBiometricEnabled()) {
            throw new Error('Biometric not enabled');
        }
        
        // Use Web Authentication API
        try {
            const credential = await navigator.credentials.get({
                publicKey: {
                    challenge: new Uint8Array(32),
                    timeout: 60000,
                    userVerification: 'required',
                    rpId: window.location.hostname,
                }
            });
            
            if (credential) {
                return localStorage.getItem(this.TOKEN_KEY);
            }
        } catch (e) {
            console.log('Biometric failed, falling back to PIN');
            throw e;
        }
    }

    // Clear auth data
    logout() {
        localStorage.removeItem(this.PIN_KEY);
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.BIOMETRIC_KEY);
    }
}

// Export
window.SmAuth = new SmAuth();
```

### Файл: `resources/views/components/pwa/pin-screen.blade.php`

```blade
{{-- PIN Screen Component --}}
<div x-data="pinScreen()" 
     x-show="showPinScreen" 
     x-cloak
     class="pwa-only fixed inset-0 z-[1000]"
     style="background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);">
    
    <div class="flex flex-col items-center justify-center min-h-screen px-6 py-10">
        {{-- Logo --}}
        <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mb-6 backdrop-blur">
            <span class="text-4xl">🔐</span>
        </div>
        
        {{-- Title --}}
        <h1 class="text-2xl font-semibold text-white mb-2">SellerMind</h1>
        <p class="text-white/70 mb-10" x-text="isSettingPin ? 'Создайте PIN-код' : 'Введите PIN-код'"></p>
        
        {{-- Dots --}}
        <div class="flex gap-4 mb-12">
            <template x-for="i in 4">
                <div class="w-4 h-4 rounded-full transition-all duration-150"
                     :class="{
                         'bg-white scale-110': pin.length >= i,
                         'bg-white/30': pin.length < i,
                         'bg-red-500 animate-shake': error
                     }"></div>
            </template>
        </div>
        
        {{-- Keypad --}}
        <div class="grid grid-cols-3 gap-4 w-full max-w-[280px]">
            <template x-for="num in [1,2,3,4,5,6,7,8,9]">
                <button @click="addDigit(num)" 
                        class="aspect-square rounded-full bg-white/15 text-white text-3xl font-light flex items-center justify-center backdrop-blur active:bg-white/30 active:scale-95 transition-all">
                    <span x-text="num"></span>
                </button>
            </template>
            
            {{-- Biometric --}}
            <button @click="useBiometric()" 
                    x-show="biometricAvailable && !isSettingPin"
                    class="aspect-square rounded-full bg-white/10 text-white text-3xl flex items-center justify-center active:bg-white/20 transition-all">
                👆
            </button>
            <div x-show="!biometricAvailable || isSettingPin" class="aspect-square"></div>
            
            {{-- Zero --}}
            <button @click="addDigit(0)" 
                    class="aspect-square rounded-full bg-white/15 text-white text-3xl font-light flex items-center justify-center backdrop-blur active:bg-white/30 active:scale-95 transition-all">
                0
            </button>
            
            {{-- Delete --}}
            <button @click="removeDigit()" 
                    class="aspect-square rounded-full bg-white/10 text-white text-2xl flex items-center justify-center active:bg-white/20 transition-all">
                ⌫
            </button>
        </div>
        
        {{-- Footer --}}
        <div class="mt-10">
            <a href="#" @click.prevent="forgotPin()" class="text-white/70 text-sm">
                Забыли PIN? Войти иначе
            </a>
        </div>
    </div>
</div>

<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-8px); }
    40%, 80% { transform: translateX(8px); }
}
.animate-shake { animation: shake 0.5s ease; }
</style>

<script>
function pinScreen() {
    return {
        showPinScreen: false,
        pin: '',
        error: false,
        isSettingPin: false,
        biometricAvailable: false,
        confirmPin: '',
        
        async init() {
            // Check if in PWA mode
            const isPWA = window.matchMedia('(display-mode: standalone)').matches || 
                          window.navigator.standalone;
            
            if (!isPWA) return;
            
            // Check biometric
            this.biometricAvailable = await window.SmAuth.isBiometricAvailable() && 
                                      window.SmAuth.isBiometricEnabled();
            
            // Check if need to show PIN screen
            if (window.SmAuth.hasPinSet()) {
                this.showPinScreen = true;
                
                // Try biometric first
                if (this.biometricAvailable) {
                    setTimeout(() => this.useBiometric(), 500);
                }
            }
        },
        
        addDigit(num) {
            if (this.pin.length >= 4) return;
            this.haptic();
            this.pin += num;
            
            if (this.pin.length === 4) {
                setTimeout(() => this.verify(), 200);
            }
        },
        
        removeDigit() {
            if (this.pin.length > 0) {
                this.haptic();
                this.pin = this.pin.slice(0, -1);
            }
        },
        
        async verify() {
            if (this.isSettingPin) {
                if (!this.confirmPin) {
                    this.confirmPin = this.pin;
                    this.pin = '';
                    return;
                }
                
                if (this.pin === this.confirmPin) {
                    await window.SmAuth.setPin(this.pin);
                    this.success();
                } else {
                    this.showError();
                    this.confirmPin = '';
                }
            } else {
                const valid = await window.SmAuth.verifyPin(this.pin);
                if (valid) {
                    this.success();
                } else {
                    this.showError();
                }
            }
        },
        
        async useBiometric() {
            try {
                await window.SmAuth.authenticateWithBiometric();
                this.success();
            } catch (e) {
                console.log('Biometric failed');
            }
        },
        
        showError() {
            this.error = true;
            this.haptic([50, 100, 50]);
            setTimeout(() => {
                this.error = false;
                this.pin = '';
            }, 500);
        },
        
        success() {
            this.haptic([10, 50, 10]);
            this.showPinScreen = false;
        },
        
        forgotPin() {
            window.SmAuth.logout();
            window.location.href = '/login';
        },
        
        haptic(pattern = 10) {
            if (navigator.vibrate) {
                navigator.vibrate(pattern);
            }
        }
    };
}
</script>
```

---

## 💾 ЧАСТЬ 3: Offline и кэширование

### Файл: `resources/js/pwa/cache.js`

```javascript
/**
 * SellerMind PWA - IndexedDB Cache
 */

class SmCache {
    constructor() {
        this.dbName = 'sellermind_cache';
        this.dbVersion = 1;
        this.db = null;
        
        this.stores = {
            dashboard: { keyPath: 'id', ttl: 5 * 60 * 1000 },      // 5 min
            products: { keyPath: 'id', ttl: 30 * 60 * 1000 },      // 30 min
            orders: { keyPath: 'id', ttl: 5 * 60 * 1000 },         // 5 min
            balance: { keyPath: 'sku_id', ttl: 5 * 60 * 1000 },    // 5 min
            categories: { keyPath: 'id', ttl: 24 * 60 * 60 * 1000 }, // 24 hours
            warehouses: { keyPath: 'id', ttl: 60 * 60 * 1000 },    // 1 hour
        };
    }
    
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                Object.keys(this.stores).forEach(storeName => {
                    if (!db.objectStoreNames.contains(storeName)) {
                        const store = db.createObjectStore(storeName, { 
                            keyPath: this.stores[storeName].keyPath 
                        });
                        store.createIndex('timestamp', 'timestamp', { unique: false });
                    }
                });
            };
        });
    }
    
    async get(storeName, key) {
        await this.ensureDb();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                const result = request.result;
                if (!result) {
                    resolve(null);
                    return;
                }
                
                // Check TTL
                const ttl = this.stores[storeName]?.ttl || 5 * 60 * 1000;
                if (Date.now() - result.timestamp > ttl) {
                    this.delete(storeName, key);
                    resolve(null);
                } else {
                    resolve(result.data);
                }
            };
        });
    }
    
    async set(storeName, key, data) {
        await this.ensureDb();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            
            const record = {
                [this.stores[storeName].keyPath]: key,
                data: data,
                timestamp: Date.now()
            };
            
            const request = store.put(record);
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(true);
        });
    }
    
    async getAll(storeName) {
        await this.ensureDb();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                const ttl = this.stores[storeName]?.ttl || 5 * 60 * 1000;
                const now = Date.now();
                const results = request.result
                    .filter(item => now - item.timestamp <= ttl)
                    .map(item => item.data);
                resolve(results);
            };
        });
    }
    
    async delete(storeName, key) {
        await this.ensureDb();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(key);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(true);
        });
    }
    
    async clear(storeName) {
        await this.ensureDb();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(true);
        });
    }
    
    async ensureDb() {
        if (!this.db) {
            await this.init();
        }
    }
}

// Export
window.SmCache = new SmCache();
```

### Файл: `resources/js/pwa/offline.js`

```javascript
/**
 * SellerMind PWA - Offline Support with Optimistic UI
 */

class SmOffline {
    constructor() {
        this.isOnline = navigator.onLine;
        this.listeners = [];
        
        window.addEventListener('online', () => this.setOnline(true));
        window.addEventListener('offline', () => this.setOnline(false));
    }
    
    setOnline(status) {
        this.isOnline = status;
        this.listeners.forEach(fn => fn(status));
        
        // Show toast
        if (status) {
            this.showToast('🌐 Соединение восстановлено', 'success');
        } else {
            this.showToast('📴 Нет соединения', 'warning');
        }
    }
    
    onStatusChange(callback) {
        this.listeners.push(callback);
    }
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `sm-toast sm-toast-${type}`;
        toast.innerHTML = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('visible'), 10);
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    /**
     * Fetch with cache fallback
     */
    async fetch(url, options = {}) {
        const cacheKey = options.cacheKey || url;
        const storeName = options.storeName || 'api_cache';
        
        // Try to get cached data first for optimistic UI
        let cachedData = null;
        try {
            cachedData = await window.SmCache.get(storeName, cacheKey);
        } catch (e) {}
        
        // If offline, return cached data
        if (!this.isOnline) {
            if (cachedData) {
                return { data: cachedData, fromCache: true };
            }
            throw new Error('Нет соединения и нет кэша');
        }
        
        // Fetch fresh data
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            // Cache the response
            try {
                await window.SmCache.set(storeName, cacheKey, data);
            } catch (e) {}
            
            return { data, fromCache: false };
        } catch (error) {
            // Return cached data on error
            if (cachedData) {
                return { data: cachedData, fromCache: true };
            }
            throw error;
        }
    }
}

// Export
window.SmOffline = new SmOffline();

// Toast styles
const style = document.createElement('style');
style.textContent = `
.sm-toast {
    position: fixed;
    bottom: calc(72px + env(safe-area-inset-bottom, 0px));
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1C1C1E;
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    font-size: 14px;
    z-index: 10000;
    opacity: 0;
    transition: all 0.3s ease;
}
.sm-toast.visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.sm-toast-success { background: #34C759; }
.sm-toast-warning { background: #FF9500; }
.sm-toast-error { background: #FF3B30; }
`;
document.head.appendChild(style);
```

---

## 📄 ЧАСТЬ 4: Blade компоненты

### Файл: `resources/views/components/pwa/header.blade.php`

```blade
@props([
    'title' => '',
    'backUrl' => null,
    'badge' => null,
    'actions' => null
])

<header class="pwa-only sm-header">
    {{-- Left --}}
    <div class="flex items-center">
        @if($backUrl)
            <a href="{{ $backUrl }}" class="sm-header-back" onclick="if(window.haptic) window.haptic.light()">
                ←
            </a>
        @endif
        
        <span class="sm-header-title">{{ $title }}</span>
        
        @if($badge)
            <span class="sm-header-badge">{{ $badge }}</span>
        @endif
    </div>
    
    {{-- Right --}}
    <div class="flex items-center gap-2">
        {{ $actions ?? $slot }}
    </div>
</header>
```

### Файл: `resources/views/components/pwa/tabbar.blade.php`

```blade
@props(['active' => 'home'])

<nav class="pwa-only sm-tabbar">
    <a href="/home" class="sm-tab {{ $active === 'home' ? 'active' : '' }}">
        <span class="sm-tab-icon">🏠</span>
        <span class="sm-tab-label">{{ __('admin.home') }}</span>
    </a>
    <a href="/warehouse" class="sm-tab {{ $active === 'warehouse' ? 'active' : '' }}">
        <span class="sm-tab-icon">📦</span>
        <span class="sm-tab-label">{{ __('admin.warehouse_documents') }}</span>
    </a>
    <a href="/marketplace" class="sm-tab {{ $active === 'marketplace' ? 'active' : '' }}">
        <span class="sm-tab-icon">⚡</span>
        <span class="sm-tab-label">{{ __('admin.marketplace') }}</span>
        @if(isset($newOrders) && $newOrders > 0)
            <span class="sm-tab-badge">{{ $newOrders }}</span>
        @endif
    </a>
    <a href="/sales" class="sm-tab {{ $active === 'sales' ? 'active' : '' }}">
        <span class="sm-tab-icon">🛒</span>
        <span class="sm-tab-label">{{ __('admin.sales') }}</span>
    </a>
    <a href="#" @click.prevent="$dispatch('open-more-menu')" class="sm-tab {{ $active === 'more' ? 'active' : '' }}">
        <span class="sm-tab-icon">•••</span>
        <span class="sm-tab-label">{{ __('app.settings.navigation.more') }}</span>
    </a>
</nav>
```

### Файл: `resources/views/components/pwa/skeleton.blade.php`

```blade
@props(['type' => 'card', 'count' => 3])

@if($type === 'card')
    @for($i = 0; $i < $count; $i++)
        <div class="sm-skeleton-card">
            <div class="sm-skeleton-avatar sm-shimmer"></div>
            <div class="sm-skeleton-content">
                <div class="sm-skeleton-line sm-shimmer" style="width: 70%"></div>
                <div class="sm-skeleton-line sm-shimmer" style="width: 50%"></div>
                <div class="sm-skeleton-line sm-shimmer" style="width: 30%"></div>
            </div>
        </div>
    @endfor
@elseif($type === 'metric')
    @for($i = 0; $i < $count; $i++)
        <div class="sm-skeleton-metric">
            <div class="sm-skeleton-icon sm-shimmer"></div>
            <div class="sm-skeleton-value sm-shimmer"></div>
            <div class="sm-skeleton-label sm-shimmer"></div>
        </div>
    @endfor
@elseif($type === 'list')
    @for($i = 0; $i < $count; $i++)
        <div class="sm-skeleton-list-item sm-shimmer"></div>
    @endfor
@endif
```

---

## 📱 ЧАСТЬ 5: Страницы (примеры)

### Пример: Dashboard PWA секция

```blade
{{-- resources/views/home.blade.php --}}

{{-- PWA Version --}}
<div class="pwa-only min-h-screen" 
     x-data="dashboardPwa()" 
     x-init="init()"
     style="background: var(--sm-bg-primary);">
    
    {{-- Header --}}
    <x-pwa.header title="Сегодня">
        <x-slot:actions>
            <button class="sm-header-action">
                <span x-text="$store.auth.user?.name?.charAt(0) || '👤'"></span>
            </button>
        </x-slot:actions>
    </x-pwa.header>
    
    {{-- Refresh Indicator --}}
    <div class="sm-refresh-indicator" :class="{ 'visible': refreshing }">
        <div class="sm-refresh-spinner"></div>
        Обновление...
    </div>
    
    {{-- Cache Badge --}}
    <div x-show="fromCache" class="sm-cache-badge">📦 Кэш</div>
    
    {{-- Content --}}
    <main class="sm-main">
        {{-- Revenue Card --}}
        <template x-if="!loading">
            <div class="sm-card-gradient primary mb-4">
                <p class="text-sm opacity-85 mb-2">💰 Доход сегодня</p>
                <p class="text-3xl font-bold mb-2" x-text="formatMoney(data.revenue)"></p>
                <span class="inline-flex items-center gap-1 bg-white/20 px-3 py-1 rounded-full text-sm">
                    📈 <span x-text="data.revenueChange"></span>% к вчера
                </span>
            </div>
        </template>
        
        {{-- Skeleton --}}
        <template x-if="loading">
            <div class="sm-skeleton-card mb-4 h-32 sm-shimmer rounded-xl"></div>
        </template>
        
        {{-- Metrics --}}
        <div class="sm-metrics mb-6">
            <template x-if="!loading">
                <template x-for="metric in data.metrics" :key="metric.key">
                    <div class="sm-metric-card">
                        <div class="sm-metric-icon" :class="metric.color">
                            <span x-text="metric.icon"></span>
                        </div>
                        <p class="sm-metric-value" x-text="metric.value"></p>
                        <p class="sm-metric-label" x-text="metric.label"></p>
                    </div>
                </template>
            </template>
            
            <template x-if="loading">
                <x-pwa.skeleton type="metric" :count="4" />
            </template>
        </div>
        
        {{-- Quick Actions --}}
        <p class="sm-section-title">Быстрые действия</p>
        <div class="flex gap-3 overflow-x-auto pb-2 mb-6">
            <a href="/warehouse/in/create" class="sm-quick-action">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl"
                     style="background: linear-gradient(135deg, #34C759, #30D158);">📥</div>
                <span class="text-xs text-gray-500 mt-2">Приём</span>
            </a>
            <a href="/warehouse/write-off/create" class="sm-quick-action">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl"
                     style="background: linear-gradient(135deg, #FF9500, #FF9F0A);">📤</div>
                <span class="text-xs text-gray-500 mt-2">Отгрузка</span>
            </a>
            <a href="/inventory" class="sm-quick-action">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl"
                     style="background: linear-gradient(135deg, #5856D6, #AF52DE);">📋</div>
                <span class="text-xs text-gray-500 mt-2">Инвентарь</span>
            </a>
            <a href="/scanner" class="sm-quick-action">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl"
                     style="background: linear-gradient(135deg, #007AFF, #5AC8FA);">📷</div>
                <span class="text-xs text-gray-500 mt-2">Сканер</span>
            </a>
        </div>
        
        {{-- Recent Orders --}}
        <div class="flex items-center justify-between mb-3">
            <p class="sm-section-title mb-0">Последние заказы</p>
            <a href="/marketplace" class="text-sm text-blue-500">Все →</a>
        </div>
        
        <div class="space-y-3">
            <template x-if="!loading">
                <template x-for="order in data.recentOrders" :key="order.id">
                    <a :href="'/marketplace/orders/' + order.id" class="sm-card block">
                        <div class="flex gap-3">
                            <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center text-2xl flex-shrink-0"
                                 x-text="order.productIcon || '📦'"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-5 h-5 rounded flex items-center justify-center text-[10px] font-bold text-white"
                                         :class="{
                                             'bg-purple-500': order.marketplace === 'uzum',
                                             'bg-pink-500': order.marketplace === 'wb',
                                             'bg-blue-500': order.marketplace === 'ozon'
                                         }"
                                         x-text="order.marketplace.charAt(0).toUpperCase()"></div>
                                    <span class="font-semibold" x-text="'#' + order.number"></span>
                                </div>
                                <p class="text-sm text-gray-500 truncate" x-text="order.productName"></p>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="font-semibold" x-text="formatMoney(order.total)"></span>
                                    <span class="sm-badge" 
                                          :class="{
                                              'sm-badge-new': order.status === 'new',
                                              'sm-badge-processing': order.status === 'processing',
                                              'sm-badge-success': order.status === 'shipped'
                                          }"
                                          x-text="order.statusLabel"></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </template>
            </template>
            
            <template x-if="loading">
                <x-pwa.skeleton type="card" :count="3" />
            </template>
        </div>
    </main>
    
    {{-- Tab Bar --}}
    <x-pwa.tabbar active="home" />
</div>

<script>
function dashboardPwa() {
    return {
        loading: true,
        refreshing: false,
        fromCache: false,
        data: {
            revenue: 0,
            revenueChange: 0,
            metrics: [],
            recentOrders: []
        },
        
        async init() {
            await this.load();
            this.setupPullToRefresh();
        },
        
        async load() {
            try {
                const result = await window.SmOffline.fetch('/api/dashboard', {
                    storeName: 'dashboard',
                    cacheKey: 'main'
                });
                
                this.data = result.data;
                this.fromCache = result.fromCache;
            } catch (e) {
                console.error('Failed to load dashboard:', e);
            } finally {
                this.loading = false;
                this.refreshing = false;
            }
        },
        
        async refresh() {
            this.refreshing = true;
            this.fromCache = false;
            await this.load();
        },
        
        setupPullToRefresh() {
            let startY = 0;
            document.addEventListener('touchstart', (e) => {
                if (window.scrollY === 0) startY = e.touches[0].clientY;
            });
            document.addEventListener('touchmove', (e) => {
                if (startY && e.touches[0].clientY - startY > 80 && !this.refreshing) {
                    this.refresh();
                }
            });
            document.addEventListener('touchend', () => { startY = 0; });
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        }
    };
}
</script>
```

---

## 📋 Порядок выполнения

### Фаза 1: Основа (3-4 дня)
1. [ ] Создать папки `resources/css/pwa/` и `resources/js/pwa/`
2. [ ] Создать `variables.css`, `base.css`, `components.css`, `skeleton.css`
3. [ ] Обновить `resources/css/pwa-native.css` — импортировать новые файлы
4. [ ] Создать blade компоненты в `resources/views/components/pwa/`
5. [ ] Обновить `vite.config.js` если нужно

### Фаза 2: Авторизация (2-3 дня)
1. [ ] Создать `resources/js/pwa/auth.js`
2. [ ] Создать `pin-screen.blade.php`
3. [ ] Добавить PIN экран в `layouts/app.blade.php`
4. [ ] Тестировать на реальных устройствах

### Фаза 3: Offline (2-3 дня)
1. [ ] Создать `resources/js/pwa/cache.js`
2. [ ] Создать `resources/js/pwa/offline.js`
3. [ ] Обновить service worker для кэширования API
4. [ ] Добавить индикаторы кэша на страницы

### Фаза 4: Страницы (5-7 дней)
1. [ ] Dashboard (`/home`)
2. [ ] Warehouse (`/warehouse`, `/warehouse/balance`, `/warehouse/ledger`)
3. [ ] Marketplace (`/marketplace`, `/marketplace/orders/{id}`)
4. [ ] Sales (`/sales`, `/sales/{id}`)
5. [ ] Products (`/products`)
6. [ ] Settings (`/settings`)

### Фаза 5: Тестирование (2-3 дня)
1. [ ] Тест на iPhone (Safari PWA)
2. [ ] Тест на Android (Chrome PWA)
3. [ ] Тест offline режима
4. [ ] Тест PIN/биометрии
5. [ ] Исправление багов

---

## 🔗 Ссылки на макеты

HTML макеты для референса находятся в папке `/mnt/user-data/outputs/pwa-mockups/`:
- `dashboard.html` — Главная страница
- `marketplace.html` — Список заказов маркетплейса
- `order-detail.html` — Детальная страница заказа
- `warehouse.html` — Главная склада
- `balance.html` — Остатки
- `pin-screen.html` — Экран PIN-кода

---

## ⚠️ Напоминание

```
❌ НЕ трогать:
   - app/Http/Controllers/*
   - app/Models/*
   - routes/web.php, routes/api.php
   - Десктоп версию (.browser-only)
   - Существующую бизнес-логику

✅ Только изменять:
   - resources/css/pwa/*
   - resources/js/pwa/*
   - resources/views/components/pwa/*
   - .pwa-only секции в blade файлах
```
