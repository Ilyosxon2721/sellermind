<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Counterparty;
use App\Models\CounterpartyContract;

final class CounterpartyService
{
    /**
     * Создать контрагента
     *
     * @param  array<string, mixed>  $data
     */
    public function createCounterparty(array $data, int $companyId): Counterparty
    {
        $data['company_id'] = $companyId;
        $data['is_active'] = true;

        return Counterparty::create($data);
    }

    /**
     * Обновить контрагента
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCounterparty(Counterparty $counterparty, array $data): Counterparty
    {
        $counterparty->update($data);

        return $counterparty->fresh();
    }

    /**
     * Удалить контрагента
     */
    public function deleteCounterparty(Counterparty $counterparty): void
    {
        $counterparty->delete();
    }

    /**
     * Создать договор контрагента
     *
     * @param  array<string, mixed>  $data
     */
    public function createContract(Counterparty $counterparty, array $data, int $companyId): CounterpartyContract
    {
        $data['counterparty_id'] = $counterparty->id;
        $data['company_id'] = $companyId;

        return CounterpartyContract::create($data);
    }

    /**
     * Обновить договор
     *
     * @param  array<string, mixed>  $data
     */
    public function updateContract(CounterpartyContract $contract, array $data): CounterpartyContract
    {
        $contract->update($data);

        return $contract->fresh();
    }

    /**
     * Удалить договор
     */
    public function deleteContract(CounterpartyContract $contract): void
    {
        $contract->delete();
    }
}
