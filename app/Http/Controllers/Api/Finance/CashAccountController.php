<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashAccount;
use App\Models\Finance\CashTransaction;
use App\Models\Finance\MarketplacePayout;
use App\Models\MarketplaceAccount;
use App\Services\Finance\MarketplacePayoutSyncService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashAccountController extends Controller
{
    use ApiResponder;

    /**
     * Список всех счетов компании
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $accounts = CashAccount::byCompany($companyId)
            ->when($request->active_only, fn ($q) => $q->active())
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return $this->successResponse($accounts);
    }

    /**
     * Создать новый счёт
     */
    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,bank,card,ewallet,marketplace,other'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'bik' => ['nullable', 'string', 'max:50'],
            'card_number' => ['nullable', 'string', 'max:4'],
            'marketplace_account_id' => ['nullable', 'exists:marketplace_accounts,id'],
            'marketplace' => ['nullable', 'string', 'max:32'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['company_id'] = $companyId;
        $data['balance'] = $data['initial_balance'] ?? 0;
        $data['currency_code'] = $data['currency_code'] ?? 'UZS';

        // Если это первый счёт или установлен is_default
        if (! empty($data['is_default'])) {
            CashAccount::byCompany($companyId)->update(['is_default' => false]);
        } elseif (CashAccount::byCompany($companyId)->count() === 0) {
            $data['is_default'] = true;
        }

        $account = CashAccount::create($data);

        return $this->successResponse($account, 201);
    }

    /**
     * Получить счёт по ID
     */
    public function show(int $id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $account = CashAccount::byCompany($companyId)->findOrFail($id);

        return $this->successResponse($account);
    }

    /**
     * Обновить счёт
     */
    public function update(Request $request, int $id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $account = CashAccount::byCompany($companyId)->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:cash,bank,card,ewallet,marketplace,other'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'bik' => ['nullable', 'string', 'max:50'],
            'card_number' => ['nullable', 'string', 'max:4'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($data['is_default'])) {
            CashAccount::byCompany($companyId)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $account->update($data);

        return $this->successResponse($account->fresh());
    }

    /**
     * Удалить счёт (только если нет транзакций)
     */
    public function destroy(int $id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $account = CashAccount::byCompany($companyId)->findOrFail($id);

        if ($account->transactions()->exists()) {
            return $this->errorResponse('Cannot delete account with transactions', 'validation_error', null, 422);
        }

        $account->delete();

        return $this->successResponse(['deleted' => true]);
    }

    /**
     * Транзакции счёта
     */
    public function transactions(Request $request, int $id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $account = CashAccount::byCompany($companyId)->findOrFail($id);

        $transactions = $account->transactions()
            ->with(['category', 'counterparty', 'employee'])
            ->when($request->from, fn ($q) => $q->where('transaction_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('transaction_date', '<=', $request->to))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 50);

        return $this->successResponse($transactions);
    }

    /**
     * Добавить приход
     */
    public function income(Request $request, int $id)
    {
        return $this->createTransaction($request, $id, CashTransaction::TYPE_INCOME);
    }

    /**
     * Добавить расход
     */
    public function expense(Request $request, int $id)
    {
        return $this->createTransaction($request, $id, CashTransaction::TYPE_EXPENSE);
    }

    /**
     * Перевод между счетами
     */
    public function transfer(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'from_account_id' => ['required', 'exists:cash_accounts,id'],
            'to_account_id' => ['required', 'exists:cash_accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $fromAccount = CashAccount::byCompany($companyId)->findOrFail($data['from_account_id']);
        $toAccount = CashAccount::byCompany($companyId)->findOrFail($data['to_account_id']);

        if ($fromAccount->balance < $data['amount']) {
            return $this->errorResponse('Insufficient funds', 'validation_error', null, 422);
        }

        DB::beginTransaction();
        try {
            $date = $data['transaction_date'] ?? now()->toDateString();

            // Списание
            $fromAccount->balance -= $data['amount'];
            $fromAccount->save();

            $outTransaction = CashTransaction::create([
                'company_id' => $companyId,
                'cash_account_id' => $fromAccount->id,
                'type' => CashTransaction::TYPE_TRANSFER_OUT,
                'amount' => $data['amount'],
                'balance_after' => $fromAccount->balance,
                'currency_code' => $fromAccount->currency_code,
                'transfer_to_account_id' => $toAccount->id,
                'description' => $data['description'] ?? "Перевод на {$toAccount->name}",
                'transaction_date' => $date,
                'status' => CashTransaction::STATUS_CONFIRMED,
                'created_by' => Auth::id(),
            ]);

            // Зачисление
            $toAccount->balance += $data['amount'];
            $toAccount->save();

            $inTransaction = CashTransaction::create([
                'company_id' => $companyId,
                'cash_account_id' => $toAccount->id,
                'type' => CashTransaction::TYPE_TRANSFER_IN,
                'amount' => $data['amount'],
                'balance_after' => $toAccount->balance,
                'currency_code' => $toAccount->currency_code,
                'transfer_from_transaction_id' => $outTransaction->id,
                'description' => $data['description'] ?? "Перевод с {$fromAccount->name}",
                'transaction_date' => $date,
                'status' => CashTransaction::STATUS_CONFIRMED,
                'created_by' => Auth::id(),
            ]);

            // Связываем транзакции
            $outTransaction->update(['transfer_from_transaction_id' => $inTransaction->id]);

            DB::commit();

            return $this->successResponse([
                'out' => $outTransaction,
                'in' => $inTransaction,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e->getMessage(), 'error', null, 500);
        }
    }

    /**
     * Сводка по всем счетам
     */
    public function summary(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $accounts = CashAccount::byCompany($companyId)->active()->get();

        // Группируем по валюте
        $byCurrency = $accounts->groupBy('currency_code')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_balance' => $group->sum('balance'),
            ];
        });

        // Группируем по типу
        $byType = $accounts->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_balance' => $group->sum('balance'),
            ];
        });

        return $this->successResponse([
            'total_accounts' => $accounts->count(),
            'by_currency' => $byCurrency,
            'by_type' => $byType,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Создать счёт для маркетплейса
     */
    public function createForMarketplace(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
        ]);

        $marketplaceAccount = MarketplaceAccount::where('company_id', $companyId)
            ->findOrFail($data['marketplace_account_id']);

        $account = CashAccount::getOrCreateForMarketplace($companyId, $marketplaceAccount);

        return $this->successResponse($account);
    }

    /**
     * Получить счета маркетплейсов
     */
    public function marketplaceAccounts(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $accounts = CashAccount::byCompany($companyId)
            ->where('type', CashAccount::TYPE_MARKETPLACE)
            ->with('marketplaceAccount')
            ->get();

        return $this->successResponse($accounts);
    }

    /**
     * Создать транзакцию (приход/расход)
     */
    protected function createTransaction(Request $request, int $accountId, string $type)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $account = CashAccount::byCompany($companyId)->findOrFail($accountId);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category_id' => ['nullable', 'exists:finance_categories,id'],
            'counterparty_id' => ['nullable', 'exists:counterparties,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'description' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        // Проверка на достаточность средств для расхода
        if ($type === CashTransaction::TYPE_EXPENSE && $account->balance < $data['amount']) {
            return $this->errorResponse('Insufficient funds', 'validation_error', null, 422);
        }

        DB::beginTransaction();
        try {
            // Обновляем баланс
            if ($type === CashTransaction::TYPE_INCOME) {
                $account->balance += $data['amount'];
            } else {
                $account->balance -= $data['amount'];
            }
            $account->save();

            // Создаём транзакцию
            $transaction = CashTransaction::create([
                'company_id' => $companyId,
                'cash_account_id' => $account->id,
                'type' => $type,
                'amount' => $data['amount'],
                'balance_after' => $account->balance,
                'currency_code' => $account->currency_code,
                'category_id' => $data['category_id'] ?? null,
                'counterparty_id' => $data['counterparty_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'description' => $data['description'] ?? null,
                'reference' => $data['reference'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'status' => CashTransaction::STATUS_CONFIRMED,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return $this->successResponse($transaction->load(['category', 'counterparty', 'employee']));
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e->getMessage(), 'error', null, 500);
        }
    }

    /**
     * Синхронизировать выплаты маркетплейсов
     */
    public function syncPayouts(Request $request, MarketplacePayoutSyncService $service)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'marketplace' => ['nullable', 'string', 'in:uzum,wb,ozon'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        try {
            if (($data['marketplace'] ?? null) === 'uzum') {
                $result = $service->syncUzum($companyId, $data['from'] ?? null, $data['to'] ?? null);
            } else {
                $result = $service->syncAll($companyId, $data['from'] ?? null, $data['to'] ?? null);
            }

            return $this->successResponse([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'error', null, 500);
        }
    }

    /**
     * Получить статистику выплат
     */
    public function payoutStats(MarketplacePayoutSyncService $service)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $stats = $service->getStats($companyId);
        $pending = $service->getPendingWithdrawals($companyId);

        return $this->successResponse([
            'stats' => $stats,
            'pending' => $pending,
        ]);
    }

    /**
     * Список выплат маркетплейсов
     */
    public function payouts(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $payouts = MarketplacePayout::byCompany($companyId)
            ->with(['marketplaceAccount', 'cashTransaction'])
            ->when($request->marketplace, fn ($q) => $q->forMarketplace($request->marketplace))
            ->when($request->account_id, fn ($q) => $q->forAccount($request->account_id))
            ->when($request->from, fn ($q) => $q->where('payout_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('payout_date', '<=', $request->to))
            ->orderByDesc('payout_date')
            ->paginate($request->per_page ?? 50);

        return $this->successResponse($payouts);
    }
}
