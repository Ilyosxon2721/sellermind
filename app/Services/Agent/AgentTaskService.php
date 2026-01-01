<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\AgentTaskRun;
use App\Models\User;

class AgentTaskService
{
    public function createTask(
        User $user,
        Agent $agent,
        array $data
    ): AgentTask {
        return AgentTask::create([
            'user_id' => $user->id,
            'company_id' => $data['company_id'] ?? null,
            'agent_id' => $agent->id,
            'product_id' => $data['product_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'general',
            'input_payload' => $data['input_payload'] ?? null,
            'status' => 'active',
        ]);
    }

    public function createRun(AgentTask $task): AgentTaskRun
    {
        return AgentTaskRun::create([
            'agent_task_id' => $task->id,
            'status' => AgentTaskRun::STATUS_PENDING,
        ]);
    }

    public function markRunStarted(AgentTaskRun $run): void
    {
        $run->markAsRunning();
    }

    public function markRunSuccess(AgentTaskRun $run, ?string $summary = null): void
    {
        $run->markAsSuccess($summary);
    }

    public function markRunFailed(AgentTaskRun $run, string $error): void
    {
        $run->markAsFailed($error);
    }

    public function getUserTasks(User $user, int $perPage = 15)
    {
        return AgentTask::forUser($user->id)
            ->with(['agent', 'latestRun'])
            ->latest()
            ->paginate($perPage);
    }

    public function getTaskRuns(AgentTask $task, int $perPage = 10)
    {
        return $task->runs()
            ->with('messages')
            ->latest()
            ->paginate($perPage);
    }
}
