# SellerMind Store Builder - Модуль создания интернет-магазинов

## 📋 Обзор проекта

**Цель:** Дать пользователям SellerMind возможность создать собственный интернет-магазин с кастомным дизайном, который автоматически синхронизируется с их товарами, остатками и заказами.

**Аналоги:** Shopify, Ecwid, InSales, Tilda Shop

---

## 🎯 Ключевые функции

### Для владельца магазина (пользователь SellerMind):
1. **Конструктор магазина** - визуальный редактор без кода
2. **Выбор шаблона** - готовые темы оформления
3. **Кастомизация дизайна** - цвета, шрифты, логотип, баннеры
4. **Управление каталогом** - какие товары показывать
5. **Настройка доставки** - зоны, тарифы, самовывоз
6. **Настройка оплаты** - Click, Payme, наличные
7. **Собственный домен** - store.sellermind.uz/username или свой домен
8. **Аналитика** - посещения, конверсии, популярные товары

### Для покупателя (посетитель магазина):
1. **Каталог товаров** - с фильтрами и поиском
2. **Карточка товара** - фото, описание, варианты, цена
3. **Корзина** - добавление, изменение количества
4. **Оформление заказа** - без регистрации или с аккаунтом
5. **Оплата онлайн** - Click, Payme
6. **Отслеживание заказа** - статус доставки
7. **PWA** - установка как приложение

---

## 🏗️ Архитектура

### Структура URL:
```
store.sellermind.uz/{store_slug}           - Главная магазина
store.sellermind.uz/{store_slug}/catalog   - Каталог
store.sellermind.uz/{store_slug}/product/{slug} - Товар
store.sellermind.uz/{store_slug}/cart      - Корзина
store.sellermind.uz/{store_slug}/checkout  - Оформление
store.sellermind.uz/{store_slug}/order/{id} - Статус заказа

# Или свой домен:
mystore.uz/...
```

### Таблицы БД:

