<?php

namespace App\Console\Commands;

use App\Models\AIUsageLog;
use App\Models\Dialog;
use App\Models\GenerationTask;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanupOldData extends Command
{
    protected $signature = 'cleanup:old-data
                            {--days=90 : Days to keep data}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old dialogs, completed tasks, and usage logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = Carbon::now()->subDays($days);

        $this->info($dryRun ? "DRY RUN - showing what would be deleted:" : "Cleaning up data older than {$days} days...");
        $this->newLine();

        // Old dialogs without activity
        $oldDialogs = Dialog::where('updated_at', '<', $cutoff)->count();
        $this->line("Old dialogs: {$oldDialogs}");

        // Completed tasks
        $completedTasks = GenerationTask::whereIn('status', ['done', 'failed'])
            ->where('updated_at', '<', $cutoff)
            ->count();
        $this->line("Completed/failed tasks: {$completedTasks}");

        // Old usage logs
        $oldLogs = AIUsageLog::where('created_at', '<', $cutoff)->count();
        $this->line("Old AI usage logs: {$oldLogs}");

        $this->newLine();

        if ($dryRun) {
            $this->warn('No data was deleted (dry run mode)');
            return Command::SUCCESS;
        }

        if (!$this->confirm('Do you want to proceed with deletion?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        // Perform deletion
        Dialog::where('updated_at', '<', $cutoff)->delete();
        GenerationTask::whereIn('status', ['done', 'failed'])
            ->where('updated_at', '<', $cutoff)
            ->delete();
        AIUsageLog::where('created_at', '<', $cutoff)->delete();

        $this->info('Cleanup completed!');

        return Command::SUCCESS;
    }
}
