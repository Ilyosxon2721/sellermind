# Review Response Generator Guide

**Version:** 1.0
**Date:** 2026-01-12

---

## Overview

Review Response Generator использует AI для автоматической генерации профессиональных ответов на отзывы клиентов с маркетплейсов. Система экономит до 70% времени менеджеров и обеспечивает единообразный профессиональный тон в общении.

**Ключевые возможности:**
- 🤖 AI-генерация ответов на основе sentiment analysis
- 📝 Библиотека готовых шаблонов
- 🎯 Автоматическое определение тональности отзыва
- 🔍 Извлечение ключевых слов
- 📊 Статистика по обработке отзывов
- 💬 Массовая генерация ответов
- 🌐 Поддержка всех маркетплейсов

---

## Quick Start

### 1. Через UI

1. Перейдите в `/reviews`
2. Выберите отзыв из списка
3. Нажмите **"🤖 Сгенерировать ответ"**
4. Отредактируйте ответ при необходимости
5. Нажмите **"💾 Сохранить"**

### 2. Через API

```bash
# Сгенерировать ответ
curl -X POST https://api.sellermind.ai/api/reviews/123/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "tone": "professional",
    "length": "medium",
    "language": "ru"
  }'

# Сохранить ответ
curl -X POST https://api.sellermind.ai/api/reviews/123/save-response \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "response_text": "Благодарим за отзыв...",
    "is_ai_generated": true
  }'
```

---

## Sentiment Analysis

Система автоматически определяет тональность отзыва на основе оценки:

| Рейтинг | Sentiment  | Стратегия ответа                    |
|---------|------------|-------------------------------------|
| 5       | Positive   | Благодарность, приглашение вернуться|
| 4       | Positive   | Благодарность, уточнение деталей    |
| 3       | Neutral    | Благодарность, запрос на feedback   |
| 2       | Negative   | Извинения, предложение решения      |
| 1       | Negative   | Срочные извинения, компенсация      |

**AI также анализирует:**
- Ключевые слова (брак, доставка, размер, качество)
- Эмоциональный тон текста
- Наличие вопросов или жалоб
- Упоминание конкретных проблем

---

## AI-генерация ответов

### Параметры генерации

```php
$response = $reviewResponseService->generateResponse($review, [
    'tone' => 'professional',      // professional, friendly, formal
    'length' => 'medium',          // short, medium, long
    'language' => 'ru',            // ru, en, uz
    'include_product_name' => true,
    'include_customer_name' => true,
]);
```

### Тональности (Tone)

**Professional (по умолчанию)**
- Формально-вежливый стиль
- Для B2C коммуникации
- Пример: "Благодарим за ваш отзыв..."

**Friendly**
- Дружелюбный, неформальный стиль
- Для молодежной аудитории
- Пример: "Спасибо огромное! Рады, что понравилось!"

**Formal**
- Официально-деловой стиль
- Для премиум сегмента
- Пример: "Приносим искренние извинения..."

### Длина ответа (Length)

- **Short:** 1-2 предложения, ~50 слов
- **Medium:** 2-4 предложения, ~100 слов (по умолчанию)
- **Long:** 4-6 предложений, ~150 слов

---

## Шаблоны ответов

### Категории шаблонов

1. **positive** — Позитивные отзывы (4-5 звезд)
2. **negative_quality** — Проблемы с качеством
3. **negative_delivery** — Проблемы с доставкой
4. **negative_size** — Проблемы с размером
5. **neutral** — Нейтральные отзывы (3 звезды)
6. **question** — Вопросы от клиентов
7. **complaint** — Жалобы

### Переменные в шаблонах

Шаблоны поддерживают подстановку переменных:

- `{customer_name}` — Имя клиента
- `{product_name}` — Название товара
- `{company_name}` — Название компании
- `{order_number}` — Номер заказа

