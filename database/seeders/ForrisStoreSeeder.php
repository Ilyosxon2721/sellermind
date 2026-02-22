<?php

declare(strict_types=1);

namespace Database\Seeders;

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

class ForrisStoreSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'forris-store')->first();

        if (! $store) {
            $this->command->error('FORRIS STORE not found (slug: forris-store).');

            return;
        }

        $companyId = $store->company_id; // 2

        // =============================================
        // 1. Категории товаров (company_id=2)
        // =============================================
        $existingCategory = ProductCategory::where('company_id', $companyId)
            ->where('name', 'Органайзеры и разделители')
            ->first();

        if ($existingCategory && empty($existingCategory->slug)) {
            $existingCategory->update(['slug' => 'organizers']);
            $this->command->info('Обновлен slug для существующей категории "Органайзеры и разделители"');
        }

        $categoriesData = [
            ['name' => 'Органайзеры и разделители', 'slug' => 'organizers', 'description' => 'Складные органайзеры, разделители для ящиков и полок', 'sort_order' => 1],
            ['name' => 'Коробки для хранения', 'slug' => 'storage-boxes', 'description' => 'Коробки с крышками для хранения вещей', 'sort_order' => 2],
            ['name' => 'Корзины и контейнеры', 'slug' => 'baskets', 'description' => 'Плетёные корзины, пластиковые контейнеры', 'sort_order' => 3],
            ['name' => 'Вакуумные пакеты', 'slug' => 'vacuum-bags', 'description' => 'Вакуумные пакеты для одежды и постельного белья', 'sort_order' => 4],
            ['name' => 'Чехлы и кофры', 'slug' => 'covers', 'description' => 'Чехлы для одежды, кофры для хранения', 'sort_order' => 5],
            ['name' => 'Для кухни', 'slug' => 'kitchen-storage', 'description' => 'Органайзеры и контейнеры для кухни', 'sort_order' => 6],
        ];

        $categoryModels = [];
        foreach ($categoriesData as $cat) {
            $categoryModels[$cat['slug']] = ProductCategory::firstOrCreate(
                ['company_id' => $companyId, 'slug' => $cat['slug']],
                array_merge($cat, ['company_id' => $companyId, 'is_active' => true])
            );
        }

        $this->command->info('Категории: '.count($categoryModels));

        // =============================================
        // 2. Товары (company_id=2, brand: FORRIS)
        // =============================================
        $productsData = [
            // Органайзеры и разделители
            [
                'name' => 'Складной органайзер для белья 24 ячейки',
                'article' => 'FH-101',
                'brand_name' => 'FORRIS',
                'category_slug' => 'organizers',
                'price' => 45000,
                'description_short' => 'Органайзер с 24 ячейками для нижнего белья и носков',
            ],
            [
                'name' => 'Разделитель для ящика регулируемый 4 шт',
                'article' => 'FH-102',
                'brand_name' => 'FORRIS',
                'category_slug' => 'organizers',
                'price' => 35000,
                'description_short' => 'Набор из 4 регулируемых разделителей для ящиков комода',
            ],
            [
                'name' => 'Органайзер подвесной 6 полок',
                'article' => 'FH-103',
                'brand_name' => 'FORRIS',
                'category_slug' => 'organizers',
                'price' => 69000,
                'description_short' => 'Подвесной органайзер на 6 полок для шкафа',
            ],
            // Коробки для хранения
            [
                'name' => 'Коробка для хранения с крышкой 50L',
                'article' => 'FH-201',
                'brand_name' => 'FORRIS',
                'category_slug' => 'storage-boxes',
                'price' => 89000,
                'description_short' => 'Складная коробка из нетканого материала с крышкой, 50 литров',
            ],
            [
                'name' => 'Набор коробок для хранения 3 шт',
                'article' => 'FH-202',
                'brand_name' => 'FORRIS',
                'category_slug' => 'storage-boxes',
                'price' => 129000,
                'description_short' => 'Комплект из 3 складных коробок разного размера (S, M, L)',
            ],
            [
                'name' => 'Коробка для обуви прозрачная 6 шт',
                'article' => 'FH-203',
                'brand_name' => 'FORRIS',
                'category_slug' => 'storage-boxes',
                'price' => 98000,
                'description_short' => 'Набор из 6 прозрачных коробок для обуви с магнитной дверцей',
            ],
            // Корзины и контейнеры
            [
                'name' => 'Корзина для белья складная 65L',
                'article' => 'FH-301',
                'brand_name' => 'FORRIS',
                'category_slug' => 'baskets',
                'price' => 75000,
                'description_short' => 'Складная корзина для грязного белья с ручками, 65 литров',
            ],
            [
                'name' => 'Контейнер пластиковый с крышкой 30L',
                'article' => 'FH-302',
                'brand_name' => 'FORRIS',
                'category_slug' => 'baskets',
                'price' => 55000,
                'description_short' => 'Прозрачный пластиковый контейнер с защёлками, 30 литров',
            ],
            [
                'name' => 'Набор плетёных корзин 3 шт',
                'article' => 'FH-303',
                'brand_name' => 'FORRIS',
                'category_slug' => 'baskets',
                'price' => 145000,
                'description_short' => 'Комплект из 3 декоративных плетёных корзин для интерьера',
            ],
            // Вакуумные пакеты
            [
                'name' => 'Вакуумные пакеты для одежды 10 шт',
                'article' => 'FH-401',
                'brand_name' => 'FORRIS',
                'category_slug' => 'vacuum-bags',
                'price' => 59000,
                'description_short' => 'Набор из 10 вакуумных пакетов разного размера с насосом',
            ],
            [
                'name' => 'Вакуумный пакет для одеял XL 2 шт',
                'article' => 'FH-402',
                'brand_name' => 'FORRIS',
                'category_slug' => 'vacuum-bags',
                'price' => 42000,
                'description_short' => 'Большие вакуумные пакеты 100x80 см для одеял и подушек',
            ],
            // Чехлы и кофры
            [
                'name' => 'Чехол для одежды дышащий 5 шт',
                'article' => 'FH-501',
                'brand_name' => 'FORRIS',
                'category_slug' => 'covers',
                'price' => 49000,
                'description_short' => 'Набор из 5 дышащих чехлов для костюмов и платьев',
            ],
            [
                'name' => 'Кофр для хранения одеял 80L',
                'article' => 'FH-502',
                'brand_name' => 'FORRIS',
                'category_slug' => 'covers',
                'price' => 65000,
                'description_short' => 'Мягкий кофр с молнией и окошком для хранения одеял',
            ],
            // Для кухни
            [
                'name' => 'Органайзер для специй 20 баночек',
                'article' => 'FH-601',
                'brand_name' => 'FORRIS',
                'category_slug' => 'kitchen-storage',
                'price' => 189000,
                'description_short' => 'Вращающаяся подставка с 20 стеклянными баночками для специй',
            ],
            [
                'name' => 'Контейнеры для круп набор 6 шт',
                'article' => 'FH-602',
                'brand_name' => 'FORRIS',
                'category_slug' => 'kitchen-storage',
                'price' => 115000,
                'description_short' => 'Герметичные контейнеры для хранения круп и сыпучих продуктов',
            ],
            [
                'name' => 'Органайзер для холодильника 4 шт',
                'article' => 'FH-603',
                'brand_name' => 'FORRIS',
                'category_slug' => 'kitchen-storage',
                'price' => 78000,
                'description_short' => 'Прозрачные лотки-органайзеры для порядка в холодильнике',
            ],
        ];

        $productModels = [];

        foreach ($productsData as $pd) {
            $product = Product::firstOrCreate(
                ['company_id' => $companyId, 'article' => $pd['article']],
                [
                    'name' => $pd['name'],
                    'brand_name' => $pd['brand_name'],
                    'category_id' => $categoryModels[$pd['category_slug']]->id,
                    'description_short' => $pd['description_short'],
                    'is_active' => true,
                    'is_archived' => false,
                ]
            );

            // Обновляем category_id если товар уже существовал без категории
            if (! $product->category_id) {
                $product->update(['category_id' => $categoryModels[$pd['category_slug']]->id]);
            }

            // Добавляем изображение-заглушку (picsum.photos)
            $seed = strtolower(str_replace('-', '', $pd['article']));
            ProductImage::firstOrCreate(
                ['product_id' => $product->id, 'is_main' => true],
                [
                    'company_id' => $companyId,
                    'file_path' => "https://picsum.photos/seed/{$seed}/800/800",
                    'alt_text' => $pd['name'],
                    'sort_order' => 0,
                ]
            );

            $productModels[] = ['product' => $product, 'price' => $pd['price']];
        }

        // Добавляем существующий товар id=4 (если он принадлежит этой компании)
        $existingProduct = Product::where('id', 4)
            ->where('company_id', $companyId)
            ->first();

        if ($existingProduct) {
            // Обновляем категорию если нужно
            if (! $existingProduct->category_id && isset($categoryModels['organizers'])) {
                $existingProduct->update(['category_id' => $categoryModels['organizers']->id]);
            }

            $productModels[] = ['product' => $existingProduct, 'price' => 85000, 'is_featured' => true];
            $this->command->info('Существующий товар id=4 добавлен в выборку');
        }

        $this->command->info('Товары: '.count($productModels));

        // =============================================
        // 3. Обновление магазина
        // =============================================
        $store->update([
            'description' => 'FORRIS -- организация пространства для жизни. Органайзеры, коробки, корзины и системы хранения.',
            'address' => 'г. Ташкент, Юнусабадский район, ул. Амира Темура 88',
            'working_hours' => [
                'Пн-Пт' => '09:00 -- 18:00',
                'Сб' => '10:00 -- 15:00',
                'Вс' => 'Выходной',
            ],
            'telegram' => 'forris_store',
            'instagram' => 'forris.store',
            'min_order_amount' => 50000,
            'meta_title' => 'FORRIS -- Органайзеры и системы хранения | Ташкент',
            'meta_description' => 'Складные органайзеры, коробки, корзины и вакуумные пакеты для дома. Доставка по Узбекистану.',
        ]);

        $this->command->info('Магазин обновлён: '.$store->name.' (/store/'.$store->slug.')');

        // =============================================
        // 4. Тема магазина (обновление существующей)
        // =============================================
        if ($store->theme) {
            $store->theme->update([
                'template' => 'minimal',
                // Брендбук FORRIS: золото #DCC096/#C0A676, тёмный #323232, крем #FFFCF6
                'primary_color' => '#C0A676',
                'secondary_color' => '#323232',
                'accent_color' => '#DCC096',
                'background_color' => '#FFFCF6',
                'text_color' => '#323232',
                'heading_font' => 'DM Sans',
                'body_font' => 'DM Sans',
                'header_style' => 'default',
                'header_bg_color' => '#FFFCF6',
                'header_text_color' => '#323232',
                'hero_enabled' => true,
                'hero_title' => 'FORRIS HOME GOODS',
                'hero_subtitle' => 'Качество и комфорт — в каждом уголке вашего дома',
                'hero_image' => null,
                'hero_button_text' => 'Смотреть каталог',
                'hero_button_url' => '/catalog',
                'footer_bg_color' => '#323232',
                'footer_text_color' => '#FFFCF6',
                'footer_text' => '© 2026 FORRIS HOME GOODS. Все права защищены.',
                'product_card_style' => 'default',
                'products_per_page' => 12,
                'show_search' => true,
                'show_cart' => true,
                'show_phone' => true,
                'show_quick_view' => true,
                'show_add_to_cart' => true,
                'custom_css' => '/* FORRIS Brand — dark #323232, gold #DCC096/#C0A676, cream #FFFCF6, font: DM Sans */

/* Dark backgrounds */
.bg-gray-900 { background-color: #323232 !important; }
.bg-gray-800 { background-color: #3d3d3d !important; }

/* Buttons */
.btn-primary { background: #323232 !important; color: #DCC096 !important; }
.btn-primary:hover { background: #C0A676 !important; color: #323232 !important; filter: none !important; }

/* Border colors */
.border-gray-900 { border-color: #323232 !important; }
.border-gray-200 { border-color: #e8e0d4 !important; }
.border-gray-100 { border-color: #f0ebe3 !important; }

/* Background tones */
.bg-gray-50 { background-color: #f5f0e8 !important; }

/* Text colors */
.text-gray-900 { color: #323232 !important; }
.text-gray-700 { color: #4a4a4a !important; }
.text-gray-500 { color: #8a7e6e !important; }
.text-gray-400 { color: #a89e8e !important; }
.text-gray-300 { color: #c0b8a8 !important; }

/* Footer */
footer .bg-white\\/10 { background-color: rgba(220,192,150,0.1) !important; }
footer .bg-white\\/10:hover { background-color: rgba(220,192,150,0.2) !important; }

/* Misc */
::selection { background: #DCC096; color: #323232; }
::-webkit-scrollbar { height: 4px; }
::-webkit-scrollbar-thumb { background: #DCC096; border-radius: 4px; }
',
            ]);

            $this->command->info('Тема обновлена по брендбуку: gold #C0A676, dark #323232, cream #FFFCF6');
        } else {
            $this->command->warn('Тема не найдена для store_id='.$store->id);
        }

        // =============================================
        // 5. Категории магазина (привязка)
        // =============================================
        foreach ($categoryModels as $i => $cat) {
            StoreCategory::firstOrCreate(
                ['store_id' => $store->id, 'category_id' => $cat->id],
                [
                    'custom_name' => $cat->name,
                    'custom_description' => $cat->description,
                    'position' => $cat->sort_order,
                    'is_visible' => true,
                    'show_in_menu' => true,
                ]
            );
        }

        $this->command->info('Категории магазина: '.count($categoryModels));

        // =============================================
        // 6. Товары магазина (привязка с ценами)
        // =============================================
        foreach ($productModels as $i => $pm) {
            $isFeatured = $pm['is_featured'] ?? false;

            StoreProduct::firstOrCreate(
                ['store_id' => $store->id, 'product_id' => $pm['product']->id],
                [
                    'custom_price' => $pm['price'],
                    'is_visible' => true,
                    'is_featured' => $isFeatured,
                    'position' => $i + 1,
                ]
            );
        }

        $this->command->info('Товары магазина: '.count($productModels));

        // =============================================
        // 7. Баннеры
        // =============================================
        $banners = [
            [
                'title' => 'Новая коллекция органайзеров',
                'subtitle' => 'Складные органайзеры FORRIS -- порядок в каждом ящике. Компактное хранение для белья, носков и аксессуаров.',
                'image' => 'https://picsum.photos/seed/forris-banner1/1920/600',
                'image_mobile' => 'https://picsum.photos/seed/forris-banner1m/800/800',
                'button_text' => 'Смотреть',
                'url' => '/catalog?category=organizers',
                'position' => 1,
            ],
            [
                'title' => 'Всё для хранения в одном месте',
                'subtitle' => 'Коробки, корзины, вакуумные пакеты и чехлы. Более 50 товаров для организации пространства.',
                'image' => 'https://picsum.photos/seed/forris-banner2/1920/600',
                'image_mobile' => 'https://picsum.photos/seed/forris-banner2m/800/800',
                'button_text' => 'В каталог',
                'url' => '/catalog',
                'position' => 2,
            ],
            [
                'title' => 'Бесплатная доставка от 200 000 сум',
                'subtitle' => 'Закажите на сумму от 200 000 сум и получите бесплатную курьерскую доставку по Ташкенту.',
                'image' => 'https://picsum.photos/seed/forris-banner3/1920/600',
                'image_mobile' => 'https://picsum.photos/seed/forris-banner3m/800/800',
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

        $this->command->info('Баннеры: '.count($banners));

        // =============================================
        // 8. Способы доставки
        // =============================================
        $deliveryMethods = [
            [
                'name' => 'Самовывоз',
                'description' => 'Забрать самостоятельно из магазина: Юнусабадский район, ул. Амира Темура 88',
                'type' => 'pickup',
                'price' => 0,
                'free_from' => null,
                'min_days' => 0,
                'max_days' => 0,
                'position' => 1,
            ],
            [
                'name' => 'Курьерская доставка по Ташкенту',
                'description' => 'Доставим до двери за 1-2 рабочих дня. Бесплатно при заказе от 200 000 сум.',
                'type' => 'courier',
                'price' => 25000,
                'free_from' => 200000,
                'min_days' => 1,
                'max_days' => 2,
                'position' => 2,
            ],
            [
                'name' => 'Экспресс-доставка',
                'description' => 'Доставка в день заказа (при оформлении до 14:00)',
                'type' => 'express',
                'price' => 45000,
                'free_from' => null,
                'min_days' => 0,
                'max_days' => 1,
                'position' => 3,
            ],
            [
                'name' => 'Доставка по Узбекистану',
                'description' => 'Отправка почтой по всей стране. Срок 3-7 рабочих дней. Бесплатно от 500 000 сум.',
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

        $this->command->info('Способы доставки: '.count($deliveryMethods));

        // =============================================
        // 9. Способы оплаты
        // =============================================
        $paymentMethods = [
            [
                'type' => 'cash',
                'name' => 'Наличными при получении',
                'description' => 'Оплатите наличными курьеру или при самовывозе',
                'position' => 1,
            ],
            [
                'type' => 'card',
                'name' => 'Банковской картой',
                'description' => 'Humo, UzCard, Visa, MasterCard',
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

        $this->command->info('Способы оплаты: '.count($paymentMethods));

        // =============================================
        // 10. Статические страницы
        // =============================================
        $pages = [
            [
                'title' => 'О нас',
                'slug' => 'o-nas',
                'content' => '<h2>О бренде FORRIS</h2>
<p>FORRIS -- это бренд товаров для организации пространства, основанный в Ташкенте. Мы создаём функциональные и стильные решения для хранения, которые помогают навести порядок в каждом уголке дома.</p>

<h3>Наша миссия</h3>
<p>Мы верим, что организованное пространство -- это основа комфортной жизни. Каждый наш продукт разработан с заботой о практичности, долговечности и внешнем виде.</p>

<h3>Почему выбирают FORRIS</h3>
<ul>
<li><strong>Собственная разработка</strong> -- мы сами проектируем каждый органайзер и систему хранения</li>
<li><strong>Качественные материалы</strong> -- нетканые материалы, прочный пластик, натуральные ткани</li>
<li><strong>Доступные цены</strong> -- прямые поставки без посредников</li>
<li><strong>Гарантия качества</strong> -- обмен и возврат в течение 14 дней</li>
</ul>

<h3>Контакты</h3>
<p>Телефон: +998 90 851 50 00<br>
Email: forris.store@mail.ru<br>
Адрес: г. Ташкент, Юнусабадский район, ул. Амира Темура 88</p>',
                'show_in_menu' => true,
                'show_in_footer' => true,
                'position' => 1,
            ],
            [
                'title' => 'Доставка и оплата',
                'slug' => 'dostavka',
                'content' => '<h2>Доставка</h2>

<h3>Самовывоз -- бесплатно</h3>
<p>Заберите заказ из нашего магазина: г. Ташкент, Юнусабадский район, ул. Амира Темура 88.<br>
Режим работы: Пн-Пт 09:00-18:00, Сб 10:00-15:00.</p>

<h3>Курьерская доставка по Ташкенту</h3>
<p>Стоимость: 25 000 сум. <strong>Бесплатно при заказе от 200 000 сум.</strong><br>
Срок: 1-2 рабочих дня.</p>

<h3>Экспресс-доставка</h3>
<p>Стоимость: 45 000 сум. Доставка в день заказа при оформлении до 14:00.</p>

<h3>Доставка по Узбекистану</h3>
<p>Стоимость: 35 000 сум. <strong>Бесплатно при заказе от 500 000 сум.</strong><br>
Срок: 3-7 рабочих дней.</p>

<h2>Оплата</h2>
<ul>
<li><strong>Наличными</strong> -- при получении курьеру или при самовывозе</li>
<li><strong>Банковской картой</strong> -- Humo, UzCard, Visa, MasterCard</li>
<li><strong>Click</strong> -- через приложение</li>
<li><strong>Payme</strong> -- через приложение</li>
</ul>',
                'show_in_menu' => true,
                'show_in_footer' => true,
                'position' => 2,
            ],
            [
                'title' => 'Возврат и обмен',
                'slug' => 'vozvrat',
                'content' => '<h2>Условия возврата и обмена</h2>

<p>FORRIS гарантирует качество каждого товара. Если товар не подошёл или имеет производственный дефект, вы можете вернуть или обменять его.</p>

<h3>Сроки</h3>
<ul>
<li>Возврат/обмен в течение <strong>14 дней</strong> с момента получения</li>
<li>Товар должен быть в оригинальной упаковке</li>
<li>Товар не должен иметь следов использования</li>
</ul>

<h3>Как оформить возврат</h3>
<ol>
<li>Свяжитесь с нами: +998 90 851 50 00 или Telegram @forris_store</li>
<li>Опишите причину возврата и приложите фото (если есть дефект)</li>
<li>Привезите товар в наш магазин (ул. Амира Темура 88) или дождитесь курьера</li>
<li>Получите возврат средств в течение 3 рабочих дней</li>
</ol>

<h3>Не подлежат возврату</h3>
<ul>
<li>Вакуумные пакеты после вскрытия упаковки</li>
<li>Товары с явными следами эксплуатации</li>
</ul>',
                'show_in_menu' => false,
                'show_in_footer' => true,
                'position' => 3,
            ],
            [
                'title' => 'Контакты',
                'slug' => 'kontakty',
                'content' => '<h2>Свяжитесь с нами</h2>

<h3>Телефон</h3>
<p>+998 90 851 50 00 (Пн-Сб, 09:00-18:00)</p>

<h3>Мессенджеры</h3>
<p>Telegram: <a href="https://t.me/forris_store">@forris_store</a><br>
Instagram: <a href="https://instagram.com/forris.store">@forris.store</a></p>

<h3>Email</h3>
<p>forris.store@mail.ru</p>

<h3>Адрес магазина</h3>
<p>г. Ташкент, Юнусабадский район, ул. Амира Темура 88<br>
Ориентир: рядом с ТРЦ Mega Planet</p>

<h3>Режим работы</h3>
<p>Понедельник -- Пятница: 09:00 -- 18:00<br>
Суббота: 10:00 -- 15:00<br>
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

        $this->command->info('Страницы: '.count($pages));

        // =============================================
        // 11. Промокоды
        // =============================================
        $promocodes = [
            [
                'code' => 'FORRIS10',
                'description' => 'Скидка 10% на первый заказ',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 100000,
                'max_discount' => 50000,
                'usage_limit' => 200,
                'usage_count' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
            ],
            [
                'code' => 'STORAGE20',
                'description' => 'Скидка 20 000 сум на заказ от 150 000',
                'type' => 'fixed',
                'value' => 20000,
                'min_order_amount' => 150000,
                'max_discount' => null,
                'usage_limit' => 100,
                'usage_count' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
            ],
            [
                'code' => 'NEWYEAR',
                'description' => 'Новогодняя скидка 15%',
                'type' => 'percent',
                'value' => 15,
                'min_order_amount' => 200000,
                'max_discount' => 100000,
                'usage_limit' => 50,
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

        $this->command->info('Промокоды: '.count($promocodes));

        // =============================================
        // Итог
        // =============================================
        $this->command->newLine();
        $this->command->info('Магазин FORRIS STORE готов: /store/'.$store->slug);
        $this->command->info('Админка: /my-store');
        $this->command->info('Промокоды: FORRIS10, STORAGE20, NEWYEAR');
    }
}
