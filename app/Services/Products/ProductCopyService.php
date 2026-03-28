<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\OzonProduct;
use App\Models\Product;
use App\Models\WildberriesProduct;
use App\Services\Marketplaces\MarketplaceClientFactory;
use App\Services\Products\DTO\ProductCardDTO;
use App\Services\Products\Extractors\LocalProductExtractor;
use App\Services\Products\Extractors\OzonProductExtractor;
use App\Services\Products\Extractors\ProductExtractorInterface;
use App\Services\Products\Extractors\UzumProductExtractor;
use App\Services\Products\Extractors\WildberriesProductExtractor;
use App\Services\Products\Extractors\YandexMarketProductExtractor;
use Illuminate\Support\Facades\Log;

/**
 * Универсальный сервис копирования/публикации карточек товаров
 *
 * Поддерживает:
 * - Маркетплейс A -> Маркетплейс B
 * - Локальная карточка -> Маркетплейс
 * - Массовая публикация на все маркетплейсы
 */
final class ProductCopyService
{
    /** @var ProductExtractorInterface[] */
    private array $extractors;

    public function __construct(
        private readonly MarketplaceClientFactory $clientFactory,
    ) {
        $this->extractors = [
            new WildberriesProductExtractor(),
            new OzonProductExtractor(),
            new UzumProductExtractor(),
            new YandexMarketProductExtractor(),
            new LocalProductExtractor(),
        ];
    }

    /**
     * Копирование товаров из маркетплейса A в маркетплейс B
     *
     * @return array{copied: int, skipped: int, errors: array}
     */
    public function copy(
        MarketplaceAccount $sourceAccount,
        MarketplaceAccount $targetAccount,
        array $productIds = []
    ): array {
        $extractor = $this->getExtractor($sourceAccount->marketplace);
        if (! $extractor) {
            return ['copied' => 0, 'skipped' => 0, 'errors' => ["Маркетплейс {$sourceAccount->marketplace} не поддерживается как источник"]];
        }

        $sourceProducts = $this->loadSourceProducts($sourceAccount, $productIds);

        return $this->copyProducts($sourceProducts, $extractor, $targetAccount);
    }

    /**
     * Публикация локальной карточки на маркетплейс
     *
     * @return array{copied: int, skipped: int, errors: array}
     */
    public function publishLocal(Product $product, MarketplaceAccount $targetAccount): array
    {
        $extractor = new LocalProductExtractor();
        $dto = $extractor->extract($product);

        return $this->writeToTarget($dto, $targetAccount);
    }

    /**
     * Публикация локальной карточки на все активные маркетплейсы компании
     *
     * @return array<string, array{copied: int, skipped: int, errors: array}>
     */
    public function publishLocalToAll(Product $product): array
    {
        $accounts = MarketplaceAccount::where('company_id', $product->company_id)
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($accounts as $account) {
            $results[$account->id . '_' . $account->marketplace] = $this->publishLocal($product, $account);
        }

        return $results;
    }

    /**
     * Массовое копирование в несколько целевых аккаунтов
     *
     * @return array<int, array{account_id: int, marketplace: string, result: array}>
     */
    public function bulkCopy(
        MarketplaceAccount $sourceAccount,
        array $targetAccountIds,
        array $productIds = []
    ): array {
        $targets = MarketplaceAccount::whereIn('id', $targetAccountIds)
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($targets as $target) {
            $results[] = [
                'account_id' => $target->id,
                'marketplace' => $target->marketplace,
                'name' => $target->getDisplayName(),
                'result' => $this->copy($sourceAccount, $target, $productIds),
            ];
        }

        return $results;
    }