```sql
-- Магазины пользователей
CREATE TABLE stores (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    
    -- Основное
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    logo VARCHAR(500),
    favicon VARCHAR(500),
    
    -- Домен
    custom_domain VARCHAR(255) UNIQUE,
    domain_verified BOOLEAN DEFAULT FALSE,
    ssl_enabled BOOLEAN DEFAULT FALSE,
    
    -- Настройки
    is_active BOOLEAN DEFAULT TRUE,
    is_published BOOLEAN DEFAULT FALSE,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    
    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    
    -- Контакты
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    working_hours JSON,
    
    -- Соцсети
    instagram VARCHAR(255),
    telegram VARCHAR(255),
    facebook VARCHAR(255),
    
    -- Настройки магазина
    currency VARCHAR(10) DEFAULT 'UZS',
    min_order_amount DECIMAL(15,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_custom_domain (custom_domain)
);

-- Темы/дизайн магазина
CREATE TABLE store_themes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    
    -- Шаблон
    template VARCHAR(50) DEFAULT 'default', -- default, minimal, boutique, tech, grocery
    
    -- Цвета
    primary_color VARCHAR(7) DEFAULT '#007AFF',
    secondary_color VARCHAR(7) DEFAULT '#5856D6',
    accent_color VARCHAR(7) DEFAULT '#FF9500',
    background_color VARCHAR(7) DEFAULT '#FFFFFF',
    text_color VARCHAR(7) DEFAULT '#1C1C1E',
    
    -- Шрифты
    heading_font VARCHAR(100) DEFAULT 'Inter',
    body_font VARCHAR(100) DEFAULT 'Inter',
    
    -- Хедер
    header_style VARCHAR(20) DEFAULT 'default', -- default, centered, minimal
    header_bg_color VARCHAR(7) DEFAULT '#FFFFFF',
    header_text_color VARCHAR(7) DEFAULT '#1C1C1E',
    show_search BOOLEAN DEFAULT TRUE,
    show_cart BOOLEAN DEFAULT TRUE,
    show_phone BOOLEAN DEFAULT TRUE,
    
    -- Главная страница
    hero_enabled BOOLEAN DEFAULT TRUE,
    hero_title VARCHAR(255),
    hero_subtitle TEXT,
    hero_image VARCHAR(500),
    hero_button_text VARCHAR(100) DEFAULT 'Смотреть каталог',
    hero_button_url VARCHAR(255) DEFAULT '/catalog',
    
    -- Каталог
    products_per_page INT DEFAULT 12,
    product_card_style VARCHAR(20) DEFAULT 'default', -- default, minimal, detailed
    show_quick_view BOOLEAN DEFAULT TRUE,
    show_add_to_cart BOOLEAN DEFAULT TRUE,
    
    -- Футер
    footer_style VARCHAR(20) DEFAULT 'default',
    footer_bg_color VARCHAR(7) DEFAULT '#1C1C1E',
    footer_text_color VARCHAR(7) DEFAULT '#FFFFFF',
    footer_text TEXT,
    
    -- Кастомный CSS
    custom_css TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

-- Баннеры магазина
CREATE TABLE store_banners (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    
    title VARCHAR(255),
    subtitle TEXT,
    image VARCHAR(500) NOT NULL,
    image_mobile VARCHAR(500),
    url VARCHAR(500),
    button_text VARCHAR(100),
    
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Период показа
    start_date DATE,
    end_date DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_store_active (store_id, is_active)
);

-- Категории магазина (связь с категориями SellerMind)
CREATE TABLE store_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL, -- из таблицы categories SellerMind
    
    -- Переопределение для магазина
    custom_name VARCHAR(255),
    custom_description TEXT,
    custom_image VARCHAR(500),
    
    position INT DEFAULT 0,
    is_visible BOOLEAN DEFAULT TRUE,
    show_in_menu BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_category (store_id, category_id)
);

-- Товары магазина (связь с товарами SellerMind)
CREATE TABLE store_products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL, -- из таблицы products SellerMind
    
    -- Переопределение для магазина
    custom_name VARCHAR(255),
    custom_description TEXT,
    custom_price DECIMAL(15,2), -- если отличается от основной цены
    
    -- Настройки отображения
    is_visible BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE, -- показывать на главной
    position INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_product (store_id, product_id),
    INDEX idx_featured (store_id, is_featured, is_visible)
);

-- Способы доставки магазина
CREATE TABLE store_delivery_methods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50) NOT NULL, -- pickup, courier, post, cdek, express
    
    -- Стоимость
    price DECIMAL(15,2) DEFAULT 0,
    free_from DECIMAL(15,2), -- бесплатно от суммы
    
    -- Сроки
    min_days INT DEFAULT 1,
    max_days INT DEFAULT 3,
    
    -- Зоны доставки (JSON массив регионов)
    zones JSON,
    
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

-- Способы оплаты магазина
CREATE TABLE store_payment_methods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    
    type VARCHAR(50) NOT NULL, -- cash, card, click, payme, uzcard, transfer
    name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Настройки платежной системы
    settings JSON, -- merchant_id, secret_key и т.д.
    
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

-- Заказы из магазина
CREATE TABLE store_orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    
    -- Покупатель
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_email VARCHAR(255),
    
    -- Доставка
    delivery_method_id BIGINT UNSIGNED,
    delivery_address TEXT,
    delivery_city VARCHAR(255),
    delivery_comment TEXT,
    delivery_price DECIMAL(15,2) DEFAULT 0,
    
    -- Оплата
    payment_method_id BIGINT UNSIGNED,
    payment_status VARCHAR(50) DEFAULT 'pending', -- pending, paid, failed, refunded
    payment_id VARCHAR(255), -- ID транзакции
    
    -- Суммы
    subtotal DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    
    -- Статус
    status VARCHAR(50) DEFAULT 'new', -- new, confirmed, processing, shipped, delivered, cancelled
    
    -- Примечания
    customer_note TEXT,
    admin_note TEXT,
    
    -- Связь с основным заказом SellerMind (создается автоматически)
    sellermind_order_id BIGINT UNSIGNED,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_method_id) REFERENCES store_delivery_methods(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_method_id) REFERENCES store_payment_methods(id) ON DELETE SET NULL,
    INDEX idx_store_status (store_id, status),
    INDEX idx_order_number (order_number)
);

-- Товары в заказе
CREATE TABLE store_order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    
    name VARCHAR(255) NOT NULL, -- на момент заказа
    sku VARCHAR(100),
    price DECIMAL(15,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total DECIMAL(15,2) NOT NULL,
    
    -- Вариант товара (если есть)
    variant_id BIGINT UNSIGNED,
    variant_name VARCHAR(255),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES store_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Страницы магазина (О нас, Доставка, Контакты и т.д.)
CREATE TABLE store_pages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    content LONGTEXT,
    
    show_in_menu BOOLEAN DEFAULT FALSE,
    show_in_footer BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    meta_title VARCHAR(255),
    meta_description TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_page_slug (store_id, slug)
);

-- Промокоды магазина
CREATE TABLE store_promocodes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    
    code VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    
    type VARCHAR(20) NOT NULL, -- percent, fixed
    value DECIMAL(15,2) NOT NULL, -- процент или сумма
    
    min_order_amount DECIMAL(15,2) DEFAULT 0,
    max_discount DECIMAL(15,2), -- макс. скидка для процента
    
    usage_limit INT, -- сколько раз можно использовать
    usage_count INT DEFAULT 0,
    
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_promocode (store_id, code)
);

-- Аналитика посещений
CREATE TABLE store_analytics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    
    date DATE NOT NULL,
    
    -- Посещения
    visits INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    page_views INT DEFAULT 0,
    
    -- Конверсии
    cart_additions INT DEFAULT 0,
    checkouts_started INT DEFAULT 0,
    orders_completed INT DEFAULT 0,
    
    -- Деньги
    revenue DECIMAL(15,2) DEFAULT 0,
    average_order DECIMAL(15,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_date (store_id, date),
    INDEX idx_store_date (store_id, date)
);
```