**Пример:**
```
Здравствуйте, {customer_name}!
Благодарим за ваш отзыв о {product_name}.
Ваше мнение очень важно для нас!
```

### Создание своих шаблонов

```http
POST /api/reviews/templates
```

**Body:**
```json
{
  "name": "Мой шаблон для негативных отзывов",
  "category": "negative_quality",
  "template_text": "Извините за {проблема}. Мы готовы предложить замену.",
  "rating_min": 1,
  "rating_max": 2,
  "keywords": ["брак", "дефект", "сломан"]
}
```

---

## API Reference

### Получить все отзывы

```http
GET /api/reviews
```

**Query Parameters:**
- `status` (optional): `pending`, `responded`, `ignored`
- `rating` (optional): `1-5`
- `sentiment` (optional): `positive`, `neutral`, `negative`
- `marketplace` (optional): `wildberries`, `ozon`, `yandex`
- `page` (optional): Номер страницы
- `per_page` (optional): Количество на странице (по умолчанию 20)

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "customer_name": "Иван",
      "rating": 5,
      "review_text": "Отличный товар!",
      "response_text": null,
      "status": "pending",
      "sentiment": "positive",
      "keywords": ["отличный", "товар"],
      "marketplace": "wildberries",
      "product": {
        "id": 456,
        "name": "Футболка хлопковая"
      },
      "created_at": "2026-01-12T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

### Сгенерировать ответ на отзыв

```http
POST /api/reviews/{id}/generate
```

**Body:**
```json
{
  "tone": "professional",
  "length": "medium",
  "language": "ru"
}
```

**Response:**
```json
{
  "review_id": 123,
  "response": "Здравствуйте, Иван! Благодарим за ваш отзыв и высокую оценку! Мы очень рады, что вам понравилась наша футболка. Ваше мнение очень важно для нас. Надеемся на дальнейшее сотрудничество!",
  "is_ai_generated": true
}
```

### Сохранить ответ

```http
POST /api/reviews/{id}/save-response
```

**Body:**
```json
{
  "response_text": "Благодарим за отзыв!",
  "is_ai_generated": true
}
```

### Получить рекомендуемые шаблоны

```http
GET /api/reviews/{id}/suggest-templates
```

**Response:**
```json
{
  "templates": [
    {
      "id": 1,
      "name": "Благодарность за позитивный отзыв",
      "category": "positive",
      "template_text": "Здравствуйте, {customer_name}!...",
      "match_score": 95
    }
  ]
}
```

### Массовая генерация ответов

```http
POST /api/reviews/bulk-generate
```

**Body:**
```json
{
  "review_ids": [123, 124, 125],
  "tone": "professional",
  "length": "medium",
  "save_immediately": false
}
```

**Response:**
```json
{
  "total": 3,
  "success_count": 3,
  "failed_count": 0,
  "results": [
    {
      "review_id": 123,
      "success": true,
      "response": "Благодарим за отзыв..."
    }
  ]
}
```

### Статистика по отзывам

```http
GET /api/reviews/statistics
```

**Response:**
```json
{
  "total_reviews": 1500,
  "pending_reviews": 45,
  "responded_count": 1400,
  "response_rate": 93.3,
  "ai_responses_count": 1200,
  "average_rating": 4.5,
  "sentiment_breakdown": {
    "positive": 1200,
    "neutral": 200,
    "negative": 100
  },
  "response_time_avg_hours": 4.2
}
```

### Получить все шаблоны

```http
GET /api/reviews/templates
```

**Query Parameters:**
- `category` (optional): Фильтр по категории
- `is_system` (optional): Системные или пользовательские

---

## Database Schema

### `reviews` Table