    /**
     * Массовая публикация локальных карточек на выбранные аккаунты
     *
     * @return array<int, array{account_id: int, marketplace: string, result: array}>
     */
    public function bulkPublishLocal(
        array $productIds,
        array $targetAccountIds,
        int $companyId
    ): array {
        $products = Product::whereIn('id', $productIds)
            ->where('company_id', $companyId)
            ->get();

        $targets = MarketplaceAccount::whereIn('id', $targetAccountIds)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($targets as $target) {
            $copied = 0;
            $skipped = 0;
            $errors = [];

            foreach ($products as $product) {
                $result = $this->publishLocal($product, $target);
                $copied += $result['copied'];
                $skipped += $result['skipped'];
                $errors = array_merge($errors, $result['errors']);
            }

            $results[] = [
                'account_id' => $target->id,
                'marketplace' => $target->marketplace,
                'name' => $target->getDisplayName(),
                'result' => compact('copied', 'skipped', 'errors'),
            ];
        }

        return $results;
    }

    /**
     * Предпросмотр копирования (dry-run)
     *
     * @return array{items: array, total: int, new: int, existing: int}
     */
    public function preview(
        string $sourceType,
        ?int $sourceAccountId,
        array $productIds,
        array $targetAccountIds,
        int $companyId
    ): array {
        $items = [];
        $newCount = 0;
        $existingCount = 0;

        $targets = MarketplaceAccount::whereIn('id', $targetAccountIds)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        if ($sourceType === 'local') {
            $products = Product::whereIn('id', $productIds)
                ->where('company_id', $companyId)
                ->get();

            $extractor = new LocalProductExtractor();

            foreach ($products as $product) {
                $dto = $extractor->extract($product);
                foreach ($targets as $target) {
                    $existing = MarketplaceProduct::where('marketplace_account_id', $target->id)
                        ->where('external_offer_id', $dto->offerId)
                        ->first();

                    $status = $existing ? 'exists' : 'new';
                    if ($status === 'new') {
                        $newCount++;
                    } else {
                        $existingCount++;
                    }

                    $items[] = [
                        'source_name' => $dto->name,
                        'source_image' => $dto->previewImage,
                        'offer_id' => $dto->offerId,
                        'target_account' => $target->getDisplayName(),
                        'target_marketplace' => $target->marketplace,
                        'status' => $status,
                    ];
                }
            }
        } else {
            $sourceAccount = MarketplaceAccount::where('id', $sourceAccountId)
                ->where('company_id', $companyId)
                ->first();

            if (! $sourceAccount) {
                return ['items' => [], 'total' => 0, 'new' => 0, 'existing' => 0];
            }

            $extractor = $this->getExtractor($sourceAccount->marketplace);
            if (! $extractor) {
                return ['items' => [], 'total' => 0, 'new' => 0, 'existing' => 0];
            }

            $sourceProducts = $this->loadSourceProducts($sourceAccount, $productIds);

            foreach ($sourceProducts as $source) {
                $dto = $extractor->extract($source);
                foreach ($targets as $target) {
                    $existing = MarketplaceProduct::where('marketplace_account_id', $target->id)
                        ->where('external_offer_id', $dto->offerId)
                        ->first();

                    $status = $existing ? 'exists' : 'new';
                    if ($status === 'new') {
                        $newCount++;
                    } else {
                        $existingCount++;
                    }

                    $items[] = [
                        'source_name' => $dto->name,
                        'source_image' => $dto->previewImage,
                        'offer_id' => $dto->offerId,
                        'target_account' => $target->getDisplayName(),
                        'target_marketplace' => $target->marketplace,
                        'status' => $status,
                    ];
                }
            }
        }

        return [
            'items' => $items,
            'total' => count($items),
            'new' => $newCount,
            'existing' => $existingCount,
        ];
    }

    /**
     * Загрузить товары-источники по маркетплейсу
     */
    public function loadSourceProducts(MarketplaceAccount $account, array $productIds = []): \Illuminate\Support\Collection
    {
        return match ($account->marketplace) {
            'wb', 'wildberries' => $this->loadWbProducts($account, $productIds),
            'ozon' => $this->loadOzonProducts($account, $productIds),
            'uzum' => $this->loadUzumProducts($account, $productIds),
            'ym', 'yandex_market' => $this->loadYmProducts($account, $productIds),
            default => collect(),
        };
    }

