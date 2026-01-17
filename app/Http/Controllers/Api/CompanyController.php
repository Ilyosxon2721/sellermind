<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\UserCompanyRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = $request->user()
            ->companies()
            ->withCount('products')
            ->get();

        return response()->json([
            'companies' => CompanyResource::collection($companies),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $company = Company::create([
            'name' => $request->name,
        ]);

        UserCompanyRole::create([
            'user_id' => $request->user()->id,
            'company_id' => $company->id,
            'role' => 'owner',
        ]);

        // Update user's company_id to the newly created company
        $user = $request->user();
        if (!$user->company_id) {
            $user->company_id = $company->id;
            $user->save();
            // Refresh the user instance
            $user->refresh();
        }

        return response()->json([
            'company' => new CompanyResource($company),
            'user' => $user, // Return updated user so client can update their state
        ], 201);
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($company->id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $company->loadCount('products');

        return response()->json([
            'company' => new CompanyResource($company),
        ]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        if (!$request->user()->isOwnerOf($company->id)) {
            return response()->json(['message' => 'Только владелец может изменять компанию.'], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $company->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'company' => new CompanyResource($company),
        ]);
    }

    public function destroy(Request $request, Company $company): JsonResponse
    {
        if (!$request->user()->isOwnerOf($company->id)) {
            return response()->json(['message' => 'Только владелец может удалить компанию.'], 403);
        }

        $company->delete();

        return response()->json([
            'message' => 'Компания удалена.',
        ]);
    }

    public function addMember(Request $request, Company $company): JsonResponse
    {
        if (!$request->user()->isOwnerOf($company->id)) {
            return response()->json(['message' => 'Только владелец может добавлять участников.'], 403);
        }

        $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'max:100'],
        ]);

        // Нельзя создавать второго владельца
        $role = strtolower(trim($request->role));
        if ($role === 'owner' || $role === 'владелец') {
            return response()->json([
                'message' => 'Нельзя назначить роль "владелец". Владелец может быть только один. Используйте функцию передачи владения.'
            ], 422);
        }

        try {
            // Проверяем, существует ли пользователь с таким email
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user) {
                // Пользователь существует - проверяем, не в компании ли он уже
                if ($user->hasCompanyAccess($company->id)) {
                    return response()->json(['message' => 'Пользователь с таким email уже в компании.'], 422);
                }
            } else {
                // Создаём нового пользователя
                $user = \App\Models\User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'company_id' => $company->id,
                    'locale' => 'ru',
                ]);
            }

            // Проверяем, не добавлен ли уже пользователь в эту компанию
            $existingRole = UserCompanyRole::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->first();

            if ($existingRole) {
                return response()->json(['message' => 'Пользователь уже добавлен в компанию.'], 422);
            }

            // Добавляем в компанию
            UserCompanyRole::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'role' => $request->role,
            ]);

            // Устанавливаем компанию как основную, если у пользователя её нет
            if (!$user->company_id) {
                $user->update(['company_id' => $company->id]);
            }

            return response()->json([
                'message' => 'Сотрудник добавлен.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Failed to add company member', [
                'company_id' => $company->id,
                'email' => $request->email,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Проверяем на дубликат email
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json(['message' => 'Пользователь с таким email уже существует.'], 422);
            }

            return response()->json(['message' => 'Ошибка при создании пользователя: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            \Log::error('Failed to add company member', [
                'company_id' => $company->id,
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Передать владение компанией другому пользователю
     */
    public function transferOwnership(Request $request, Company $company): JsonResponse
    {
        if (!$request->user()->isOwnerOf($company->id)) {
            return response()->json(['message' => 'Только владелец может передать владение.'], 403);
        }

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newOwner = \App\Models\User::findOrFail($request->user_id);

        // Проверяем, что новый владелец уже в компании
        if (!$newOwner->hasCompanyAccess($company->id)) {
            return response()->json(['message' => 'Пользователь должен быть сотрудником компании.'], 422);
        }

        // Меняем роль текущего владельца на менеджера
        UserCompanyRole::where('company_id', $company->id)
            ->where('user_id', $request->user()->id)
            ->update(['role' => 'manager']);

        // Назначаем нового владельца
        UserCompanyRole::where('company_id', $company->id)
            ->where('user_id', $newOwner->id)
            ->update(['role' => 'owner']);

        return response()->json([
            'message' => 'Владение компанией передано пользователю ' . $newOwner->name,
        ]);
    }

    public function getMembers(Request $request, Company $company): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($company->id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $members = $company->users()->get();

        return response()->json([
            'members' => $members,
        ]);
    }

    public function removeMember(Request $request, Company $company, int $userId): JsonResponse
    {
        if (!$request->user()->isOwnerOf($company->id)) {
            return response()->json(['message' => 'Только владелец может удалять участников.'], 403);
        }

        if ($userId === $request->user()->id) {
            return response()->json(['message' => 'Нельзя удалить себя из компании.'], 422);
        }

        UserCompanyRole::where('company_id', $company->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'message' => 'Участник удалён.',
        ]);
    }

    /**
     * Получить настройки компании
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = Company::find($user->company_id);

        if (!$company) {
            return response()->json(['message' => 'Компания не найдена.'], 404);
        }

        return response()->json([
            'success' => true,
            'settings' => $company->getAllSettings(),
        ]);
    }

    /**
     * Обновить настройки компании
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = Company::find($user->company_id);

        if (!$company) {
            return response()->json(['message' => 'Компания не найдена.'], 404);
        }

        if (!$user->isOwnerOf($company->id)) {
            return response()->json(['message' => 'Только владелец может изменять настройки.'], 403);
        }

        $validated = $request->validate([
            'auto_sync_stock_on_link' => 'boolean',
            'auto_sync_stock_on_change' => 'boolean',
            'stock_sync_enabled' => 'boolean',
        ]);

        $settings = $company->settings ?? [];
        foreach ($validated as $key => $value) {
            $settings[$key] = $value;
        }
        $company->settings = $settings;
        $company->save();

        return response()->json([
            'success' => true,
            'message' => 'Настройки сохранены',
            'settings' => $company->getAllSettings(),
        ]);
    }
}
