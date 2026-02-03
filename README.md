# 🤖 SellerMind Autopilot

Полная автоматизация разработки с Claude Code.

---

## 📦 Что в комплекте

```
sellermind-autopilot/
├── CLAUDE.md                    # Главный конфиг проекта
├── TASKS.md                     # Список задач
├── AUTOPILOT_LOG.md             # Лог автопилота
├── BLOCKERS.md                  # Заблокированные задачи
├── .claude/
│   ├── settings.json            # Hooks и настройки
│   ├── agents/
│   │   ├── architect.md         # Проектирование
│   │   ├── backend.md           # PHP/Laravel
│   │   ├── frontend.md          # Alpine/Tailwind
│   │   ├── tester.md            # Тестирование
│   │   └── reviewer.md          # Code review
│   └── commands/
│       ├── autopilot.md         # /autopilot
│       ├── implement.md         # /implement
│       ├── fix.md               # /fix
│       └── review.md            # /review
└── scripts/
    └── night-dev.sh             # Ночной режим
```

---

## 🚀 Установка

### 1. Скопируй файлы в проект

```bash
# Распакуй архив
unzip sellermind-autopilot.zip

# Скопируй в проект
cp -r sellermind-autopilot/* ~/projects/sellermind/

# Перейди в проект
cd ~/projects/sellermind
```

### 2. Установи Claude Code (если ещё нет)

```bash
npm install -g @anthropic-ai/claude-code
```

### 3. Настрой API ключ

```bash
# Добавь в ~/.bashrc или ~/.zshrc
export ANTHROPIC_API_KEY="sk-ant-..."
```

### 4. Сделай скрипт исполняемым

```bash
chmod +x scripts/night-dev.sh
```

---

## 🎮 Использование

### Режим 1: Интерактивный (VS Code)

```bash
# Открой проект
cd ~/projects/sellermind
code .

# Запусти Claude Code (Ctrl+Shift+P → "Claude Code")

# Используй команды:
/autopilot           # Автономная разработка
/implement <фича>    # Реализовать функцию
/fix <баг>          # Исправить баг
/review             # Code review
```

### Режим 2: Ночной (терминал)

```bash
# Вечером перед сном:
./scripts/night-dev.sh

# Утром проверь:
cat AUTOPILOT_LOG.md
git log --oneline -10

# Если всё ок — пушь вручную:
php artisan test --parallel
git push origin develop
```

---

## 📋 Workflow

```
┌─────────────────────────────────────────────┐
│                  TASKS.md                   │
│                                             │
│  🟡 Очередь        →  🔴 В работе          │
│                           │                 │
│                    ┌──────┴──────┐          │
│                    │  AUTOPILOT  │          │
│                    │             │          │
│                    │ 1. Анализ   │          │
│                    │ 2. Код      │          │
│                    │ 3. Тесты    │          │
│                    │ 4. Review   │          │
│                    │ 5. Коммит   │          │
│                    └──────┬──────┘          │
│                           │                 │
│  ✅ Выполнено      ←──────┘                │
│                                             │
│  🚫 Заблокировано  ←  (если проблема)      │
│         ↓                                   │
│    BLOCKERS.md                              │
└─────────────────────────────────────────────┘
```

---

## ⚙️ Настройка

### Изменить лимит задач

В `scripts/night-dev.sh`:
```bash
MAX_TASKS=10  # По умолчанию 5
```

### Добавить свои hooks

В `.claude/settings.json`:
```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Edit",
        "hooks": [{
          "type": "command",
          "command": "your-command"
        }]
      }
    ]
  }
}
```

### Добавить свою задачу

В `TASKS.md` добавь:
```markdown
- [ ] #XXX **[TYPE]** Описание задачи
  - Файлы: `app/...`
  - Детали: ...
```

---

## 🛡 Безопасность

### ⚠️ GIT PUSH ОТКЛЮЧЁН!

Claude Code **только коммитит локально** и никогда не пушит в GitHub.
Это защита от автодеплоя на Forge.

**Workflow:**
```
Claude Code                          Ты
     │                                │
     ├── код                          │
     ├── тесты                        │
     ├── git commit ──────────────▶  проверяешь
     │                                │
     │                           git push (вручную)
     │                                │
     │                           Forge деплоит
```

**После работы Claude проверь:**
```bash
# Посмотри что накоммитил Claude
git log --oneline -10

# Проверь изменения
git diff HEAD~5 --stat

# Прогони тесты
php artisan test --parallel

# Если всё ок — пушь
git push origin develop
```

### Что НЕ будет делать автопилот:
- ❌ `git push` — запрещено полностью!
- ❌ `migrate:fresh` — удаление БД
- ❌ `rm -rf` — удаление файлов
- ❌ Изменение `.env.production`

### Когда остановится:
- Тесты падают 3 раза подряд
- Нужны миграции (требует подтверждения)
- Непонятная задача

---

## 📊 Мониторинг

### Логи
```bash
# Лог автопилота
cat AUTOPILOT_LOG.md

# Лог hooks
cat .claude/autopilot.log

# Лог тестов
cat .claude/test.log
```

### Git история
```bash
# Коммиты за ночь
git log --since="8 hours ago" --oneline

# Изменённые файлы
git diff --stat HEAD~5
```

---

## ❓ FAQ

**Q: Сколько стоит?**
A: Зависит от использования API. ~$0.01-0.05 за задачу.

**Q: Безопасно ли?**
A: Да, есть ограничения на опасные команды.

**Q: Что если сломает код?**
A: Используй `git checkout .` для отката. Тесты должны ловить проблемы.

**Q: Работает без интернета?**
A: Нет, требуется API Anthropic.

---

## 🆘 Проблемы

### Claude не запускается
```bash
# Проверь установку
claude --version

# Проверь API ключ
echo $ANTHROPIC_API_KEY
```

### Тесты падают
```bash
# Проверь логи
cat .claude/test.log

# Откати изменения
git checkout .
```

---

Сделано с ❤️ для автоматизации SellerMind
