<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Http\Resources\GenerationTaskResource;
use App\Jobs\ProcessGenerationTaskJob;
use App\Models\GenerationTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerationTaskController extends Controller
{
    use HasPaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'status' => ['nullable', 'in:pending,in_progress,done,failed'],
            'type' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! $request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $query = GenerationTask::where('company_id', $request->company_id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $perPage = $this->getPerPage($request);

        $tasks = $query->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'tasks' => GenerationTaskResource::collection($tasks),
            'meta' => $this->paginationMeta($tasks),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'type' => ['required', 'string', 'in:cards_bulk,descriptions_update,images_bulk'],
            'input_data' => ['required', 'array'],
        ]);

        if (! $request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $task = GenerationTask::create([
            'company_id' => $request->company_id,
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'status' => 'pending',
            'input_data' => $request->input_data,
        ]);

        // Dispatch job to process the task
        ProcessGenerationTaskJob::dispatch($task);

        return response()->json([
            'task' => new GenerationTaskResource($task),
        ], 201);
    }

    public function show(Request $request, GenerationTask $task): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($task->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'task' => new GenerationTaskResource($task),
        ]);
    }

    public function cancel(Request $request, GenerationTask $task): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($task->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $task->isPending()) {
            return response()->json([
                'message' => 'Можно отменить только задачи со статусом "pending".',
            ], 422);
        }

        $task->markAsFailed('Отменено пользователем');

        return response()->json([
            'task' => new GenerationTaskResource($task),
        ]);
    }

    public function retry(Request $request, GenerationTask $task): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($task->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $task->isFailed()) {
            return response()->json([
                'message' => 'Можно повторить только неудавшиеся задачи.',
            ], 422);
        }

        $task->update([
            'status' => 'pending',
            'progress' => 0,
            'error_message' => null,
            'output_data' => null,
        ]);

        ProcessGenerationTaskJob::dispatch($task);

        return response()->json([
            'task' => new GenerationTaskResource($task),
        ]);
    }
}
