<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to handle foreign keys and indexes reliably
        // MySQL requires dropping FK before dropping index that FK uses

        // Get foreign key names dynamically
        $fkOptions = $this->getForeignKeyName('global_options', 'company_id');
        $fkValues = $this->getForeignKeyName('global_option_values', 'company_id');

        // Drop foreign keys first
        if ($fkOptions) {
            DB::statement("ALTER TABLE `global_options` DROP FOREIGN KEY `{$fkOptions}`");
        }
        if ($fkValues) {
            DB::statement("ALTER TABLE `global_option_values` DROP FOREIGN KEY `{$fkValues}`");
        }

        // Drop unique index
        $indexName = $this->getIndexName('global_options', ['company_id', 'code']);
        if ($indexName) {
            DB::statement("ALTER TABLE `global_options` DROP INDEX `{$indexName}`");
        }

        // Make company_id nullable
        DB::statement('ALTER TABLE `global_options` MODIFY `company_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `global_option_values` MODIFY `company_id` BIGINT UNSIGNED NULL');

        // Re-add foreign keys (allowing NULL)
        DB::statement('ALTER TABLE `global_options` ADD CONSTRAINT `global_options_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE');
        DB::statement('ALTER TABLE `global_option_values` ADD CONSTRAINT `global_option_values_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE');

        // Create universal global options (company_id = NULL)
        $this->createUniversalOptions();
    }

    protected function getForeignKeyName(string $table, string $column): ?string
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne('
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ', [$database, $table, $column]);

        return $result?->CONSTRAINT_NAME;
    }

    protected function getIndexName(string $table, array $columns): ?string
    {
        $database = DB::getDatabaseName();
        $columnsStr = implode(',', $columns);

        $result = DB::selectOne("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND NON_UNIQUE = 0
            AND INDEX_NAME != 'PRIMARY'
            GROUP BY INDEX_NAME
            HAVING GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) = ?
        ", [$database, $table, $columnsStr]);

        return $result?->INDEX_NAME;
    }

    public function down(): void
    {
        // Delete universal options
        DB::table('global_option_values')->whereNull('company_id')->delete();
        DB::table('global_options')->whereNull('company_id')->delete();

        // Drop foreign keys
        $fkOptions = $this->getForeignKeyName('global_options', 'company_id');
        $fkValues = $this->getForeignKeyName('global_option_values', 'company_id');

        if ($fkOptions) {
            DB::statement("ALTER TABLE `global_options` DROP FOREIGN KEY `{$fkOptions}`");
        }
        if ($fkValues) {
            DB::statement("ALTER TABLE `global_option_values` DROP FOREIGN KEY `{$fkValues}`");
        }

        // Make company_id NOT NULL again
        DB::statement('ALTER TABLE `global_options` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `global_option_values` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        // Restore unique constraint and foreign keys
        DB::statement('ALTER TABLE `global_options` ADD UNIQUE INDEX `global_options_company_id_code_unique` (`company_id`, `code`)');
        DB::statement('ALTER TABLE `global_options` ADD CONSTRAINT `global_options_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE');
        DB::statement('ALTER TABLE `global_option_values` ADD CONSTRAINT `global_option_values_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE');
    }

    protected function createUniversalOptions(): void
    {
        // Create universal SIZE option
        $sizeOptionId = DB::table('global_options')->insertGetId([
            'company_id' => null,
            'code' => 'size',
            'name' => 'Размер',
            'type' => 'select',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create universal COLOR option
        $colorOptionId = DB::table('global_options')->insertGetId([
            'company_id' => null,
            'code' => 'color',
            'name' => 'Цвет',
            'type' => 'color',
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert sizes
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
            ['value' => '42', 'code' => '42'],
            ['value' => '44', 'code' => '44'],
            ['value' => '46', 'code' => '46'],
            ['value' => '48', 'code' => '48'],
            ['value' => '50', 'code' => '50'],
            ['value' => '52', 'code' => '52'],
            ['value' => '54', 'code' => '54'],
            ['value' => '56', 'code' => '56'],
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
            DB::table('global_option_values')->insert([
                'company_id' => null,
                'global_option_id' => $sizeOptionId,
                'value' => $size['value'],
                'code' => $size['code'],
                'color_hex' => null,
                'sort_order' => $index,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert colors
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
            DB::table('global_option_values')->insert([
                'company_id' => null,
                'global_option_id' => $colorOptionId,
                'value' => $color['value'],
                'code' => $color['code'],
                'color_hex' => $color['hex'],
                'sort_order' => $index,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
