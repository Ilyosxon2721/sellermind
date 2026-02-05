<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceTransaction;
use App\Services\Finance\TransactionService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    use ApiResponder;

    public function __construct(protected TransactionService $service) {}

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = FinanceTransaction::byCompany($companyId)
            ->with(['category', 'subcategory', 'counterparty', 'employee']);

        if ($type = $request->type) {
            $query->where('type', $type);
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($categoryId = $request->category_id) {
            $query->where('category_id', $categoryId);
        }
        if ($from = $request->from) {
            $query->whereDate('transaction_date', '>=', $from);
        }
        if ($to = $request->to) {
            $query->whereDate('transaction_date', '<=', $to);
        }
        if ($search = $request->get('query')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%');
            });
        }

        $transactions = $query->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return $this->successResponse($transactions);
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $transaction = FinanceTransaction::byCompany($companyId)
            ->with(['category', 'subcategory', 'counterparty', 'employee', 'createdBy', 'confirmedBy'])
            ->findOrFail($id);

        return $this->successResponse($transaction);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $this->validateData($request);
        $data['company_id'] = $companyId;
        $data['created_by'] = Auth::id();

        $transaction = $this->service->create($data);

        return $this->successResponse($transaction->load(['category', 'subcategory', 'counterparty', 'employee']));
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $transaction = FinanceTransaction::byCompany($companyId)->findOrFail($id);

        if (! $transaction->isDraft()) {
            return $this->errorResponse('Only draft transactions can be edited', 'invalid_state', null, 422);
        }

        $data = $this->validateData($request);
        $transaction = $this->service->update($transaction, $data);

        return $this->successResponse($transaction->load(['category', 'subcategory', 'counterparty', 'employee']));
    }

    public function destroy($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $transaction = FinanceTransaction::byCompany($companyId)->find($id);

        if (! $transaction) {
            return $this->errorResponse('Транзакция не найдена', 'not_found', null, 404);
        }

        if ($transaction->isDeleted()) {
            return $this->errorResponse('Транзакция уже удалена', 'invalid_state', null, 422);
        }

        // Soft delete - mark as deleted but keep in database
        $transaction->softDelete();

        return $this->successResponse(['deleted' => true, 'transaction' => $transaction->fresh(['category', 'subcategory', 'counterparty', 'employee'])]);
    }

    public function restore($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $transaction = FinanceTransaction::byCompany($companyId)->find($id);

        if (! $transaction) {
            return $this->errorResponse('Транзакция не найдена', 'not_found', null, 404);
        }

        if (! $transaction->isDeleted()) {
            return $this->errorResponse('Транзакция не удалена', 'invalid_state', null, 422);
        }

        $transaction->restore();

        return $this->successResponse($transaction->fresh(['category', 'subcategory', 'counterparty', 'employee']));
    }

    public function confirm($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $transaction = FinanceTransaction::byCompany($companyId)->find($id);

        if (! $transaction) {
            return $this->errorResponse('Транзакция не найдена', 'not_found', null, 404);
        }

        if (! $transaction->isDraft()) {
            return $this->errorResponse('Транзакция уже подтверждена или отменена', 'invalid_state', null, 422);
        }

        $transaction->confirm(Auth::id());

        return $this->successResponse($transaction->fresh(['category', 'subcategory', 'counterparty', 'employee']));
    }

    public function cancel($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $transaction = FinanceTransaction::byCompany($companyId)->find($id);

        if (! $transaction) {
            return $this->errorResponse('Транзакция не найдена', 'not_found', null, 404);
        }

        if ($transaction->isCancelled()) {
            return $this->errorResponse('Транзакция уже отменена', 'invalid_state', null, 422);
        }

        $transaction->cancel();

        return $this->successResponse($transaction->fresh(['category', 'subcategory', 'counterparty', 'employee']));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:income,expense'],
            'category_id' => ['nullable', 'integer'],
            'subcategory_id' => ['nullable', 'integer'],
            'counterparty_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);
    }
}
