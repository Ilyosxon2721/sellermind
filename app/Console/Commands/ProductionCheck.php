<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionCheck extends Command
{
    protected $signature = 'production:check';

    protected $description = 'Production readiness check (deprecated)';

    public function handle(): int
    {
        $this->info('This command has been removed.');

        return self::SUCCESS;
    }
}
