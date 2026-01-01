<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Services\Marketplaces\UzumClient;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class UzumPullProducts extends Command
{
    protected $signature = 'uzum:pull-products {accountId : ID аккаунта Uzum}';
    protected $description = 'Загрузить товары из Uzum (по shopIds аккаунта) в marketplace_products';

    public function handle(UzumClient $client): int
    {
        $accountId = (int) $this->argument('accountId');
        $account = MarketplaceAccount::find($accountId);

        if (!$account) {
            $this->error("Аккаунт {$accountId} не найден");
            return self::FAILURE;
        }

        if (!$account->isUzum()) {
            $this->error("Аккаунт {$accountId} не является Uzum");
            return self::FAILURE;
        }

        $this->info("Загружаем товары Uzum для аккаунта #{$account->id} ({$account->name})");

        $products = $client->fetchProducts($account);
        $this->info("Получено товаров: " . count($products));

        $created = 0;
        $updated = 0;

        foreach ($products as $product) {
            $productId = Arr::get($product, 'productId', Arr::get($product, 'id'));
            $status = Arr::get($product, 'status.value') ?? Arr::get($product, 'status');
            $skuList = Arr::get($product, 'skuList', []);

            // если нет skuList, создаём одну запись на продукт
            if (empty($skuList)) {
                [$result, $isNew] = $this->upsertMpProduct($account, $productId, null, null, $status);
                $isNew ? $created++ : $updated++;
                continue;
            }

            foreach ($skuList as $sku) {
                $skuId = Arr::get($sku, 'skuId', Arr::get($sku, 'id'));
                $barcode = Arr::get($sku, 'barcode');
                [$result, $isNew] = $this->upsertMpProduct($account, $productId, $skuId, $barcode, $status);
                $isNew ? $created++ : $updated++;
            }
        }

        $this->info("Сохранено: создано {$created}, обновлено {$updated}");
        return self::SUCCESS;
    }

    /**
     * @return array{MarketplaceProduct,bool} [$model, $isNew]
     */
    protected function upsertMpProduct(MarketplaceAccount $account, $productId, $skuId, $barcode, $status): array
    {
        $attrs = [
            'marketplace_account_id' => $account->id,
            'external_offer_id' => $skuId ? (string) $skuId : null,
        ];

        $values = [
            'external_product_id' => $productId ? (string) $productId : null,
            'external_sku' => $barcode ? (string) $barcode : null,
            'status' => $status ? (string) $status : null,
        ];

        $existing = MarketplaceProduct::where($attrs)->first();
        $model = MarketplaceProduct::updateOrCreate($attrs, $values);

        return [$model, !$existing];
    }
}
