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

class RunAgentTaskRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 60;

    public function __construct(
        private int $runId
    ) {}

    public function handle(AgentRunner $agentRunner): void
    {
        $run = AgentTaskRun::with(['task.agent'])->find($this->runId);

        if (!$run) {
            Log::warning("AgentTaskRun not found", ['run_id' => $this->runId]);
            return;
        }

        if (!$run->isPending()) {
            Log::info("AgentTaskRun already processed", [
                'run_id' => $this->runId,
                'status' => $run->status,
            ]);
            return;
        }

        $agentRunner->run($run);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("RunAgentTaskRunJob failed", [
            'run_id' => $this->runId,
            'error' => $exception->getMessage(),
        ]);

        $run = AgentTaskRun::find($this->runId);
        if ($run && !$run->isFinished()) {
            $run->markAsFailed("Job failed: {$exception->getMessage()}");
        }
    }
}
