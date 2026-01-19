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

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = Employee::byCompany($companyId);

        if ($request->active_only) {
            $query->active();
        }
        if ($search = $request->get('query')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%');
            });
        }

        $employees = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $this->successResponse($employees);
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
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
        if (!$companyId) {
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
        if (!$companyId) {
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
        if (!$companyId) {
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
        if (!$companyId) {
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
