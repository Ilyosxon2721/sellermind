<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\YandexMarketOrder;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Сборка красивых HTML-сообщений для Telegram-уведомлений о заказах.
 */
final class OrderMessageBuilder
{
    private const MARKETPLACES = [
        'uzum' => ['emoji' => '🟣', 'name' => 'Uzum Market'],
        'wb' => ['emoji' => '🟪', 'name' => 'Wildberries'],
        'ozon' => ['emoji' => '🔵', 'name' => 'Ozon'],
        'ym' => ['emoji' => '🟡', 'name' => 'Yandex Market'],
        'yandex_market' => ['emoji' => '🟡', 'name' => 'Yandex Market'],
    ];

    private const STATUSES = [
        'new' => ['emoji' => '🆕', 'label' => 'Новый'],
        'assembling' => ['emoji' => '📦', 'label' => 'В сборке'],
        'shipped' => ['emoji' => '🚚', 'label' => 'Отправлен'],
        'delivered' => ['emoji' => '✅', 'label' => 'Доставлен'],
        'cancelled' => ['emoji' => '❌', 'label' => 'Отменён'],
        'returned' => ['emoji' => '↩️', 'label' => 'Возврат'],
        'processing' => ['emoji' => '⚙️', 'label' => 'В обработке'],
        'awaiting_packaging' => ['emoji' => '📦', 'label' => 'Ожидает сборки'],
        'delivering' => ['emoji' => '🚚', 'label' => 'Доставляется'],
        'awaiting_deliver' => ['emoji' => '🚚', 'label' => 'Ожидает доставки'],
        'canceled' => ['emoji' => '❌', 'label' => 'Отменён'],
    ];

    /**
     * Собрать сообщение для любого типа заказа
     *
     * @return array{text: string, reply_markup: array}
     */
    public function build(Model $order): array
    {
        $data = $this->extractOrderData($order);

        $mp = self::MARKETPLACES[$data['marketplace']] ?? ['emoji' => '📦', 'name' => $data['marketplace']];
        $status = self::STATUSES[$data['status']] ?? ['emoji' => '📋', 'label' => $data['status']];

        // Конвертация суммы в сум
        $totalUzs = $this->convertToUzs($data['total'], $data['currency_code'], $order);
        $currencyDisplay = 'сум';

        $lines = [];
        // 1. Название маркетплейса
        $lines[] = "{$mp['emoji']} <b>{$mp['name']}</b>  │  {$status['emoji']} {$status['label']}";
        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '';

        // 2. Номер заказа
        $lines[] = "📋 <b>Заказ #{$this->escape($data['external_id'])}</b>";

        // 3. Дата и время заказа
        if (! empty($data['ordered_at'])) {
            $lines[] = "🕐 {$data['ordered_at']}";
        }
        $lines[] = '';

        // 4-5. Товары с количеством, SKU и штрих-кодом
        if (! empty($data['items'])) {
            $lines[] = '🛒 <b>Товары:</b>';
            $shown = 0;
            foreach ($data['items'] as $item) {
                if ($shown >= 5) {
                    $remaining = count($data['items']) - 5;
                    $lines[] = "   <i>...и ещё {$remaining} товар(ов)</i>";
                    break;
                }
                $prefix = ($item['qty'] > 1) ? "{$item['qty']}× " : '• ';
                $lines[] = "   {$prefix}{$this->escape($item['name'])}";
                // SKU (артикул)
                if (! empty($item['sku'])) {
                    $lines[] = "     📎 SKU: <code>{$this->escape((string) $item['sku'])}</code>";
                }
                // Штрих-код
                if (! empty($item['barcode'])) {
                    $lines[] = "     🏷 Штрих-код: <code>{$this->escape((string) $item['barcode'])}</code>";
                }
                $shown++;
            }
            $lines[] = '';
        }

        // 6. Сумма заказа в сум
        $lines[] = "💰 <b>Итого: {$this->formatMoney($totalUzs)} {$currencyDisplay}</b>";
        // Показать оригинальную сумму если валюта не UZS
        if ($data['currency_code'] !== 'UZS' && $data['currency_code'] !== 'сум') {
            $lines[] = "   <i>({$this->formatMoney($data['total'])} {$data['currency']})</i>";
        }
        $lines[] = '';

        $lines[] = '━━━━━━━━━━━━━━━━━━━━';

        // Дневная статистика
        $daily = $this->getDailyStats($order, $data['marketplace']);
        $dailyUzs = $this->convertToUzs((float) $daily->revenue, $data['currency_code'], $order);
        $lines[] = "📊 Сегодня: <b>{$daily->count}</b> заказов · <b>{$this->formatMoney($dailyUzs)}</b> {$currencyDisplay}";

        // 7. Кнопка "Подробнее" со ссылкой на заказ
        return [
            'text' => implode("\n", $lines),
            'reply_markup' => $this->buildInlineKeyboard($data['marketplace'], $data['external_id'], $data['account_id']),
        ];
    }

