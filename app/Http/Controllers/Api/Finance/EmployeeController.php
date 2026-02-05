<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Employee;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    use ApiResponder;

    /**
     * Get company employees from employees table
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employees = Employee::byCompany($companyId)
            ->with('user')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'middle_name' => $employee->middle_name,
                    'full_name' => $employee->full_name,
                    'name' => $employee->full_name,
                    'email' => $employee->email ?? $employee->user?->email,
                    'phone' => $employee->phone,
                    'position' => $employee->position,
                    'department' => $employee->department,
                    'base_salary' => $employee->base_salary ?? 0,
                    'salary_type' => $employee->salary_type ?? 'fixed',
                    'currency_code' => $employee->currency_code ?? 'UZS',
                    'hire_date' => $employee->hire_date?->format('Y-m-d'),
                    'is_active' => $employee->is_active,
                    'has_user_account' => $employee->user_id !== null,
                ];
            });

        return $this->successResponse($employees);
    }

    /**
     * Get role label in Russian
     */
    protected function getRoleLabel(string $role): string
    {
        return match ($role) {
            'owner' => 'Владелец',
            'admin' => 'Администратор',
            'manager' => 'Менеджер',
            'employee' => 'Сотрудник',
            'viewer' => 'Просмотр',
            default => ucfirst($role),
        };
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::byCompany($companyId)
            ->with('user')
            ->findOrFail($id);

        return $this->successResponse($employee);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $this->validateData($request);
        $data['company_id'] = $companyId;

        $employee = Employee::create($data);

        return $this->successResponse($employee);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::byCompany($companyId)->findOrFail($id);

        $data = $this->validateData($request, $employee->id);
        $employee->update($data);

        return $this->successResponse($employee->fresh());
    }

    public function destroy($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::byCompany($companyId)->findOrFail($id);

        // Проверяем есть ли связанные записи
        if ($employee->salaryItems()->exists() || $employee->transactions()->exists() || $employee->debts()->exists()) {
            // Вместо удаления - деактивируем
            $employee->update(['is_active' => false]);

            return $this->successResponse(['deactivated' => true, 'employee' => $employee]);
        }

        $employee->delete();

        return $this->successResponse(['deleted' => true]);
    }

    public function summary(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $total = Employee::byCompany($companyId)->count();
        $active = Employee::byCompany($companyId)->active()->count();
        $totalSalary = Employee::byCompany($companyId)->active()->sum('base_salary');

        return $this->successResponse([
            'total_employees' => $total,
            'active_employees' => $active,
            'total_monthly_salary' => $totalSalary,
        ]);
    }

    /**
     * Pay salary manually to employee
     */
    public function paySalary($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::byCompany($companyId)->findOrFail($id);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        // Create expense transaction for salary payment
        $transaction = \App\Models\Finance\FinanceTransaction::create([
            'company_id' => $companyId,
            'type' => 'expense',
            'category_id' => $this->getSalaryCategoryId($companyId),
            'employee_id' => $employee->id,
            'amount' => $data['amount'],
            'currency_code' => $employee->currency_code ?? 'UZS',
            'description' => $data['description'] ?? "Зарплата: {$employee->full_name}",
            'transaction_date' => $data['payment_date'] ?? now()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
            'created_by' => Auth::id(),
            'metadata' => [
                'type' => 'salary',
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'payment_method' => $data['payment_method'] ?? 'cash',
            ],
        ]);

        return $this->successResponse([
            'transaction' => $transaction,
            'employee' => $employee,
            'message' => 'Зарплата выплачена',
        ]);
    }

    /**
     * Add penalty/fine to employee
     */
    public function addPenalty($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::byCompany($companyId)->findOrFail($id);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'penalty_date' => ['nullable', 'date'],
        ]);

        // Create income transaction (penalty reduces what we owe to employee)
        $transaction = \App\Models\Finance\FinanceTransaction::create([
            'company_id' => $companyId,
            'type' => 'income',
            'category_id' => $this->getPenaltyCategoryId($companyId),
            'employee_id' => $employee->id,
            'amount' => $data['amount'],
            'currency_code' => $employee->currency_code ?? 'UZS',
            'description' => "Штраф: {$employee->full_name} - {$data['reason']}",
            'transaction_date' => $data['penalty_date'] ?? now()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
            'created_by' => Auth::id(),
            'metadata' => [
                'type' => 'penalty',
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'reason' => $data['reason'],
            ],
        ]);

        return $this->successResponse([
            'transaction' => $transaction,
            'employee' => $employee,
            'message' => 'Штраф добавлен',
        ]);
    }

    /**
     * Add expense for employee (advances, equipment, etc.)
     */
    public function addExpense($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::byCompany($companyId)->findOrFail($id);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:500'],
            'expense_date' => ['nullable', 'date'],
            'expense_type' => ['nullable', 'string', 'in:advance,equipment,training,travel,other'],
        ]);

        $expenseType = $data['expense_type'] ?? 'other';
        $typeLabels = [
            'advance' => 'Аванс',
            'equipment' => 'Оборудование',
            'training' => 'Обучение',
            'travel' => 'Командировка',
            'other' => 'Прочее',
        ];

        $transaction = \App\Models\Finance\FinanceTransaction::create([
            'company_id' => $companyId,
            'type' => 'expense',
            'category_id' => $this->getEmployeeExpenseCategoryId($companyId),
            'employee_id' => $employee->id,
            'amount' => $data['amount'],
            'currency_code' => $employee->currency_code ?? 'UZS',
            'description' => "{$typeLabels[$expenseType]}: {$employee->full_name} - {$data['description']}",
            'transaction_date' => $data['expense_date'] ?? now()->toDateString(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
            'created_by' => Auth::id(),
            'metadata' => [
                'type' => 'employee_expense',
                'expense_type' => $expenseType,
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
            ],
        ]);

        return $this->successResponse([
            'transaction' => $transaction,
            'employee' => $employee,
            'message' => 'Расход добавлен',
        ]);
    }

    /**
     * Get employee transactions history
     */
    public function transactions($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::byCompany($companyId)->findOrFail($id);

        $transactions = \App\Models\Finance\FinanceTransaction::where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $summary = [
            'total_salary_paid' => $transactions->where('metadata.type', 'salary')->sum('amount'),
            'total_penalties' => $transactions->where('metadata.type', 'penalty')->sum('amount'),
            'total_expenses' => $transactions->where('metadata.type', 'employee_expense')->sum('amount'),
        ];

        return $this->successResponse([
            'employee' => $employee,
            'transactions' => $transactions,
            'summary' => $summary,
        ]);
    }

    /**
     * Get or create salary category
     */
    protected function getSalaryCategoryId(int $companyId): int
    {
        $category = \App\Models\Finance\FinanceCategory::where('code', 'SALARY')
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->first();

        if (! $category) {
            $category = \App\Models\Finance\FinanceCategory::create([
                'company_id' => null,
                'type' => 'expense',
                'code' => 'SALARY',
                'name' => 'Зарплата',
                'is_system' => true,
            ]);
        }

        return $category->id;
    }

    /**
     * Get or create penalty category
     */
    protected function getPenaltyCategoryId(int $companyId): int
    {
        $category = \App\Models\Finance\FinanceCategory::where('code', 'EMPLOYEE_PENALTY')
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->first();

        if (! $category) {
            $category = \App\Models\Finance\FinanceCategory::create([
                'company_id' => null,
                'type' => 'income',
                'code' => 'EMPLOYEE_PENALTY',
                'name' => 'Штрафы сотрудников',
                'is_system' => true,
            ]);
        }

        return $category->id;
    }

    /**
     * Get or create employee expense category
     */
    protected function getEmployeeExpenseCategoryId(int $companyId): int
    {
        $category = \App\Models\Finance\FinanceCategory::where('code', 'EMPLOYEE_EXPENSE')
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->first();

        if (! $category) {
            $category = \App\Models\Finance\FinanceCategory::create([
                'company_id' => null,
                'type' => 'expense',
                'code' => 'EMPLOYEE_EXPENSE',
                'name' => 'Расходы на сотрудников',
                'is_system' => true,
            ]);
        }

        return $category->id;
    }

    protected function validateData(Request $request, ?int $employeeId = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'salary_type' => ['nullable', 'in:fixed,hourly,commission'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
