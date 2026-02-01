<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Counterparty;
use App\Models\CounterpartyContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CounterpartyController extends Controller
{
    /**
     * List all counterparties
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        
        $query = Counterparty::query()
            ->byCompany($companyId)
            ->with(['contracts' => fn($q) => $q->active()])
            ->search($request->get('search'));
        
        if ($request->get('type')) {
            $query->where('type', $request->get('type'));
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->boolean('customers_only')) {
            $query->customers();
        }
        
        if ($request->boolean('suppliers_only')) {
            $query->suppliers();
        }
        
        $perPage = min((int) $request->get('per_page', 20), 100);
        $counterparties = $query->orderBy('name')->paginate($perPage);
        
        return response()->json([
            'data' => $counterparties->items(),
            'meta' => [
                'current_page' => $counterparties->currentPage(),
                'last_page' => $counterparties->lastPage(),
                'per_page' => $counterparties->perPage(),
                'total' => $counterparties->total(),
            ]
        ]);
    }
    
    /**
     * Get single counterparty
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        
        $counterparty = Counterparty::query()
            ->byCompany($companyId)
            ->with('contracts')
            ->findOrFail($id);
        
        return response()->json(['data' => $counterparty]);
    }
    
    /**
     * Create counterparty
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:individual,legal',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'inn' => 'nullable|string|max:20',
            'kpp' => 'nullable|string|max:20',
            'ogrn' => 'nullable|string|max:20',
            'okpo' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:255',
            'legal_address' => 'nullable|string|max:500',
            'actual_address' => 'nullable|string|max:500',
            'bank_name' => 'nullable|string|max:255',
            'bank_bik' => 'nullable|string|max:20',
            'bank_account' => 'nullable|string|max:30',
            'bank_corr_account' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'contact_position' => 'nullable|string|max:100',
            'is_supplier' => 'boolean',
            'is_customer' => 'boolean',
            'notes' => 'nullable|string|max:2000',
        ]);
        
        $validated['company_id'] = $this->getCompanyId($request);
        $validated['is_active'] = true;
        
        $counterparty = Counterparty::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $counterparty,
            'message' => 'Контрагент создан'
        ], 201);
    }
    
    /**
     * Update counterparty
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $counterparty = Counterparty::byCompany($companyId)->findOrFail($id);
        
        $validated = $request->validate([
            'type' => 'in:individual,legal',
            'name' => 'string|max:255',
            'short_name' => 'nullable|string|max:100',
            'inn' => 'nullable|string|max:20',
            'kpp' => 'nullable|string|max:20',
            'ogrn' => 'nullable|string|max:20',
            'okpo' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:255',
            'legal_address' => 'nullable|string|max:500',
            'actual_address' => 'nullable|string|max:500',
            'bank_name' => 'nullable|string|max:255',
            'bank_bik' => 'nullable|string|max:20',
            'bank_account' => 'nullable|string|max:30',
            'bank_corr_account' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'contact_position' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_supplier' => 'boolean',
            'is_customer' => 'boolean',
            'notes' => 'nullable|string|max:2000',
        ]);
        
        $counterparty->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $counterparty->fresh(),
            'message' => 'Контрагент обновлён'
        ]);
    }
    
    /**
     * Delete counterparty
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $counterparty = Counterparty::byCompany($companyId)->findOrFail($id);
        
        $counterparty->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Контрагент удалён'
        ]);
    }
    
    /**
     * Get contracts for counterparty
     */
    public function contracts(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $counterparty = Counterparty::byCompany($companyId)->findOrFail($id);
        
        $contracts = $counterparty->contracts()->orderByDesc('date')->get();
        
        return response()->json(['data' => $contracts]);
    }
    
    /**
     * Create contract for counterparty
     */
    public function storeContract(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $counterparty = Counterparty::byCompany($companyId)->findOrFail($id);
        
        $validated = $request->validate([
            'number' => 'required|string|max:50',
            'name' => 'nullable|string|max:255',
            'date' => 'required|date',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'commission_percent' => 'required|numeric|min:0|max:100',
            'commission_type' => 'in:sales,profit',
            'commission_includes_vat' => 'boolean',
            'status' => 'in:draft,active,suspended,terminated',
            'payment_days' => 'integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'currency' => 'string|max:3',
            'notes' => 'nullable|string|max:2000',
        ]);
        
        $validated['counterparty_id'] = $counterparty->id;
        $validated['company_id'] = $companyId;
        
        $contract = CounterpartyContract::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $contract,
            'message' => 'Договор создан'
        ], 201);
    }
    
    /**
     * Update contract
     */
    public function updateContract(Request $request, int $id, int $contractId): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $contract = CounterpartyContract::byCompany($companyId)
            ->where('counterparty_id', $id)
            ->findOrFail($contractId);
        
        $validated = $request->validate([
            'number' => 'string|max:50',
            'name' => 'nullable|string|max:255',
            'date' => 'date',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'commission_percent' => 'numeric|min:0|max:100',
            'commission_type' => 'in:sales,profit',
            'commission_includes_vat' => 'boolean',
            'status' => 'in:draft,active,suspended,terminated,expired',
            'payment_days' => 'integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'currency' => 'string|max:3',
            'notes' => 'nullable|string|max:2000',
        ]);
        
        $contract->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $contract->fresh(),
            'message' => 'Договор обновлён'
        ]);
    }
    
    /**
     * Delete contract
     */
    public function destroyContract(Request $request, int $id, int $contractId): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $contract = CounterpartyContract::byCompany($companyId)
            ->where('counterparty_id', $id)
            ->findOrFail($contractId);
        
        $contract->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Договор удалён'
        ]);
    }
    
    private function getCompanyId(Request $request): int
    {
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }

        abort(403, 'Компания пользователя не определена');
    }
}
