<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GlobalOption;
use App\Models\GlobalOptionValue;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductDataSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedCategories($company->id);
            $this->seedSizes($company->id);
            $this->seedColors($company->id);
        }
    }

    protected function seedCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Одежда', 'slug' => 'clothing', 'children' => [
                ['name' => 'Футболки', 'slug' => 'tshirts'],
                ['name' => 'Рубашки', 'slug' => 'shirts'],
                ['name' => 'Брюки', 'slug' => 'pants'],
                ['name' => 'Платья', 'slug' => 'dresses'],
                ['name' => 'Куртки', 'slug' => 'jackets'],
                ['name' => 'Худи и свитшоты', 'slug' => 'hoodies'],
            ]],
            ['name' => 'Обувь', 'slug' => 'shoes', 'children' => [
                ['name' => 'Кроссовки', 'slug' => 'sneakers'],
                ['name' => 'Туфли', 'slug' => 'shoes-formal'],
                ['name' => 'Ботинки', 'slug' => 'boots'],
                ['name' => 'Сандалии', 'slug' => 'sandals'],
            ]],
            ['name' => 'Аксессуары', 'slug' => 'accessories', 'children' => [
                ['name' => 'Сумки', 'slug' => 'bags'],
                ['name' => 'Ремни', 'slug' => 'belts'],
                ['name' => 'Шапки', 'slug' => 'hats'],
                ['name' => 'Шарфы', 'slug' => 'scarves'],
                ['name' => 'Очки', 'slug' => 'glasses'],
            ]],
        ];

        foreach ($categories as $index => $cat) {
            $parent = ProductCategory::firstOrCreate(
                ['company_id' => $companyId, 'slug' => $cat['slug']],
                ['name' => $cat['name'], 'sort_order' => $index, 'is_active' => true]
            );

            if (!empty($cat['children'])) {
                foreach ($cat['children'] as $childIndex => $child) {
                    ProductCategory::firstOrCreate(
                        ['company_id' => $companyId, 'slug' => $child['slug']],
                        ['name' => $child['name'], 'parent_id' => $parent->id, 'sort_order' => $childIndex, 'is_active' => true]
                    );
                }
            }
        }
    }

    protected function seedSizes(int $companyId): void
    {
        $option = GlobalOption::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'size'],
            ['name' => 'Размер', 'type' => 'select', 'sort_order' => 1, 'is_active' => true]
        );

        $sizes = [
            ['value' => 'XXS', 'code' => 'xxs'],
            ['value' => 'XS', 'code' => 'xs'],
            ['value' => 'S', 'code' => 's'],
            ['value' => 'M', 'code' => 'm'],
            ['value' => 'L', 'code' => 'l'],
            ['value' => 'XL', 'code' => 'xl'],
            ['value' => 'XXL', 'code' => 'xxl'],
            ['value' => '3XL', 'code' => '3xl'],
            ['value' => '4XL', 'code' => '4xl'],
            // Числовые размеры одежды
            ['value' => '42', 'code' => '42'],
            ['value' => '44', 'code' => '44'],
            ['value' => '46', 'code' => '46'],
            ['value' => '48', 'code' => '48'],
            ['value' => '50', 'code' => '50'],
            ['value' => '52', 'code' => '52'],
            ['value' => '54', 'code' => '54'],
            ['value' => '56', 'code' => '56'],
            // Размеры обуви
            ['value' => '35', 'code' => 'shoe-35'],
            ['value' => '36', 'code' => 'shoe-36'],
            ['value' => '37', 'code' => 'shoe-37'],
            ['value' => '38', 'code' => 'shoe-38'],
            ['value' => '39', 'code' => 'shoe-39'],
            ['value' => '40', 'code' => 'shoe-40'],
            ['value' => '41', 'code' => 'shoe-41'],
            ['value' => '42', 'code' => 'shoe-42'],
            ['value' => '43', 'code' => 'shoe-43'],
            ['value' => '44', 'code' => 'shoe-44'],
            ['value' => '45', 'code' => 'shoe-45'],
        ];

        foreach ($sizes as $index => $size) {
            GlobalOptionValue::firstOrCreate(
                ['global_option_id' => $option->id, 'code' => $size['code']],
                [
                    'company_id' => $companyId,
                    'value' => $size['value'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedColors(int $companyId): void
    {
        $option = GlobalOption::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'color'],
            ['name' => 'Цвет', 'type' => 'color', 'sort_order' => 2, 'is_active' => true]
        );

        $colors = [
            ['value' => 'Белый', 'code' => 'white', 'hex' => '#FFFFFF'],
            ['value' => 'Чёрный', 'code' => 'black', 'hex' => '#000000'],
            ['value' => 'Серый', 'code' => 'gray', 'hex' => '#808080'],
            ['value' => 'Красный', 'code' => 'red', 'hex' => '#FF0000'],
            ['value' => 'Бордовый', 'code' => 'burgundy', 'hex' => '#800020'],
            ['value' => 'Оранжевый', 'code' => 'orange', 'hex' => '#FFA500'],
            ['value' => 'Жёлтый', 'code' => 'yellow', 'hex' => '#FFFF00'],
            ['value' => 'Зелёный', 'code' => 'green', 'hex' => '#008000'],
            ['value' => 'Салатовый', 'code' => 'lime', 'hex' => '#32CD32'],
            ['value' => 'Голубой', 'code' => 'lightblue', 'hex' => '#87CEEB'],
            ['value' => 'Синий', 'code' => 'blue', 'hex' => '#0000FF'],
            ['value' => 'Тёмно-синий', 'code' => 'navy', 'hex' => '#000080'],
            ['value' => 'Фиолетовый', 'code' => 'purple', 'hex' => '#800080'],
            ['value' => 'Розовый', 'code' => 'pink', 'hex' => '#FFC0CB'],
            ['value' => 'Бежевый', 'code' => 'beige', 'hex' => '#F5F5DC'],
            ['value' => 'Коричневый', 'code' => 'brown', 'hex' => '#8B4513'],
            ['value' => 'Хаки', 'code' => 'khaki', 'hex' => '#C3B091'],
            ['value' => 'Золотой', 'code' => 'gold', 'hex' => '#FFD700'],
            ['value' => 'Серебряный', 'code' => 'silver', 'hex' => '#C0C0C0'],
            ['value' => 'Мультиколор', 'code' => 'multicolor', 'hex' => null],
        ];

        foreach ($colors as $index => $color) {
            GlobalOptionValue::firstOrCreate(
                ['global_option_id' => $option->id, 'code' => $color['code']],
                [
                    'company_id' => $companyId,
                    'value' => $color['value'],
                    'color_hex' => $color['hex'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
