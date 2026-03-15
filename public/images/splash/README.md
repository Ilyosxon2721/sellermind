# iOS Splash Screens для SellerMind PWA

Эта директория содержит splash screens (экраны загрузки) для iOS устройств при запуске PWA.

## Требуемые размеры изображений

### iPhone

| Устройство | Размер (px) | Файл |
|------------|-------------|------|
| iPhone SE, iPod touch | 640x1136 | `apple-splash-640x1136.png` |
| iPhone 8, 7, 6s, 6 | 750x1334 | `apple-splash-750x1334.png` |
| iPhone 8 Plus, 7 Plus, 6s Plus, 6 Plus | 1242x2208 | `apple-splash-1242x2208.png` |
| iPhone X, XS, 11 Pro, 12 mini, 13 mini | 1125x2436 | `apple-splash-1125x2436.png` |
| iPhone XR, 11 | 828x1792 | `apple-splash-828x1792.png` |
| iPhone XS Max, 11 Pro Max | 1242x2688 | `apple-splash-1242x2688.png` |
| iPhone 12, 12 Pro, 13, 13 Pro, 14 | 1170x2532 | `apple-splash-1170x2532.png` |
| iPhone 12 Pro Max, 13 Pro Max, 14 Plus | 1284x2778 | `apple-splash-1284x2778.png` |
| iPhone 14 Pro | 1179x2556 | `apple-splash-1179x2556.png` |
| iPhone 14 Pro Max, 15 Plus, 15 Pro Max | 1290x2796 | `apple-splash-1290x2796.png` |

### iPad

| Устройство | Размер (px) | Файл |
|------------|-------------|------|
| iPad Mini, Air | 1536x2048 | `apple-splash-1536x2048.png` |
| iPad Pro 10.5" | 1668x2224 | `apple-splash-1668x2224.png` |
| iPad Pro 11" | 1668x2388 | `apple-splash-1668x2388.png` |
| iPad Pro 12.9" | 2048x2732 | `apple-splash-2048x2732.png` |

## Генерация изображений

### Вариант 1: Онлайн генератор

Используйте сервис [pwa-asset-generator](https://github.com/nicholasadamou/pwa-asset-generator) или [Progressier](https://progressier.com/pwa-splash-screen-generator).

### Вариант 2: NPM пакет pwa-asset-generator

```bash
# Установка
npm install -g pwa-asset-generator

# Генерация из SVG или PNG логотипа
pwa-asset-generator ./logo.svg ./public/images/splash \
  --splash-only \
  --background "#2563eb" \
  --padding "20%"
```

### Вариант 3: Ручная генерация (Figma/Sketch)

1. Создайте artboard нужного размера
2. Установите фон: `#2563eb` (SellerMind primary blue)
3. Разместите логотип по центру (примерно 25-30% от ширины экрана)
4. Экспортируйте как PNG

## Требования к дизайну

- **Фон:** `#2563eb` (SellerMind primary blue)
- **Логотип:** Белый, по центру экрана
- **Размер логотипа:** 25-30% от ширины экрана
- **Формат:** PNG (не JPEG)
- **Качество:** Максимальное, без сжатия

## Временный placeholder

Файл `splash-placeholder.svg` можно использовать как исходник для генерации PNG файлов.

## Проверка

После добавления изображений:

1. Откройте сайт на iOS устройстве в Safari
2. Нажмите "Поделиться" -> "На экран Домой"
3. Запустите приложение с домашнего экрана
4. Должен отобразиться splash screen

## Landscape режим (опционально)

Для полной поддержки добавьте также версии для ландшафтной ориентации:
- Все размеры в формате ШИРИНАxВЫСОТА становятся ВЫСОТАxШИРИНА
- Добавьте `and (orientation: landscape)` к media query
