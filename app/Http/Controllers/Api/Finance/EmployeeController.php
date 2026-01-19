<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Employee;
use App\Models\UserCompanyRole;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    use ApiResponder;

    /**
     * Get company employees (users attached to the company via UserCompanyRole)
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        // Get users from UserCompanyRole (including owner)
        $companyUsers = UserCompanyRole::with('user')
            ->where('company_id', $companyId)
            ->get()
            ->map(function ($role) {
                $user = $role->user;
                if (!$user) return null;

                // Parse name into first_name and last_name
                $nameParts = explode(' ', $user->name ?? '', 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';

                // Get employee record if exists (for salary info)
                $employee = Employee::where('user_id', $user->id)->first();

                return [
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => null,
                    'position' => $this->getRoleLabel($role->role),
                    'role' => $role->role,
                    'is_owner' => $role->role === 'owner',
                    'base_salary' => $employee?->base_salary ?? 0,
                    'currency_code' => $employee?->currency_code ?? 'UZS',
                    'hire_date' => $employee?->hire_date ?? $user->created_at?->toDateString(),
                    'is_active' => true,
                    'employee_id' => $employee?->id, // Link to Employee record if exists
                ];
            })
            ->filter()
            ->sortBy('name')
            ->values();

        return $this->successResponse($companyUsers);
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

        // Count company users from UserCompanyRole
        $total = UserCompanyRole::where('company_id', $companyId)->count();
        $active = $total; // All company users are active

        // Get total salary from Employee records linked to company users
        $userIds = UserCompanyRole::where('company_id', $companyId)->pluck('user_id');
        $totalSalary = Employee::whereIn('user_id', $userIds)->sum('base_salary');

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
