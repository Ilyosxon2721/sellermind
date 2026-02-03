<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Dialog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialogAdminController extends Controller
{
    /**
     * List all hidden/deleted private dialogs (admin only)
     */
    public function hiddenDialogs(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is admin
        if (! $user->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $dialogs = Dialog::onlyHidden()
            ->with(['user:id,name,email', 'company:id,name'])
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->private_only, fn ($q) => $q->where('is_private', true))
            ->orderByDesc('hidden_at')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'dialogs' => $dialogs->items(),
            'meta' => [
                'current_page' => $dialogs->currentPage(),
                'last_page' => $dialogs->lastPage(),
                'per_page' => $dialogs->perPage(),
                'total' => $dialogs->total(),
            ],
        ]);
    }

    /**
     * View a specific hidden dialog with all messages (admin only)
     */
    public function showHiddenDialog(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Check if user is admin
        if (! $user->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $dialog = Dialog::withHidden()
            ->with(['messages', 'user:id,name,email', 'company:id,name'])
            ->findOrFail($id);

        return response()->json([
            'dialog' => [
                'id' => $dialog->id,
                'company' => $dialog->company,
                'user' => $dialog->user,
                'title' => $dialog->title,
                'category' => $dialog->category,
                'is_private' => $dialog->is_private,
                'hidden_at' => $dialog->hidden_at,
                'created_at' => $dialog->created_at,
                'updated_at' => $dialog->updated_at,
                'messages' => MessageResource::collection($dialog->messages),
            ],
        ]);
    }

    /**
     * Get statistics on private dialogs (admin only)
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is admin
        if (! $user->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'stats' => [
                'total_dialogs' => Dialog::withHidden()->count(),
                'hidden_dialogs' => Dialog::onlyHidden()->count(),
                'private_dialogs' => Dialog::withHidden()->where('is_private', true)->count(),
                'hidden_private_dialogs' => Dialog::onlyHidden()->where('is_private', true)->count(),
            ],
        ]);
    }
}