```sql
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY,
    company_id BIGINT FOREIGN KEY,
    product_id BIGINT FOREIGN KEY,
    marketplace_account_id BIGINT FOREIGN KEY,
    external_review_id VARCHAR(255),
    customer_name VARCHAR(255),
    rating INT CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    review_date TIMESTAMP,
    photos JSON,
    response_text TEXT,
    responded_at TIMESTAMP,
    is_ai_generated BOOLEAN,
    status ENUM('pending', 'responded', 'ignored'),
    sentiment ENUM('positive', 'neutral', 'negative'),
    keywords JSON,
    marketplace VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### `review_templates` Table

```sql
CREATE TABLE review_templates (
    id BIGINT PRIMARY KEY,
    company_id BIGINT FOREIGN KEY,
    name VARCHAR(255),
    category VARCHAR(100),
    template_text TEXT,
    is_system BOOLEAN,
    rating_min INT,
    rating_max INT,
    keywords JSON,
    usage_count INT,
    last_used_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Best Practices

### 1. Всегда проверяйте AI-ответы

AI генерирует качественные ответы, но:
- Проверьте факты (особенно при жалобах)
- Адаптируйте под специфику товара
- Добавьте персонализацию при необходимости

### 2. Используйте тональность правильно

```php
// Для позитивных отзывов
'tone' => 'friendly'  // Более теплый ответ

// Для негативных отзывов
'tone' => 'formal'    // Более серьезный подход

// Для нейтральных отзывов
'tone' => 'professional'  // Универсальный стиль
```

### 3. Настройте свои шаблоны

Создайте шаблоны для часто встречающихся ситуаций:
- Проблемы с размером для одежды
- Вопросы по составу для косметики
- Сроки доставки для вашего региона

### 4. Анализируйте статистику

```php
// Отслеживайте эффективность
$stats = $controller->statistics($request);

if ($stats['response_rate'] < 80) {
    // Активируйте массовую генерацию
}

if ($stats['sentiment_breakdown']['negative'] > 100) {
    // Проверьте качество товаров
}
```

### 5. Отвечайте быстро

- Цель: ответ в течение 24 часов
- Используйте массовую генерацию для ускорения
- Настройте уведомления о новых отзывах

---

## Workflow Examples

### Ежедневная обработка отзывов

```php
// 1. Получить новые отзывы
$pendingReviews = Review::where('status', 'pending')
    ->where('company_id', $companyId)
    ->get();

// 2. Массовая генерация ответов
$reviewIds = $pendingReviews->pluck('id')->toArray();
$results = $service->bulkGenerate($reviewIds, [
    'tone' => 'professional',
    'save_immediately' => false,
]);

// 3. Проверка и сохранение
foreach ($results as $result) {
    if ($result['success']) {
        // Автоматически сохранить или отправить на ревью
    }
}
```

### Обработка негативных отзывов

```php
// 1. Найти все негативные отзывы без ответа
$negativeReviews = Review::where('sentiment', 'negative')
    ->whereNull('response_text')
    ->get();

// 2. Генерация с формальным тоном
foreach ($negativeReviews as $review) {
    $response = $service->generateResponse($review, [
        'tone' => 'formal',
        'length' => 'long',  // Более развернутый ответ
    ]);

    // 3. Отправить на утверждение менеджеру
    // (не сохранять автоматически)
}
```

### Автоматизация с шаблонами

```php
// Для отзывов 5 звезд - автоматический ответ
$fiveStarReviews = Review::where('rating', 5)
    ->whereNull('response_text')
    ->get();

foreach ($fiveStarReviews as $review) {
    $template = ReviewTemplate::where('category', 'positive')
        ->where('rating_min', 5)
        ->where('is_system', true)
        ->first();

    if ($template) {
        $response = $template->apply([
            'customer_name' => $review->customer_name ?: 'Уважаемый покупатель',
            'product_name' => $review->product->name,
        ]);

        $review->update([
            'response_text' => $response,
            'is_ai_generated' => false,
            'status' => 'responded',
            'responded_at' => now(),
        ]);
    }
}
```

---

## AI Prompt Customization

Для разработчиков: можно кастомизировать промпты в `ReviewResponseService.php`:

```php
protected function buildPrompt(Review $review, string $tone, string $length, string $language): string
{
    $basePrompt = "Вы - профессиональный менеджер по работе с клиентами.";

    // Добавьте свои инструкции
    if ($review->product->category === 'electronics') {
        $basePrompt .= " Вы эксперт в электронике.";
    }

    return $basePrompt . "Напишите ответ...";
}
```

---

## Troubleshooting

### AI не генерирует ответы

**Проблема:** Ошибка при генерации ответа

**Решения:**

1. Проверьте настройки AIService:
   ```php
   $aiService = app(AIService::class);
   $aiService->isConfigured(); // true?
   ```

2. Проверьте лимиты API
3. Используйте fallback на шаблоны:
   ```php
   $service->getTemplateResponse($review);
   ```

### Шаблоны не применяются корректно

**Проблема:** Переменные не подставляются

**Решение:**
```php
// Проверьте что переменные в правильном формате
$template->apply([
    'customer_name' => $review->customer_name ?: 'Уважаемый покупатель',
    'product_name' => $review->product?->name ?: 'товар',
]);
```

### Медленная генерация

**Проблема:** AI долго генерирует ответы

**Решения:**

1. Уменьшите `max_tokens`:
   ```php
   'max_tokens' => 100,  // Вместо 200
   ```

2. Используйте очереди для массовой генерации:
   ```php
   BulkGenerateReviewResponses::dispatch($reviewIds);
   ```

3. Кэшируйте популярные ответы

---

## Performance Optimization

### Для больших объемов отзывов

```php
// Используйте батчинг
Review::whereNull('response_text')
    ->chunk(100, function ($reviews) use ($service) {
        $reviewIds = $reviews->pluck('id')->toArray();
        $service->bulkGenerate($reviewIds, [
            'save_immediately' => true,
        ]);
    });

// Или очереди
foreach ($reviews as $review) {
    GenerateReviewResponse::dispatch($review)->onQueue('reviews');
}
```

### Кэширование шаблонов

```php
$templates = Cache::remember('review_templates_' . $category, 3600, function () use ($category) {
    return ReviewTemplate::where('category', $category)
        ->where('is_system', true)
        ->get();
});
```

---

## Metrics & Analytics

### Ключевые метрики

1. **Response Rate** — % отвеченных отзывов
2. **AI Usage Rate** — % AI-генерированных ответов
3. **Average Response Time** — Среднее время ответа
4. **Sentiment Distribution** — Распределение по тональности
5. **Template Usage** — Популярность шаблонов

### Дашборд метрик

```php
Route::get('/reviews/analytics', function () {
    $stats = [
        'response_rate' => Review::responseRate(),
        'avg_response_time' => Review::avgResponseTime(),
        'top_templates' => ReviewTemplate::topUsed(10),
        'sentiment_trend' => Review::sentimentTrend(30), // За 30 дней
    ];

    return view('reviews.analytics', compact('stats'));
});
```

---

## Roadmap

**Planned Features:**

- 📧 Email уведомления о новых отзывах
- 🤖 Автоматическая публикация ответов (с одобрением)
- 📊 Дашборд аналитики отзывов
- 🎯 A/B тестирование разных тональностей
- 🌍 Мультиязычная генерация (EN, UZ, KZ)
- 🔄 Интеграция с Telegram для быстрых ответов
- 📱 Push-уведомления о критичных отзывах
- 🧠 ML-модель для предсказания удовлетворенности

---

## Support

- **Email:** [support@sellermind.ai](mailto:support@sellermind.ai)
- **Telegram:** [@sellermind_support](https://t.me/sellermind_support)
- **Docs:** [docs.sellermind.ai](https://docs.sellermind.ai)

---

**Last Updated:** 2026-01-12
**Version:** 1.0
**Maintained by:** SellerMind AI Team
