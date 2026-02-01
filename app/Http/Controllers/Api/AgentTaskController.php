<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\AgentTaskResource;
use App\Http\Resources\AgentTaskRunResource;
use App\Jobs\RunAgentTaskRunJob;
use App\Jobs\ContinueAgentRunJob;
use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\AgentTaskRun;
use App\Services\Agent\AgentTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentTaskController extends Controller
{
    public function __construct(
        private AgentTaskService $taskService
    ) {}

    /**
     * List all agents (for task creation form)
     */
    public function agents(): JsonResponse
    {
        $agents = Agent::active()->get();

        return response()->json([
            'agents' => AgentResource::collection($agents),
        ]);
    }

    /**
     * List user's tasks
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $tasks = AgentTask::forUser($user->id)
            ->with(['agent', 'latestRun'])
            ->withCount('runs')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'tasks' => AgentTaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Create a new task
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'agent_id' => ['required', 'exists:agents,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'input_payload' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $agent = Agent::findOrFail($request->agent_id);

        // Check company access if company_id provided
        if ($request->company_id && !$user->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ к компании запрещён.'], 403);
        }

        $task = $this->taskService->createTask($user, $agent, $request->only([
            'title',
            'description',
            'company_id',
            'product_id',
            'type',
            'input_payload',
        ]));

        $task->load(['agent', 'latestRun']);

        return response()->json([
            'task' => new AgentTaskResource($task),
            'message' => 'Задача успешно создана.',
        ], 201);
    }

    /**
     * Show task details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $task = AgentTask::with(['agent', 'latestRun', 'runs' => function ($query) {
            $query->latest()->limit(10);
        }])
        ->withCount('runs')
        ->findOrFail($id);

        if ($task->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'task' => new AgentTaskResource($task),
            'runs' => AgentTaskRunResource::collection($task->runs),
        ]);
    }

    /**
     * Run the task (create a new run and dispatch job)
     */
    public function run(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $task = AgentTask::where('user_id', $user->id)->findOrFail($id);

        // Create a new run
        $run = $this->taskService->createRun($task);

        // Dispatch job to queue
        RunAgentTaskRunJob::dispatch($run->id);

        return response()->json([
            'run' => new AgentTaskRunResource($run),
            'message' => 'Задача поставлена в очередь на выполнение.',
        ]);
    }

    /**
     * Show run details with messages
     */
    public function showRun(Request $request, int $runId): JsonResponse
    {
        $user = $request->user();

        $run = AgentTaskRun::with(['task', 'messages'])
            ->findOrFail($runId);

        if ($run->task->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'run' => new AgentTaskRunResource($run),
        ]);
    }

    /**
     * Send follow-up message to a run
     */
    public function sendMessage(Request $request, int $runId): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $user = $request->user();

        $run = AgentTaskRun::with(['task'])->findOrFail($runId);

        if ($run->task->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Only allow follow-up on completed runs
        if ($run->status !== AgentTaskRun::STATUS_SUCCESS) {
            return response()->json(['message' => 'Можно продолжить только успешно завершённый диалог.'], 400);
        }

        // Reset run status to pending for continuation
        $run->update([
            'status' => AgentTaskRun::STATUS_PENDING,
            'finished_at' => null,
        ]);

        // Dispatch continuation job
        ContinueAgentRunJob::dispatch($run->id, $request->message);

        $run->load('messages');

        return response()->json([
            'run' => new AgentTaskRunResource($run),
            'message' => 'Сообщение отправлено.',
        ]);
    }

    /**
     * Delete a task
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $task = AgentTask::where('user_id', $user->id)->findOrFail($id);

        $task->delete();

        return response()->json([
            'message' => 'Задача удалена.',
        ]);
    }
}
