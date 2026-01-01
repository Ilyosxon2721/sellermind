<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGenerationTaskJob;
use App\Models\GenerationTask;
use Illuminate\Console\Command;

class ProcessPendingTasks extends Command
{
    protected $signature = 'tasks:process {--limit=10 : Maximum number of tasks to process}';

    protected $description = 'Process pending generation tasks';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $tasks = GenerationTask::where('status', 'pending')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No pending tasks found.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$tasks->count()} pending tasks...\n");

        $bar = $this->output->createProgressBar($tasks->count());
        $bar->start();

        foreach ($tasks as $task) {
            ProcessGenerationTaskJob::dispatch($task);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('All tasks dispatched to queue.');
        $this->line('Run "php artisan queue:work" to process them.');

        return Command::SUCCESS;
    }
}
