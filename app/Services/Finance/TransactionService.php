<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;

class TransactionService
{
    public function create(array $data): FinanceTransaction
    {
        // Устанавливаем валюту по умолчанию
        if (empty($data['currency_code'])) {
            $settings = FinanceSettings::getForCompany($data['company_id']);
            $data['currency_code'] = $settings->base_currency_code;
        }

        // Расчёт суммы в базовой валюте
        $data['exchange_rate'] = $data['exchange_rate'] ?? 1;
        $data['amount_base'] = $data['amount'] * $data['exchange_rate'];

        // Статус по умолчанию
        $data['status'] = $data['status'] ?? FinanceTransaction::STATUS_DRAFT;

        return FinanceTransaction::create($data);
    }

    public function update(FinanceTransaction $transaction, array $data): FinanceTransaction
    {
        // Пересчёт суммы в базовой валюте при изменении
        if (isset($data['amount']) || isset($data['exchange_rate'])) {
            $amount = $data['amount'] ?? $transaction->amount;
            $exchangeRate = $data['exchange_rate'] ?? $transaction->exchange_rate ?? 1;
            $data['amount_base'] = $amount * $exchangeRate;
        }

        $transaction->update($data);

        return $transaction;
    }

    public function createFromSource(array $data, string $sourceType, int $sourceId): FinanceTransaction
    {
        $data['source_type'] = $sourceType;
        $data['source_id'] = $sourceId;

        return $this->create($data);
    }
}