---

## 📁 Структура файлов

```
app/
├── Models/
│   └── Store/
│       ├── Store.php
│       ├── StoreTheme.php
│       ├── StoreBanner.php
│       ├── StoreCategory.php
│       ├── StoreProduct.php
│       ├── StoreDeliveryMethod.php
│       ├── StorePaymentMethod.php
│       ├── StoreOrder.php
│       ├── StoreOrderItem.php
│       ├── StorePage.php
│       ├── StorePromocode.php
│       └── StoreAnalytics.php
│
├── Http/
│   └── Controllers/
│       ├── Store/
│       │   ├── StoreAdminController.php      # Управление магазином (владелец)
│       │   ├── StoreThemeController.php      # Настройка дизайна
│       │   ├── StoreCatalogController.php    # Управление каталогом
│       │   ├── StoreDeliveryController.php   # Настройка доставки
│       │   ├── StorePaymentController.php    # Настройка оплаты
│       │   ├── StoreOrderController.php      # Заказы магазина
│       │   ├── StoreAnalyticsController.php  # Аналитика
│       │   └── StorePageController.php       # Страницы магазина
│       │
│       └── Storefront/
│           ├── StorefrontController.php      # Витрина магазина
│           ├── CatalogController.php         # Каталог товаров
│           ├── ProductController.php         # Карточка товара
│           ├── CartController.php            # Корзина
│           ├── CheckoutController.php        # Оформление заказа
│           └── PaymentController.php         # Обработка оплаты
│
├── Services/
│   └── Store/
│       ├── StoreService.php                  # Основная логика магазина
│       ├── StoreOrderService.php             # Создание заказов
│       ├── StorePaymentService.php           # Обработка платежей
│       └── StoreAnalyticsService.php         # Аналитика
│
└── Filament/
    └── Resources/
        └── Store/
            ├── StoreResource.php             # CRUD магазина
            ├── StoreOrderResource.php        # Заказы
            └── Pages/
                ├── StoreBuilder.php          # Конструктор магазина
                └── StoreAnalytics.php        # Аналитика

resources/
└── views/
    ├── store/
    │   └── admin/                            # Панель управления магазином
    │       ├── dashboard.blade.php
    │       ├── theme.blade.php
    │       ├── catalog.blade.php
    │       ├── delivery.blade.php
    │       ├── payment.blade.php
    │       ├── orders/
    │       │   ├── index.blade.php
    │       │   └── show.blade.php
    │       ├── pages/
    │       │   ├── index.blade.php
    │       │   └── edit.blade.php
    │       └── analytics.blade.php
    │
    └── storefront/
        ├── layouts/
        │   └── app.blade.php                 # Основной layout витрины
        │
        ├── themes/
        │   ├── default/                      # Тема по умолчанию
        │   │   ├── home.blade.php
        │   │   ├── catalog.blade.php
        │   │   ├── product.blade.php
        │   │   ├── cart.blade.php
        │   │   ├── checkout.blade.php
        │   │   └── components/
        │   │       ├── header.blade.php
        │   │       ├── footer.blade.php
        │   │       ├── product-card.blade.php
        │   │       ├── category-card.blade.php
        │   │       └── hero.blade.php
        │   │
        │   ├── minimal/                      # Минималистичная тема
        │   ├── boutique/                     # Для одежды/бутиков
        │   ├── tech/                         # Для техники
        │   └── grocery/                      # Для продуктов
        │
        └── components/
            ├── cart-icon.blade.php
            ├── search.blade.php
            └── quantity-selector.blade.php
```

