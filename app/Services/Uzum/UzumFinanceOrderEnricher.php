<?php

declare(strict_types=1);

namespace App\Services\Uzum;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Проставляет delivery_type (FBS|DBS|EDBS|FBO) в uzum_finance_orders
 * через перекрёстную проверку с uzum_orders.
 *
 * Логика:
 *   - Если order_id из Finance API найден в uzum_orders — копируем его delivery_type
 *     (это значит, что заказ требует действий продавца → FBS / DBS / EDBS).
 *   - Если order_id НЕ найден в uzum_orders — выставляем 'FBO'
 *     (заказ полностью обслуживается Uzum, продавцу делать ничего не нужно,
 *     поэтому через FBS API он не возвращается).
 *
 * Сервис рассчитан на запуск после полной синхронизации FBS заказов
 * (UzumOrderSyncService) и Finance заказов (SyncUzumFinanceOrdersJob).
 * Пишет всё одним SQL запросом, чтобы работало быстро на больших объёмах.
 */
final class UzumFinanceOrderEnricher
{
    /**
     * Проставить delivery_type для всех записей аккаунта.
     *
     * @param  bool  $onlyMissing  Если true — обновляем только строки с NULL delivery_type
     * @return int  Количество затронутых строк
     */
    public function enrichForAccount(MarketplaceAccount $account, bool $onlyMissing = false): int
    {
        if ($account->marketplace !== 'uzum') {
            return 0;
        }

        // Один SQL: LEFT JOIN uzum_orders по (account_id, order_id),
        // подставляем delivery_type из uzum_orders или 'FBO' если связи нет.
        // CAST нужен потому что uzum_orders.external_order_id — VARCHAR,
        // а uzum_finance_orders.order_id — BIGINT.
        $sql = <<<'SQL'
            UPDATE uzum_finance_orders f
            LEFT JOIN uzum_orders o
                ON o.marketplace_account_id = f.marketplace_account_id
               AND o.external_order_id = CAST(f.order_id AS CHAR)
            SET f.delivery_type = COALESCE(NULLIF(o.delivery_type, ''), 'FBO'),
                f.updated_at = f.updated_at
            WHERE f.marketplace_account_id = ?
        SQL;

        if ($onlyMissing) {
            $sql .= ' AND f.delivery_type IS NULL';
        }

        $affected = DB::affectingStatement($sql, [$account->id]);

        Log::info('UzumFinanceOrderEnricher: проставлен delivery_type', [
            'account_id' => $account->id,
            'only_missing' => $onlyMissing,
            'affected_rows' => $affected,
        ]);

        return $affected;
    }
}
