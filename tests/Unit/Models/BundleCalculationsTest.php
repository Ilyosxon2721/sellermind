<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductBundleItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Тесты расчётов для комплектов (bundle):
 * - динамический остаток из компонентов;
 * - динамическая себестоимость как сумма закупочных цен компонентов.
 *
 * Тесты работают без БД: отношения подменяются через setRelation().
 */
class BundleCalculationsTest extends TestCase
{
    private function makeVariant(int $id, int $stock, float $purchasePrice): ProductVariant
    {
        $v = new ProductVariant();
        $v->id = $id;
        $v->setRawAttributes([
            'id' => $id,
            'stock_default' => $stock,
            'purchase_price' => $purchasePrice,
            'is_bundle_variant' => false,
        ], true);

        return $v;
    }

    private function makeBundleItem(int $componentId, int $stock, float $price, int $quantity): ProductBundleItem
    {
        $item = new ProductBundleItem();
        $item->quantity = $quantity;
        $item->setRelation('componentVariant', $this->makeVariant($componentId, $stock, $price));

        return $item;
    }

    public function test_calculateBundleStock_returns_minimum_kits_from_components(): void
    {
        $bundle = new Product();
        $bundle->is_bundle = true;

        // 50 полотенец, 25 халатов, 10 мыл → 1 комплект = 2 полотенца + 1 халат + 1 мыло
        // Возможно комплектов: min(50/2=25, 25/1=25, 10/1=10) = 10
        $items = new Collection([
            $this->makeBundleItem(1, 50, 10_000, 2),
            $this->makeBundleItem(2, 25, 20_000, 1),
            $this->makeBundleItem(3, 10, 5_000, 1),
        ]);
        $bundle->setRelation('bundleItems', $items);

        $this->assertEquals(10, $bundle->calculateBundleStock());
    }

    public function test_calculateBundleStock_returns_zero_when_any_component_missing(): void
    {
        $bundle = new Product();
        $bundle->is_bundle = true;

        // Один компонент закончился → комплект собрать нельзя
        $items = new Collection([
            $this->makeBundleItem(1, 50, 10_000, 2),
            $this->makeBundleItem(2, 0, 20_000, 1),
        ]);
        $bundle->setRelation('bundleItems', $items);

        $this->assertEquals(0, $bundle->calculateBundleStock());
    }

    public function test_calculateBundleStock_returns_zero_for_non_bundle_product(): void
    {
        $product = new Product();
        $product->is_bundle = false;

        $this->assertEquals(0, $product->calculateBundleStock());
    }

    public function test_calculateBundleCost_sums_component_costs_multiplied_by_quantity(): void
    {
        $bundle = new Product();
        $bundle->is_bundle = true;

        // 2 × 10000 + 1 × 20000 + 3 × 5000 = 55000
        $items = new Collection([
            $this->makeBundleItem(1, 100, 10_000, 2),
            $this->makeBundleItem(2, 100, 20_000, 1),
            $this->makeBundleItem(3, 100, 5_000, 3),
        ]);
        $bundle->setRelation('bundleItems', $items);

        $this->assertEquals(55_000.0, $bundle->calculateBundleCost());
    }

    public function test_calculateBundleCost_returns_zero_for_non_bundle_product(): void
    {
        $product = new Product();
        $product->is_bundle = false;

        $this->assertEquals(0.0, $product->calculateBundleCost());
    }

    public function test_calculateBundleCost_handles_components_without_purchase_price(): void
    {
        $bundle = new Product();
        $bundle->is_bundle = true;

        // Один компонент без закупочной цены — не должно ломать расчёт
        $items = new Collection([
            $this->makeBundleItem(1, 100, 10_000, 2),
            $this->makeBundleItem(2, 100, 0, 1),
        ]);
        $bundle->setRelation('bundleItems', $items);

        $this->assertEquals(20_000.0, $bundle->calculateBundleCost());
    }

    public function test_bundle_stock_accessor_delegates_to_calculateBundleStock(): void
    {
        $bundle = new Product();
        $bundle->is_bundle = true;

        $items = new Collection([
            $this->makeBundleItem(1, 30, 1_000, 1),
            $this->makeBundleItem(2, 60, 2_000, 2),
        ]);
        $bundle->setRelation('bundleItems', $items);

        // min(30, 30) = 30
        $this->assertEquals(30, $bundle->bundle_stock);
    }

    public function test_bundle_cost_accessor_delegates_to_calculateBundleCost(): void
    {
        $bundle = new Product();
        $bundle->is_bundle = true;

        $items = new Collection([
            $this->makeBundleItem(1, 100, 1_500, 2),
            $this->makeBundleItem(2, 100, 2_500, 1),
        ]);
        $bundle->setRelation('bundleItems', $items);

        // 2 × 1500 + 1 × 2500 = 5500
        $this->assertEquals(5_500.0, $bundle->bundle_cost);
    }
}