---

## 🎨 Шаблоны тем

### 1. Default (Универсальная)
- Чистый современный дизайн
- Подходит для любых товаров
- Баланс между информацией и визуалом

### 2. Minimal (Минимализм)
- Много белого пространства
- Акцент на товарах
- Для premium брендов

### 3. Boutique (Бутик)
- Элегантный стиль
- Большие фото
- Для одежды, аксессуаров

### 4. Tech (Техника)
- Тёмная тема
- Технические характеристики
- Для электроники

### 5. Grocery (Продукты)
- Яркие цвета
- Быстрое добавление в корзину
- Категории с иконками

---

## 🔄 Синхронизация с SellerMind

### Товары:
- Автоматически доступны все товары из SellerMind
- Владелец выбирает какие показывать
- Остатки синхронизируются в реальном времени
- Цены можно переопределить для магазина

### Заказы:
- Заказ из магазина → автоматически создается в SellerMind
- Статус заказа синхронизируется в обе стороны
- Списание остатков происходит в SellerMind

### Категории:
- Берутся из SellerMind
- Можно переименовать и настроить для магазина

---

## 💳 Интеграция оплаты

### Click:
```php
class ClickPaymentService {
    public function createPayment(StoreOrder $order): string {
        // Генерируем ссылку на оплату
    }
    
    public function handleCallback(Request $request): void {
        // Обрабатываем callback от Click
    }
}
```

### Payme:
```php
class PaymePaymentService {
    public function createPayment(StoreOrder $order): string {
        // Генерируем ссылку на оплату
    }
    
    public function handleCallback(Request $request): void {
        // Обрабатываем callback от Payme
    }
}
```

---

## 📱 PWA для магазина

Каждый магазин получает:
- manifest.json с брендингом магазина
- Service Worker для офлайн
- Иконки магазина
- Push-уведомления о заказах

---

## 🚀 Порядок реализации

### Фаза 1: База (1 неделя)
1. [ ] Миграции БД
2. [ ] Модели и связи
3. [ ] Базовый CRUD магазина в Filament
4. [ ] Роуты для витрины

### Фаза 2: Витрина (1 неделя)
1. [ ] Layout витрины
2. [ ] Главная страница
3. [ ] Каталог с фильтрами
4. [ ] Карточка товара
5. [ ] Default тема

### Фаза 3: Корзина и заказы (1 неделя)
1. [ ] Корзина (Session/LocalStorage)
2. [ ] Оформление заказа
3. [ ] Создание заказа в SellerMind
4. [ ] Уведомления владельцу

### Фаза 4: Оплата (3-4 дня)
1. [ ] Интеграция Click
2. [ ] Интеграция Payme
3. [ ] Обработка callbacks
4. [ ] Статусы оплаты

### Фаза 5: Конструктор (1 неделя)
1. [ ] Редактор темы
2. [ ] Управление баннерами
3. [ ] Управление каталогом
4. [ ] Настройки доставки

### Фаза 6: Дополнительно (1 неделя)
1. [ ] Промокоды
2. [ ] Аналитика
3. [ ] Дополнительные темы
4. [ ] PWA
5. [ ] Свой домен

---

## 📊 Тарифы (предложение)

| Тариф | Цена | Функции |
|-------|------|---------|
| **Free** | 0 сум | 50 товаров, 1 тема, store.sellermind.uz |
| **Basic** | 99,000 сум/мес | 500 товаров, все темы, свой домен |
| **Pro** | 199,000 сум/мес | Безлимит, приоритетная поддержка, API |

---

## 🎯 MVP (Минимальный продукт)

Для первого релиза:
1. ✅ Создание магазина
2. ✅ Выбор товаров из SellerMind
3. ✅ 1 базовая тема с настройкой цветов
4. ✅ Корзина и оформление заказа
5. ✅ Оплата наличными + Click
6. ✅ Синхронизация заказов с SellerMind
7. ✅ Поддомен store.sellermind.uz

---

## 🔧 Начинаем?

Скажи с чего начать:
1. **Миграции БД** - создаю SQL и миграции Laravel
2. **Модели** - создаю все модели с связями
3. **Роуты** - настраиваю роутинг для витрины
4. **Витрина** - начинаю с frontend части
