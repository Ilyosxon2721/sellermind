# AI Провайдеры для KPI

## 🤖 Поддерживаемые провайдеры

SellerMind AI поддерживает **2 AI провайдера** с автоматическим переключением:

| Провайдер | Модели | Цена (input/output за 1M токенов) |
|-----------|--------|-----------------------------------|
| **OpenAI** | GPT-5.1, GPT-4o, GPT-4o-mini | $5/$15, $2.5/$10, $0.15/$0.6 |
| **Anthropic** | Claude Opus 4, Sonnet 4, Haiku 4 | $15/$75, $3/$15, $0.25/$1.25 |

## 🔧 Настройка

### 1. Добавьте API ключи в `.env`:

```bash
# Основной провайдер (openai или anthropic)
AI_PROVIDER=openai

# Резервный провайдер (автоматически включается при ошибках)
AI_FALLBACK_PROVIDER=anthropic

# OpenAI
OPENAI_API_KEY=sk-...
OPENAI_KPI_MODEL=gpt-5.1

# Anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_KPI_MODEL=claude-sonnet-4-20250514
```

### 2. Получить API ключи:

**OpenAI:**
- https://platform.openai.com/api-keys

**Anthropic:**
- https://console.anthropic.com/settings/keys

## ⚙️ Как работает автоматический fallback

```
┌─────────────────┐
│  KPI AI Request │
└────────┬────────┘
         │
         ▼
    ┌─────────┐
    │ Primary │ ◄─── OpenAI (по умолчанию)
    │Provider │
    └────┬────┘
         │
    ✅ Success? ────► Вернуть результат
         │
    ❌ Error?
         │
         ▼
   ┌──────────┐
   │ Fallback │ ◄─── Anthropic (резервный)
   │ Provider │
   └─────┬────┘
         │
    ✅ Success? ────► Вернуть результат
         │
    ❌ Error? ────► Выбросить исключение
```

## 📊 Логирование

Все запросы к AI логируются в таблицу `ai_usage_logs`:

```sql
SELECT
    model,                    -- Например: openai:gpt-5.1
    tokens_input,
    tokens_output,
    cost_estimated,
    created_at
FROM ai_usage_logs
ORDER BY created_at DESC
LIMIT 10;
```

## 🎯 Рекомендации по выбору провайдера

### Используйте **OpenAI**, если:
- Нужна скорость (GPT-5.1 быстрее)
- Ограничен бюджет (GPT-4o-mini дешевле)
- Уже есть кредиты OpenAI

### Используйте **Anthropic (Claude)**, если:
- Нужна максимальная точность анализа
- Работаете с финансовыми данными
- Требуется детальное reasoning

### Рекомендуемая конфигурация:
```bash
# Claude как основной (лучше для аналитики)
AI_PROVIDER=anthropic
AI_FALLBACK_PROVIDER=openai

# OpenAI как основной (дешевле)
AI_PROVIDER=openai
AI_FALLBACK_PROVIDER=anthropic
```

## 🧪 Тестирование

```bash
# Тест с текущим провайдером
php artisan kpi:test-ai --company-id=1

# Просмотр логов использования
php artisan ai:usage --limit=20
```

## 💰 Оценка стоимости

Средний KPI запрос:
- **Токены:** ~2000 input + 800 output
- **OpenAI GPT-5.1:** ~$0.022 за запрос
- **Anthropic Sonnet 4:** ~$0.018 за запрос

**100 KPI планов в месяц:**
- OpenAI: ~$2.20
- Anthropic: ~$1.80

## 🔍 Мониторинг

Проверить статус провайдеров:

```php
use App\Services\AI\AiProviderService;

$aiService = app(AiProviderService::class);

// Основной провайдер
$primary = $aiService->getPrimaryProvider();
echo $primary->getName(); // openai

// Резервный провайдер
$fallback = $aiService->getFallbackProvider();
echo $fallback->getName(); // anthropic
```

## 🚨 Обработка ошибок

Система автоматически переключается при:
- **Rate limits** (429)
- **Quota exceeded** (insufficient_quota)
- **API timeouts**
- **Network errors**

Все переключения логируются:

```bash
tail -f storage/logs/laravel.log | grep "AI"
```

## 📝 Примеры использования

### KPI рекомендации с Claude:

```bash
# .env
AI_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
```

Результат:
```json
{
  "target_revenue": 8500000,
  "target_margin": 1700000,
  "target_orders": 180,
  "reasoning": "На основе анализа последних 6 месяцев...",
  "provider": "anthropic",
  "model": "claude-sonnet-4-20250514"
}
```

### Fallback в действии:

```
[2026-03-23 15:30:45] Primary provider failed: Rate limit exceeded
[2026-03-23 15:30:46] Switching to fallback: anthropic
[2026-03-23 15:30:48] Fallback success: claude-sonnet-4-20250514
```

## ❓ FAQ

**Q: Можно ли использовать только один провайдер?**
A: Да, установите `AI_FALLBACK_PROVIDER=` (пусто)

**Q: Как выбрать между моделями?**
A: Используйте Sonnet для баланса цена/качество, Opus для максимального качества, Haiku для скорости

**Q: Где хранятся API ключи?**
A: В `.env` файле, НЕ коммитьте их в git!

## 🔐 Безопасность

- ✅ API ключи только в `.env`
- ✅ Никогда не логируем ключи
- ✅ Используем HTTPS для всех запросов
- ✅ Таймауты для защиты от зависаний
- ✅ Graceful degradation при ошибках