    private function loadWbProducts(MarketplaceAccount $account, array $productIds): \Illuminate\Support\Collection
    {
        $query = WildberriesProduct::where('marketplace_account_id', $account->id)
            ->where('is_active', true);

        if (! empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        return $query->get();
    }

    private function loadOzonProducts(MarketplaceAccount $account, array $productIds): \Illuminate\Support\Collection
    {
        $query = OzonProduct::where('marketplace_account_id', $account->id)
            ->where('visible', true);

        if (! empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        return $query->get();
    }

    private function loadUzumProducts(MarketplaceAccount $account, array $productIds): \Illuminate\Support\Collection
    {
        $query = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->where('status', MarketplaceProduct::STATUS_ACTIVE);

        if (! empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        return $query->get();
    }

    private function loadYmProducts(MarketplaceAccount $account, array $productIds): \Illuminate\Support\Collection
    {
        $query = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereIn('status', [MarketplaceProduct::STATUS_ACTIVE, MarketplaceProduct::STATUS_PENDING]);

        if (! empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        return $query->get();
    }

    /**
     * Копировать коллекцию товаров через extractor в целевой аккаунт
     */
    private function copyProducts(
        \Illuminate\Support\Collection $sourceProducts,
        ProductExtractorInterface $extractor,
        MarketplaceAccount $targetAccount
    ): array {
        $copied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($sourceProducts as $source) {
            try {
                $dto = $extractor->extract($source);
                $result = $this->writeToTarget($dto, $targetAccount);
                $copied += $result['copied'];
                $skipped += $result['skipped'];
                $errors = array_merge($errors, $result['errors']);
            } catch (\Throwable $e) {
                $errors[] = [
                    'source_id' => $source->id,
                    'title' => $source->title ?? $source->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
                Log::warning('Ошибка копирования товара', [
                    'source_id' => $source->id,
                    'target_account' => $targetAccount->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('copied', 'skipped', 'errors');
    }

    /**
     * Записать DTO в целевой маркетплейс
     */
    private function writeToTarget(ProductCardDTO $dto, MarketplaceAccount $targetAccount): array
    {
        // Проверяем, не существует ли уже
        $existing = MarketplaceProduct::where('marketplace_account_id', $targetAccount->id)
            ->where('external_offer_id', $dto->offerId)
            ->first();

        if ($existing && $existing->status === MarketplaceProduct::STATUS_ACTIVE) {
            return ['copied' => 0, 'skipped' => 1, 'errors' => []];
        }

        try {
            // Создаём MarketplaceProduct
            $mp = MarketplaceProduct::updateOrCreate(
                [
                    'marketplace_account_id' => $targetAccount->id,
                    'external_offer_id' => $dto->offerId,
                ],
                [
                    'product_id' => $dto->localProductId,
                    'title' => $dto->name,
                    'category' => $dto->category,
                    'preview_image' => $dto->previewImage,
                    'last_synced_price' => $dto->price > 0 ? $dto->price : null,
                    'status' => MarketplaceProduct::STATUS_PENDING,
                    'raw_payload' => $dto->toRawPayload(),
                ]
            );

            // Синхронизируем через клиент маркетплейса
            $client = $this->clientFactory->forAccount($targetAccount);
            $client->syncProducts($targetAccount, [$mp]);

            return ['copied' => 1, 'skipped' => 0, 'errors' => []];
        } catch (\Throwable $e) {
            Log::warning('Ошибка записи товара в маркетплейс', [
                'offer_id' => $dto->offerId,
                'target_account' => $targetAccount->id,
                'error' => $e->getMessage(),
            ]);

            return ['copied' => 0, 'skipped' => 0, 'errors' => [
                ['source_id' => $dto->sourceId, 'title' => $dto->name, 'error' => $e->getMessage()],
            ]];
        }
    }

    private function getExtractor(string $marketplace): ?ProductExtractorInterface
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($marketplace)) {
                return $extractor;
            }
        }

        return null;
    }
}