    /**
     * Сводный дневной отчёт для пользователя
     *
     * @return array{text: string, reply_markup: array}
     */
    public function buildDailySummary(int $userId): array
    {
        $user = \App\Models\User::with('company.marketplaceAccounts')->find($userId);
        if (! $user || ! $user->company) {
            return ['text' => '📊 Нет данных для отчёта.', 'reply_markup' => []];
        }

        $accountIds = $user->company->marketplaceAccounts()->pluck('id');
        $date = now()->format('d.m.Y');

        $lines = [];
        $lines[] = "📊 <b>Итоги дня — {$date}</b>";
        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '';

        $totalOrders = 0;
        $totalRevenue = 0.0;
        $totalCancels = 0;
        $totalReturns = 0;

        // Uzum
        $uzum = $this->queryDailyByMarketplace(UzumOrder::class, $accountIds, 'total_amount', 'ordered_at');
        if ($uzum['orders'] > 0) {
            $lines[] = "🟣 Uzum Market";
            $lines[] = "   Заказов: {$uzum['orders']} · Выручка: {$this->formatMoney($uzum['revenue'])} сум";
            $lines[] = '';
            $totalOrders += $uzum['orders'];
            $totalRevenue += $uzum['revenue'];
            $totalCancels += $uzum['cancels'];
            $totalReturns += $uzum['returns'];
        }

        // Wildberries
        $wb = $this->queryDailyByMarketplace(WbOrder::class, $accountIds, 'total_amount', 'ordered_at');
        if ($wb['orders'] > 0) {
            $lines[] = '🟪 Wildberries';
            $lines[] = "   Заказов: {$wb['orders']} · Выручка: {$this->formatMoney($wb['revenue'])} ₽";
            $lines[] = '';
            $totalOrders += $wb['orders'];
            $totalRevenue += $wb['revenue'];
            $totalCancels += $wb['cancels'];
            $totalReturns += $wb['returns'];
        }

        // Ozon
        $ozon = $this->queryDailyByMarketplace(OzonOrder::class, $accountIds, 'total_price', 'created_at_ozon');
        if ($ozon['orders'] > 0) {
            $lines[] = '🔵 Ozon';
            $lines[] = "   Заказов: {$ozon['orders']} · Выручка: {$this->formatMoney($ozon['revenue'])} ₽";
            $lines[] = '';
            $totalOrders += $ozon['orders'];
            $totalRevenue += $ozon['revenue'];
            $totalCancels += $ozon['cancels'];
            $totalReturns += $ozon['returns'];
        }

        // Yandex Market
        $ym = $this->queryDailyByMarketplace(YandexMarketOrder::class, $accountIds, 'total_price', 'created_at_ym');
        if ($ym['orders'] > 0) {
            $lines[] = '🟡 Yandex Market';
            $lines[] = "   Заказов: {$ym['orders']} · Выручка: {$this->formatMoney($ym['revenue'])} ₽";
            $lines[] = '';
            $totalOrders += $ym['orders'];
            $totalRevenue += $ym['revenue'];
            $totalCancels += $ym['cancels'];
            $totalReturns += $ym['returns'];
        }

        if ($totalOrders === 0) {
            $lines[] = '😴 Сегодня заказов не было.';
            $lines[] = '';
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = "💰 <b>ИТОГО: {$totalOrders} заказов · {$this->formatMoney($totalRevenue)}</b>";

        if ($totalCancels > 0 || $totalReturns > 0) {
            $lines[] = "❌ Отмены: {$totalCancels} · ↩️ Возвраты: {$totalReturns}";
        }

        return [
            'text' => implode("\n", $lines),
            'reply_markup' => [
                'inline_keyboard' => [[
                    ['text' => '📊 Открыть дашборд', 'url' => config('app.url') . '/analytics'],
                ]],
            ],
        ];
    }

    /**
     * Извлечь данные заказа в унифицированный формат
     */
    private function extractOrderData(Model $order): array
    {
        return match (true) {
            $order instanceof UzumOrder => $this->extractUzumData($order),
            $order instanceof WbOrder => $this->extractWbData($order),
            $order instanceof OzonOrder => $this->extractOzonData($order),
            $order instanceof YandexMarketOrder => $this->extractYmData($order),
            default => $this->extractGenericData($order),
        };
    }

    private function extractUzumData(UzumOrder $order): array
    {
        $items = [];
        if ($order->relationLoaded('items') || $order->items()->exists()) {
            foreach ($order->items as $item) {
                $raw = $item->raw_payload ?? [];
                $items[] = [
                    'name' => $item->name ?? 'Товар',
                    'qty' => (int) ($item->quantity ?? 1),
                    'price' => (float) ($item->total_price ?? $item->price ?? 0),
                    'sku' => $raw['skuId'] ?? $item->external_offer_id ?? '',
                    'barcode' => $raw['barcode'] ?? '',
                ];
            }
        }

        $raw = $order->raw_payload ?? [];
        $warehouse = $raw['warehouse']['name'] ?? $raw['warehouseName'] ?? '';

        return [
            'marketplace' => 'uzum',
            'external_id' => $order->external_order_id ?? (string) $order->id,
            'status' => $order->status_normalized ?? $order->status ?? 'new',
            'account_name' => $order->account?->name ?? 'Uzum',
            'account_id' => $order->marketplace_account_id,
            'items' => $items,
            'total' => (float) ($order->total_amount ?? 0),
            'currency' => $order->currency ?? 'сум',
            'currency_code' => 'UZS',
            'ordered_at' => $order->ordered_at ? $order->ordered_at->format('d.m.Y H:i') : '',
            'delivery' => $order->delivery_address_full ?: ($order->delivery_city ?? ''),
            'warehouse' => $warehouse,
            'buyer' => $order->customer_name ?? '',
        ];
    }

    private function extractWbData(WbOrder $order): array
    {
        // WB: каждый заказ — один товар
        $raw = $order->raw_payload ?? [];
        $itemName = $raw['subject'] ?? $raw['supplierArticle'] ?? $order->article ?? 'Товар';

        return [
            'marketplace' => 'wb',
            'external_id' => $order->external_order_id ?? (string) $order->id,
            'status' => $order->status_normalized ?? $order->status ?? 'new',
            'account_name' => $order->account?->name ?? 'Wildberries',
            'account_id' => $order->marketplace_account_id,
            'items' => [
                [
                    'name' => $itemName,
                    'qty' => 1,
                    'price' => (float) ($order->total_amount ?? $order->price ?? 0),
                    'sku' => $order->sku ?? $raw['supplierArticle'] ?? '',
                    'barcode' => $raw['barcode'] ?? '',
                ],
            ],
            'total' => (float) ($order->total_amount ?? $order->price ?? 0),
            'currency' => '₽',
            'currency_code' => 'RUB',
            'ordered_at' => $order->ordered_at ? $order->ordered_at->format('d.m.Y H:i') : '',
            'delivery' => $raw['regionName'] ?? $raw['oblastOkrugName'] ?? '',
            'warehouse' => $raw['warehouseName'] ?? '',
            'buyer' => $order->customer_name ?? '',
        ];
    }

    private function extractOzonData(OzonOrder $order): array
    {
        $items = [];
        $products = $order->products ?? [];
        foreach ($products as $product) {
            $items[] = [
                'name' => $product['name'] ?? $product['offer_id'] ?? 'Товар',
                'qty' => (int) ($product['quantity'] ?? 1),
                'price' => (float) ($product['price'] ?? 0),
                'sku' => $product['offer_id'] ?? $product['sku'] ?? '',
                'barcode' => $product['barcode'] ?? '',
            ];
        }

        return [
            'marketplace' => 'ozon',
            'external_id' => $order->posting_number ?? (string) $order->order_id,
            'status' => $order->status ?? 'new',
            'account_name' => $order->marketplaceAccount?->name ?? 'Ozon',
            'account_id' => $order->marketplace_account_id,
            'items' => $items,
            'total' => (float) ($order->total_price ?? 0),
            'currency' => '₽',
            'currency_code' => 'RUB',
            'ordered_at' => $order->created_at_ozon ? $order->created_at_ozon->format('d.m.Y H:i') : '',
            'delivery' => $order->delivery_address ?? '',
            'warehouse' => '',
            'buyer' => $order->customer_name ?? '',
        ];
    }

    private function extractYmData(YandexMarketOrder $order): array
    {
        $items = [];
        $orderData = $order->order_data ?? [];
        foreach ($orderData['items'] ?? [] as $item) {
            $items[] = [
                'name' => $item['offerName'] ?? $item['offerId'] ?? 'Товар',
                'qty' => (int) ($item['count'] ?? 1),
                'price' => (float) ($item['price'] ?? 0),
                'sku' => $item['offerId'] ?? $item['shopSku'] ?? '',
                'barcode' => '',
            ];
        }

        return [
            'marketplace' => 'ym',
            'external_id' => (string) ($order->order_id ?? $order->id),
            'status' => $order->status_normalized ?? $order->status ?? 'new',
            'account_name' => $order->marketplaceAccount?->name ?? 'Yandex Market',
            'account_id' => $order->marketplace_account_id,
            'items' => $items,
            'total' => (float) ($order->total_price ?? 0),
            'currency' => '₽',
            'currency_code' => 'RUB',
            'ordered_at' => $order->created_at_ym ? $order->created_at_ym->format('d.m.Y H:i') : '',
            'delivery' => $orderData['delivery']['address']['city'] ?? '',
            'warehouse' => '',
            'buyer' => $order->customer_name ?? '',
        ];
    }

    private function extractGenericData(Model $order): array
    {
        return [
            'marketplace' => $order->account?->marketplace ?? 'unknown',
            'external_id' => (string) ($order->external_order_id ?? $order->order_id ?? $order->id),
            'status' => $order->status ?? 'new',
            'account_name' => $order->account?->name ?? '',
            'account_id' => $order->marketplace_account_id ?? null,
            'items' => [],
            'total' => (float) ($order->total_amount ?? $order->total_price ?? 0),
            'currency' => '',
            'currency_code' => 'UZS',
            'ordered_at' => $order->created_at?->format('d.m.Y H:i') ?? '',
            'delivery' => '',
            'warehouse' => '',
            'buyer' => '',
        ];
    }

    /**
     * Статистика за сегодня для маркетплейса
     */
    private function getDailyStats(Model $order, string $marketplace): object
    {
        $accountId = $order->marketplace_account_id ?? null;

        $dateColumn = match (true) {
            $order instanceof UzumOrder => 'ordered_at',
            $order instanceof WbOrder => 'ordered_at',
            $order instanceof OzonOrder => 'created_at_ozon',
            $order instanceof YandexMarketOrder => 'created_at_ym',
            default => 'created_at',
        };

        $totalColumn = match (true) {
            $order instanceof OzonOrder, $order instanceof YandexMarketOrder => 'total_price',
            default => 'total_amount',
        };

        return $order->newQuery()
            ->where('marketplace_account_id', $accountId)
            ->whereDate($dateColumn, today())
            ->selectRaw("COUNT(*) as count, COALESCE(SUM({$totalColumn}), 0) as revenue")
            ->first() ?? (object) ['count' => 0, 'revenue' => 0];
    }

    /**
     * Запрос дневной статистики для конкретного маркетплейса
     */
    private function queryDailyByMarketplace(string $modelClass, $accountIds, string $totalColumn, string $dateColumn): array
    {
        $query = $modelClass::whereIn('marketplace_account_id', $accountIds)
            ->whereDate($dateColumn, today());

        $stats = (clone $query)
            ->selectRaw("COUNT(*) as orders, COALESCE(SUM({$totalColumn}), 0) as revenue")
            ->first();

        $cancels = (clone $query)
            ->whereIn('status', ['cancelled', 'canceled'])
            ->count();

        $returns = (clone $query)
            ->where('status', 'returned')
            ->count();

        return [
            'orders' => (int) ($stats->orders ?? 0),
            'revenue' => (float) ($stats->revenue ?? 0),
            'cancels' => $cancels,
            'returns' => $returns,
        ];
    }

    private function buildInlineKeyboard(string $marketplace, string $externalId, ?int $accountId = null): array
    {
        $baseUrl = config('app.url');

        // Ссылка на страницу заказов конкретного маркетплейса
        if ($accountId) {
            $mpRoute = match ($marketplace) {
                'wb' => "/marketplace/{$accountId}/wb-orders",
                'uzum' => "/marketplace/{$accountId}/uzum-orders",
                'ozon' => "/marketplace/{$accountId}/ozon-orders",
                'ym', 'yandex_market' => "/marketplace/{$accountId}/ym-orders",
                default => '/orders',
            };
            $url = $baseUrl . $mpRoute;
        } else {
            $url = $baseUrl . '/orders';
        }

        return [
            'inline_keyboard' => [[
                ['text' => '📋 Подробнее', 'url' => $url],
            ]],
        ];
    }

    /**
     * Конвертировать сумму в UZS (сум)
     */
    private function convertToUzs(float $amount, string $currencyCode, Model $order): float
    {
        if ($currencyCode === 'UZS' || $currencyCode === 'сум') {
            return $amount;
        }

        try {
            $company = $order->account?->company;
            $converter = new CurrencyConversionService($company);

            return $converter->convert($amount, $currencyCode, 'UZS');
        } catch (\Exception $e) {
            // Фоллбэк: вернуть оригинальную сумму
            return $amount;
        }
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 0, '.', ' ');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
