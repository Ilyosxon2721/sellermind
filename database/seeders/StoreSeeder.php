<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Store\Store;
use App\Models\Store\StoreBanner;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreDeliveryMethod;
use App\Models\Store\StorePage;
use App\Models\Store\StorePaymentMethod;
use App\Models\Store\StoreProduct;
use App\Models\Store\StorePromocode;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo-store')->first();

        if (! $company) {
            $this->command->error('Demo company not found. Run DatabaseSeeder first.');
            return;
        }

        // =====================
        // 1. Категории товаров
        // =====================
        $categories = [
            ['name' => 'Домашний текстиль', 'slug' => 'home-textile', 'description' => 'Полотенца, халаты, постельное бельё', 'sort_order' => 1],
            ['name' => 'Организация хранения', 'slug' => 'storage', 'description' => 'Органайзеры, коробки, корзины', 'sort_order' => 2],
            ['name' => 'Кухня', 'slug' => 'kitchen', 'description' => 'Посуда, столовые приборы, аксессуары', 'sort_order' => 3],
            ['name' => 'Декор', 'slug' => 'decor', 'description' => 'Свечи, вазы, картины, рамки', 'sort_order' => 4],
            ['name' => 'Уход за домом', 'slug' => 'cleaning', 'description' => 'Средства для уборки, аксессуары', 'sort_order' => 5],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[] = ProductCategory::firstOrCreate(
                ['company_id' => $company->id, 'slug' => $cat['slug']],
                array_merge($cat, ['company_id' => $company->id, 'is_active' => true])
            );
        }

        $this->command->info('✓ Категории: ' . count($categoryModels));

        // =====================
        // 2. Товары
        // =====================
        $productsData = [
            // Домашний текстиль
            ['name' => 'Халат мужской махровый', 'article' => 'HAL-001', 'brand_name' => 'HomeStyle', 'category' => 0, 'price' => 189000],
            ['name' => 'Набор полотенец хлопок', 'article' => 'TOW-003', 'brand_name' => 'SoftTouch', 'category' => 0, 'price' => 95000],
            ['name' => 'Постельное бельё сатин', 'article' => 'BED-004', 'brand_name' => 'DreamSoft', 'category' => 0, 'price' => 320000],
            ['name' => 'Плед флисовый 150×200', 'article' => 'PLT-005', 'brand_name' => 'CozyHome', 'category' => 0, 'price' => 145000],
            ['name' => 'Подушка ортопедическая', 'article' => 'PIL-006', 'brand_name' => 'SleepWell', 'category' => 0, 'price' => 210000],
            // Организация хранения
            ['name' => 'Органайзер для хранения 66L', 'article' => 'ORG-002', 'brand_name' => 'StoreMaster', 'category' => 1, 'price' => 78000],
            ['name' => 'Набор корзин плетёных 3шт', 'article' => 'BSK-007', 'brand_name' => 'NaturaCraft', 'category' => 1, 'price' => 125000],
            ['name' => 'Вакуумные пакеты 10шт', 'article' => 'VAC-008', 'brand_name' => 'SpaceSaver', 'category' => 1, 'price' => 45000],
            // Кухня
            ['name' => 'Набор кастрюль нержавейка', 'article' => 'POT-009', 'brand_name' => 'ChefPro', 'category' => 2, 'price' => 450000],
            ['name' => 'Сковорода антипригарная 28см', 'article' => 'PAN-010', 'brand_name' => 'ChefPro', 'category' => 2, 'price' => 165000],
            ['name' => 'Набор ножей 6 предметов', 'article' => 'KNF-011', 'brand_name' => 'SharpEdge', 'category' => 2, 'price' => 280000],
            ['name' => 'Чайник заварочный стекло', 'article' => 'TEA-012', 'brand_name' => 'GlassCraft', 'category' => 2, 'price' => 55000],
            // Декор
            ['name' => 'Ваза керамическая белая', 'article' => 'VAS-013', 'brand_name' => 'ArtDecor', 'category' => 3, 'price' => 89000],
            ['name' => 'Набор свечей ароматических', 'article' => 'CND-014', 'brand_name' => 'ScentHouse', 'category' => 3, 'price' => 42000],
            ['name' => 'Рамка для фото 30×40', 'article' => 'FRM-015', 'brand_name' => 'FrameArt', 'category' => 3, 'price' => 35000],
            // Уход за домом
            ['name' => 'Набор для уборки Premium', 'article' => 'CLN-016', 'brand_name' => 'CleanPro', 'category' => 4, 'price' => 120000],
            ['name' => 'Контейнер для мусора 30L', 'article' => 'BIN-017', 'brand_name' => 'EcoHome', 'category' => 4, 'price' => 68000],
            ['name' => 'Швабра паровая', 'article' => 'MOP-018', 'brand_name' => 'SteamClean', 'category' => 4, 'price' => 350000],
        ];

        // Placeholder images (picsum.photos)
        $imageUrls = [
            'https://picsum.photos/seed/hal001/800/800',
            'https://picsum.photos/seed/tow003/800/800',
            'https://picsum.photos/seed/bed004/800/800',
            'https://picsum.photos/seed/plt005/800/800',
            'https://picsum.photos/seed/pil006/800/800',
            'https://picsum.photos/seed/org002/800/800',
            'https://picsum.photos/seed/bsk007/800/800',
            'https://picsum.photos/seed/vac008/800/800',
            'https://picsum.photos/seed/pot009/800/800',
            'https://picsum.photos/seed/pan010/800/800',
            'https://picsum.photos/seed/knf011/800/800',
            'https://picsum.photos/seed/tea012/800/800',
            'https://picsum.photos/seed/vas013/800/800',
            'https://picsum.photos/seed/cnd014/800/800',
            'https://picsum.photos/seed/frm015/800/800',
            'https://picsum.photos/seed/cln016/800/800',
            'https://picsum.photos/seed/bin017/800/800',
            'https://picsum.photos/seed/mop018/800/800',
        ];

        $productModels = [];
        foreach ($productsData as $i => $pd) {
            $product = Product::firstOrCreate(
                ['company_id' => $company->id, 'article' => $pd['article']],
                [
                    'name' => $pd['name'],
                    'brand_name' => $pd['brand_name'],
                    'category_id' => $categoryModels[$pd['category']]->id,
                    'is_active' => true,
                    'is_archived' => false,
                ]
            );

            // Обновляем category_id если товар уже существовал без категории
            if (! $product->category_id) {
                $product->update(['category_id' => $categoryModels[$pd['category']]->id]);
            }

            // Добавляем изображение-заглушку
            ProductImage::firstOrCreate(
                ['product_id' => $product->id, 'is_main' => true],
                [
                    'company_id' => $company->id,
                    'file_path' => $imageUrls[$i],
                    'alt_text' => $pd['name'],
                    'sort_order' => 0,
                ]
            );

            $productModels[] = ['product' => $product, 'price' => $pd['price']];
        }

        $this->command->info('✓ Товары: ' . count($productModels));

        // =====================
        // 3. Магазин
        // =====================
        $store = Store::firstOrCreate(
            ['company_id' => $company->id, 'slug' => 'demo-shop'],
            [
                'name' => 'HomeStyle — Товары для дома',
                'description' => 'Качественные товары для вашего дома по доступным ценам. Текстиль, посуда, декор и аксессуары.',
                'is_active' => true,
                'is_published' => true,
                'maintenance_mode' => false,
                'phone' => '+998 90 123 45 67',
                'email' => 'info@homestyle.uz',
                'address' => 'г. Ташкент, ул. Навои 52',
                'working_hours' => [
                    'Пн-Пт' => '09:00 — 18:00',
                    'Сб' => '10:00 — 16:00',
                    'Вс' => 'Выходной',
                ],
                'instagram' => 'homestyle_uz',
                'telegram' => 'homestyle_uz',
                'currency' => 'сум',
                'min_order_amount' => 50000,
                'meta_title' => 'HomeStyle — Лучшие товары для дома в Ташкенте',
                'meta_description' => 'Широкий ассортимент товаров для дома: текстиль, посуда, декор. Доставка по Ташкенту.',
            ]
        );

        // Обновляем тему если она создалась по умолчанию
        if ($store->theme) {
            $store->theme->update([
                'template' => 'default',
                'primary_color' => '#2563eb',
                'secondary_color' => '#7c3aed',
                'accent_color' => '#f59e0b',
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'hero_enabled' => true,
                'hero_title' => 'Всё для уютного дома',
                'hero_subtitle' => 'Качественные товары по доступным ценам с доставкой по Ташкенту',
                'hero_button_text' => 'Перейти в каталог',
                'hero_button_url' => '/catalog',
                'hero_image' => 'https://picsum.photos/seed/hero-home/1920/800',
                'footer_text' => '© ' . date('Y') . ' HomeStyle. Все права защищены.',
                'products_per_page' => 12,
            ]);
        }

        $this->command->info('✓ Магазин: ' . $store->name . ' (/store/' . $store->slug . ')');

        // =====================
        // 4. Категории магазина
        // =====================
        foreach ($categoryModels as $i => $cat) {
            StoreCategory::firstOrCreate(
                ['store_id' => $store->id, 'category_id' => $cat->id],
                [
                    'custom_name' => $cat->name,
                    'custom_description' => $cat->description,
                    'position' => $i + 1,
                    'is_visible' => true,
                    'show_in_menu' => true,
                ]
            );
        }

        $this->command->info('✓ Категории магазина: ' . count($categoryModels));

        // =====================
        // 5. Товары магазина
        // =====================
        foreach ($productModels as $i => $pm) {
            StoreProduct::firstOrCreate(
                ['store_id' => $store->id, 'product_id' => $pm['product']->id],
                [
                    'custom_price' => $pm['price'],
                    'is_visible' => true,
                    'is_featured' => $i < 8, // первые 8 — рекомендуемые
                    'position' => $i + 1,
                ]
            );
        }

        $this->command->info('✓ Товары магазина: ' . count($productModels));

        // =====================
        // 6. Баннеры
        // =====================
        $banners = [
            [
                'title' => 'Новая коллекция текстиля',
                'subtitle' => 'Скидки до 30% на постельное бельё и полотенца',
                'image' => 'https://picsum.photos/seed/banner1/1920/600',
                'image_mobile' => 'https://picsum.photos/seed/banner1m/800/800',
                'button_text' => 'Смотреть',
                'url' => '/catalog',
                'position' => 1,
            ],
            [
                'title' => 'Всё для кухни',
                'subtitle' => 'Профессиональная посуда ChefPro по специальной цене',
                'image' => 'https://picsum.photos/seed/banner2/1920/600',
                'image_mobile' => 'https://picsum.photos/seed/banner2m/800/800',
                'button_text' => 'Выбрать',
                'url' => '/catalog?category_id=3',
                'position' => 2,
            ],
            [
                'title' => 'Бесплатная доставка',
                'subtitle' => 'При заказе от 200 000 сум — доставка бесплатно',
                'image' => 'https://picsum.photos/seed/banner3/1920/600',
                'image_mobile' => 'https://picsum.photos/seed/banner3m/800/800',
                'button_text' => 'Подробнее',
                'url' => '/page/dostavka',
                'position' => 3,
            ],
        ];

        foreach ($banners as $banner) {
            StoreBanner::firstOrCreate(
                ['store_id' => $store->id, 'title' => $banner['title']],
                array_merge($banner, ['store_id' => $store->id, 'is_active' => true])
            );
        }

        $this->command->info('✓ Баннеры: ' . count($banners));

        // =====================
        // 7. Способы доставки
        // =====================
        $deliveryMethods = [
            [
                'name' => 'Самовывоз',
                'description' => 'Забрать самостоятельно из нашего магазина по адресу: ул. Навои 52',
                'type' => 'pickup',
                'price' => 0,
                'free_from' => null,
                'min_days' => 0,
                'max_days' => 0,
                'position' => 1,
            ],
            [
                'name' => 'Курьерская доставка по Ташкенту',
                'description' => 'Доставим до двери в течение 1-2 дней. Бесплатно при заказе от 200 000 сум.',
                'type' => 'courier',
                'price' => 25000,
                'free_from' => 200000,
                'min_days' => 1,
                'max_days' => 2,
                'position' => 2,
            ],
            [
                'name' => 'Экспресс-доставка',
                'description' => 'Доставка в день заказа (при заказе до 14:00)',
                'type' => 'express',
                'price' => 50000,
                'free_from' => null,
                'min_days' => 0,
                'max_days' => 1,
                'position' => 3,
            ],
            [
                'name' => 'Доставка по Узбекистану',
                'description' => 'Отправка почтой по всей стране. Срок 3-7 рабочих дней.',
                'type' => 'post',
                'price' => 35000,
                'free_from' => 500000,
                'min_days' => 3,
                'max_days' => 7,
                'position' => 4,
            ],
        ];

        foreach ($deliveryMethods as $dm) {
            StoreDeliveryMethod::firstOrCreate(
                ['store_id' => $store->id, 'name' => $dm['name']],
                array_merge($dm, ['store_id' => $store->id, 'is_active' => true])
            );
        }

        $this->command->info('✓ Способы доставки: ' . count($deliveryMethods));

        // =====================
        // 8. Способы оплаты
        // =====================
        $paymentMethods = [
            [
                'type' => 'cash',
                'name' => 'Наличными при получении',
                'description' => 'Оплатите наличными курьеру или в пункте выдачи',
                'position' => 1,
            ],
            [
                'type' => 'card',
                'name' => 'Банковской картой',
                'description' => 'Visa, MasterCard, Humo, UzCard',
                'position' => 2,
            ],
            [
                'type' => 'click',
                'name' => 'Click',
                'description' => 'Оплата через приложение Click',
                'position' => 3,
            ],
            [
                'type' => 'payme',
                'name' => 'Payme',
                'description' => 'Оплата через приложение Payme',
                'position' => 4,
            ],
        ];

        foreach ($paymentMethods as $pm) {
            StorePaymentMethod::firstOrCreate(
                ['store_id' => $store->id, 'type' => $pm['type']],
                array_merge($pm, ['store_id' => $store->id, 'is_active' => true])
            );
        }

        $this->command->info('✓ Способы оплаты: ' . count($paymentMethods));

        // =====================
        // 9. Статические страницы
        // =====================
        $pages = [
            [
                'title' => 'О нас',
                'slug' => 'o-nas',
                'content' => '<h2>О компании HomeStyle</h2>
<p>Мы — команда энтузиастов, которая верит, что каждый дом заслуживает уюта и красоты. С 2020 года мы помогаем нашим клиентам создавать комфортное пространство для жизни.</p>

<h3>Наши преимущества</h3>
<ul>
<li><strong>Качество</strong> — работаем только с проверенными производителями</li>
<li><strong>Доступные цены</strong> — прямые поставки без посредников</li>
<li><strong>Быстрая доставка</strong> — по Ташкенту за 1-2 дня</li>
<li><strong>Гарантия</strong> — обмен и возврат в течение 14 дней</li>
</ul>

<h3>Контакты</h3>
<p>Телефон: +998 90 123 45 67<br>
Email: info@homestyle.uz<br>
Адрес: г. Ташкент, ул. Навои 52</p>',
                'show_in_menu' => true,
                'show_in_footer' => true,
                'position' => 1,
            ],
            [
                'title' => 'Доставка и оплата',
                'slug' => 'dostavka',
                'content' => '<h2>Доставка</h2>

<h3>Самовывоз — бесплатно</h3>
<p>Заберите заказ из нашего магазина по адресу: г. Ташкент, ул. Навои 52. Режим работы: Пн-Пт 9:00-18:00, Сб 10:00-16:00.</p>

<h3>Курьерская доставка по Ташкенту</h3>
<p>Стоимость: 25 000 сум. <strong>Бесплатно при заказе от 200 000 сум.</strong> Срок: 1-2 рабочих дня.</p>

<h3>Экспресс-доставка</h3>
<p>Стоимость: 50 000 сум. Доставка в день заказа при оформлении до 14:00.</p>

<h3>Доставка по Узбекистану</h3>
<p>Стоимость: 35 000 сум. <strong>Бесплатно при заказе от 500 000 сум.</strong> Срок: 3-7 рабочих дней.</p>

<h2>Оплата</h2>
<ul>
<li><strong>Наличными</strong> — при получении</li>
<li><strong>Банковской картой</strong> — Visa, MasterCard, Humo, UzCard</li>
<li><strong>Click</strong> — через приложение</li>
<li><strong>Payme</strong> — через приложение</li>
</ul>',
                'show_in_menu' => true,
                'show_in_footer' => true,
                'position' => 2,
            ],
            [
                'title' => 'Возврат и обмен',
                'slug' => 'vozvrat',
                'content' => '<h2>Условия возврата и обмена</h2>

<p>Мы гарантируем качество всех товаров. Если товар не подошёл или оказался с дефектом, вы можете вернуть или обменять его.</p>

<h3>Сроки</h3>
<ul>
<li>Возврат/обмен в течение <strong>14 дней</strong> с момента получения</li>
<li>Товар должен быть в оригинальной упаковке</li>
<li>Товар не должен иметь следов использования</li>
</ul>

<h3>Как оформить возврат</h3>
<ol>
<li>Свяжитесь с нами по телефону +998 90 123 45 67 или через Telegram</li>
<li>Опишите причину возврата</li>
<li>Привезите товар в наш магазин или дождитесь курьера</li>
<li>Получите возврат средств в течение 3 рабочих дней</li>
</ol>',
                'show_in_menu' => false,
                'show_in_footer' => true,
                'position' => 3,
            ],
            [
                'title' => 'Контакты',
                'slug' => 'kontakty',
                'content' => '<h2>Свяжитесь с нами</h2>

<h3>Телефон</h3>
<p>+998 90 123 45 67 (Пн-Сб, 9:00-18:00)</p>

<h3>Мессенджеры</h3>
<p>Telegram: @homestyle_uz<br>
Instagram: @homestyle_uz</p>

<h3>Email</h3>
<p>info@homestyle.uz</p>

<h3>Адрес магазина</h3>
<p>г. Ташкент, ул. Навои 52<br>
Ориентир: напротив станции метро «Навои»</p>

<h3>Режим работы</h3>
<p>Понедельник — Пятница: 9:00 — 18:00<br>
Суббота: 10:00 — 16:00<br>
Воскресенье: выходной</p>',
                'show_in_menu' => true,
                'show_in_footer' => true,
                'position' => 4,
            ],
        ];

        foreach ($pages as $page) {
            StorePage::firstOrCreate(
                ['store_id' => $store->id, 'slug' => $page['slug']],
                array_merge($page, ['store_id' => $store->id, 'is_active' => true])
            );
        }

        $this->command->info('✓ Страницы: ' . count($pages));

        // =====================
        // 10. Промокоды
        // =====================
        $promocodes = [
            [
                'code' => 'WELCOME10',
                'description' => 'Скидка 10% на первый заказ',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 100000,
                'max_discount' => 50000,
                'usage_limit' => 100,
                'usage_count' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
            ],
            [
                'code' => 'HOME20',
                'description' => 'Скидка 20 000 сум на заказ от 200 000',
                'type' => 'fixed',
                'value' => 20000,
                'min_order_amount' => 200000,
                'max_discount' => null,
                'usage_limit' => 50,
                'usage_count' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(1)->toDateString(),
            ],
            [
                'code' => 'VIP30',
                'description' => 'VIP скидка 30%',
                'type' => 'percent',
                'value' => 30,
                'min_order_amount' => 300000,
                'max_discount' => 150000,
                'usage_limit' => 10,
                'usage_count' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ],
        ];

        foreach ($promocodes as $promo) {
            StorePromocode::firstOrCreate(
                ['store_id' => $store->id, 'code' => $promo['code']],
                array_merge($promo, ['store_id' => $store->id, 'is_active' => true])
            );
        }

        $this->command->info('✓ Промокоды: ' . count($promocodes));

        // =====================
        // Итог
        // =====================
        $this->command->newLine();
        $this->command->info('🏪 Магазин готов: /store/' . $store->slug);
        $this->command->info('📋 Админка: /my-store');
        $this->command->info('🎫 Промокоды: WELCOME10, HOME20, VIP30');
    }
}
