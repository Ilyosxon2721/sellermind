<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Unit;
use App\Models\Warehouse\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProductVariantsToWarehouse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:sync-variants {--company_id=} {--dry-run} {--sync-stock : Also sync stock_default for existing SKUs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизация ProductVariants в складскую систему (Warehouse\Sku)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY-RUN MODE: Изменения НЕ будут сохранены в БД');
        }

        $this->info('🔄 Начинаем синхронизацию ProductVariants → Warehouse\Sku...');

        // Получаем ProductVariants
        $query = ProductVariant::with('product')
            ->where('is_active', true)
            ->where('is_deleted', false);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $variants = $query->get();
        $this->info("📦 Найдено ProductVariants: {$variants->count()}");

        if ($variants->isEmpty()) {
            $this->warn('⚠️  Нет активных ProductVariants для синхронизации');

            return 0;
        }

        // Получаем или создаем единицу измерения "шт"
        $defaultUnit = Unit::firstOrCreate(
            ['code' => 'PCS'],
            ['name' => 'Штука']
        );

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($variants as $variant) {
                // Проверяем есть ли уже Warehouse\Sku для этого variant
                $warehouseSku = Sku::where('product_variant_id', $variant->id)->first();

                if ($warehouseSku) {
                    // Обновляем существующий
                    if (! $dryRun) {
                        $warehouseSku->update([
                            'sku_code' => $variant->sku,
                            'barcode_ean13' => $variant->barcode,
                            'weight_g' => $variant->weight_g,
                            'length_mm' => $variant->length_mm,
                            'width_mm' => $variant->width_mm,
                            'height_mm' => $variant->height_mm,
                            'is_active' => $variant->is_active,
                        ]);

                        // Sync stock if option is set and variant has stock but ledger doesn't
                        if ($this->option('sync-stock') && $variant->stock_default > 0) {
                            $currentLedgerStock = StockLedger::where('sku_id', $warehouseSku->id)->sum('qty_delta');
                            if ($currentLedgerStock == 0) {
                                $this->syncInitialStock($warehouseSku, $variant);
                            }
                        }
                    }
                    $updated++;
                    $this->line("   ✓ Обновлен: {$variant->sku}");
                } else {
                    // Создаем новый Warehouse\Sku
                    if (! $dryRun) {
                        $warehouseSku = Sku::create([
                            'product_id' => $variant->product_id,
                            'product_variant_id' => $variant->id,
                            'company_id' => $variant->company_id,
                            'sku_code' => $variant->sku,
                            'barcode_ean13' => $variant->barcode,
                            'weight_g' => $variant->weight_g,
                            'length_mm' => $variant->length_mm,
                            'width_mm' => $variant->width_mm,
                            'height_mm' => $variant->height_mm,
                            'is_active' => $variant->is_active,
                        ]);

                        // Если есть stock_default, создаем начальные записи в stock_ledger
                        if ($variant->stock_default > 0) {
                            $this->syncInitialStock($warehouseSku, $variant);
                        }
                    }
                    $created++;
                    $this->line("   + Создан: {$variant->sku}".($variant->stock_default > 0 ? " (остаток: {$variant->stock_default})" : ''));
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('🔄 Откат транзакции (dry-run)');
            } else {
                DB::commit();
                $this->info('✅ Транзакция зафиксирована');
            }

            $this->newLine();
            $this->info('📊 Результаты синхронизации:');
            $this->table(
                ['Статус', 'Количество'],
                [
                    ['Создано', $created],
                    ['Обновлено', $updated],
                    ['Пропущено', $skipped],
                    ['Всего', $variants->count()],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Ошибка: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Создает начальные записи остатков в stock_ledger для всех складов компании
     */
    /**
     * Создает начальные записи остатков в stock_ledger
     * cost_delta хранит полную стоимость (qty × цена) в базовой валюте (UZS)
     */
    private function syncInitialStock(Sku $warehouseSku, ProductVariant $variant): void
    {
        // Получаем дефолтный склад компании
        $defaultWarehouse = Warehouse::where('company_id', $variant->company_id)
            ->where('is_default', true)
            ->first();

        if (! $defaultWarehouse) {
            // Если нет дефолтного, берем первый склад компании
            $defaultWarehouse = Warehouse::where('company_id', $variant->company_id)->first();
        }

        if ($defaultWarehouse && $variant->stock_default > 0) {
            // Конвертируем закупочную цену в базовую валюту (UZS)
            $purchasePriceBase = $variant->getPurchasePriceInBase();
            $costDelta = $purchasePriceBase * $variant->stock_default;

            // Создаем запись в stock_ledger как начальное оприходование
            StockLedger::create([
                'company_id' => $variant->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $defaultWarehouse->id,
                'location_id' => null,
                'sku_id' => $warehouseSku->id,
                'qty_delta' => $variant->stock_default,
                'cost_delta' => $costDelta,
                'currency_code' => 'UZS',
                'document_id' => null,
                'document_line_id' => null,
                'source_type' => 'INITIAL_SYNC',
                'source_id' => $variant->id,
                'created_by' => null,
            ]);

            $currency = $variant->purchase_price_currency ?? 'UZS';
            $price = $variant->purchase_price ?? 0;
            $costInfo = $price > 0 ? ", закупка: {$price} {$currency}/шт (= " . number_format($purchasePriceBase, 0) . " UZS)" : '';
            $this->line("      ↳ Добавлен остаток {$variant->stock_default} на склад '{$defaultWarehouse->name}'{$costInfo}");
        }
    }
}
