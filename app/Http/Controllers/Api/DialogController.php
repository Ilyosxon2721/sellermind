<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DialogResource;
use App\Models\Dialog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id') ?: ($request->user()->company_id ?? null);

        // Если компания не передана и у пользователя нет привязки — вернуть пустой ответ без ошибки валидации
        if (!$companyId) {
            return response()->json(['dialogs' => [], 'meta' => []], 200);
        }

        $category = $request->input('category');
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        // Мягкая проверка доступа: если метод есть и доступ запрещён, вернём пустой список (без 403, чтобы не ломать UI)
        if (method_exists($request->user(), 'hasCompanyAccess') && !$request->user()->hasCompanyAccess($companyId)) {
            return response()->json(['dialogs' => [], 'meta' => []], 200);
        }

        $query = Dialog::where('company_id', $companyId)
            ->where('user_id', $request->user()->id);

        if ($category) {
            $query->where('category', $category);
        }

        $dialogs = $query->with(['messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json([
            'dialogs' => DialogResource::collection($dialogs),
            'meta' => [
                'current_page' => $dialogs->currentPage(),
                'last_page' => $dialogs->lastPage(),
                'per_page' => $dialogs->perPage(),
                'total' => $dialogs->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id') ?: ($request->user()->company_id ?? null);
        if (!$companyId) {
            return response()->json(['message' => 'Компания не выбрана.'], 422);
        }

        // Мягкая проверка доступа: если метод есть и доступ запрещён — не создаём, но и не роняем клиент
        if (method_exists($request->user(), 'hasCompanyAccess') && !$request->user()->hasCompanyAccess($companyId)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $dialog = Dialog::create([
            'company_id' => $companyId,
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'category' => $request->input('category', 'general'),
        ]);

        return response()->json([
            'dialog' => new DialogResource($dialog),
        ], 201);
    }

    public function show(Request $request, Dialog $dialog): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($dialog->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($dialog->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Это не ваш диалог.'], 403);
        }

        return response()->json([
            'dialog' => new DialogResource($dialog->load('messages')),
        ]);
    }

    public function update(Request $request, Dialog $dialog): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($dialog->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($dialog->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Это не ваш диалог.'], 403);
        }

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:general,cards,reviews,images'],
        ]);

        $dialog->update($request->only(['title', 'category']));

        return response()->json([
            'dialog' => new DialogResource($dialog),
        ]);
    }

    public function destroy(Request $request, Dialog $dialog): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($dialog->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($dialog->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Это не ваш диалог.'], 403);
        }

        $dialog->delete();

        return response()->json([
            'message' => 'Диалог удалён.',
        ]);
    }
}
