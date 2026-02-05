<?php

namespace App\Jobs;

use App\Models\AgentTaskRun;
use App\Services\Agent\AgentRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ContinueAgentRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(
        private int $runId,
        private string $userMessage
    ) {}

    public function handle(AgentRunner $agentRunner): void
    {
        $run = AgentTaskRun::with(['task.agent', 'messages'])->find($this->runId);

        if (! $run) {
            Log::warning('AgentTaskRun not found for continuation', ['run_id' => $this->runId]);

            return;
        }

        $agentRunner->continueRun($run, $this->userMessage);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ContinueAgentRunJob failed', [
            'run_id' => $this->runId,
            'error' => $exception->getMessage(),
        ]);

        $run = AgentTaskRun::find($this->runId);
        if ($run && ! $run->isFinished()) {
            $run->markAsFailed("Continuation failed: {$exception->getMessage()}");
        }
    }
}
