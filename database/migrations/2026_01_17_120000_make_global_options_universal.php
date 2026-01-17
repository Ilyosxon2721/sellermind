<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints
        Schema::table('global_options', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('global_option_values', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        // Drop unique constraint
        Schema::table('global_options', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
        });

        // Make company_id nullable in global_options
        Schema::table('global_options', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

        // Make company_id nullable in global_option_values
        Schema::table('global_option_values', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

        // Re-add foreign keys (now allowing NULL)
        Schema::table('global_options', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('global_option_values', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Create universal global options (company_id = NULL)
        $this->createUniversalOptions();
    }

    public function down(): void
    {
        // Delete universal options
        DB::table('global_option_values')->whereNull('company_id')->delete();
        DB::table('global_options')->whereNull('company_id')->delete();

        // Drop foreign keys
        Schema::table('global_options', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('global_option_values', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        // Make company_id NOT NULL again
        Schema::table('global_options', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });

        Schema::table('global_option_values', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });

        // Restore unique constraint and foreign keys
        Schema::table('global_options', function (Blueprint $table) {
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('global_option_values', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
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
