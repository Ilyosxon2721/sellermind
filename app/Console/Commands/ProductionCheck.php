h<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ProductionCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:check
                            {--fix : Automatically fix issues where possible}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if the application is ready for production deployment';

    protected int $errors = 0;
    protected int $warnings = 0;
    protected int $passed = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  SellerMind AI - Production Readiness Check');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Run all checks
        $this->checkEnvironment();
        $this->checkSecurityKeys();
        $this->checkDebugMode();
        $this->checkDatabase();
        $this->checkRedis();
        $this->checkStorage();
        $this->checkPermissions();
        $this->checkEnvVariables();
        $this->checkOptimization();

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->line("✓ Passed:   <fg=green>{$this->passed}</>");
        $this->line("⚠ Warnings: <fg=yellow>{$this->warnings}</>");
        $this->line("✗ Errors:   <fg=red>{$this->errors}</>");
        $this->newLine();

        if ($this->errors > 0) {
            $this->error('❌ Application is NOT ready for production!');
            return self::FAILURE;
        } elseif ($this->warnings > 0) {
            $this->warn('⚠️  Application can be deployed but has warnings.');
            return self::SUCCESS;
        } else {
            $this->info('✅ Application is READY for production!');
            return self::SUCCESS;
        }
    }

    protected function checkEnvironment(): void
    {
        $this->info('🔍 Environment Configuration');
        $this->newLine();

        $env = app()->environment();

        if ($env === 'production') {
            $this->pass('Environment set to production');
        } else {
            $this->warn("Environment is set to '{$env}' (expected: production)");
        }
    }

    protected function checkSecurityKeys(): void
    {
        $this->newLine();
        $this->info('🔒 Security Keys');
        $this->newLine();

        // APP_KEY check
        $appKey = config('app.key');
        if (empty($appKey)) {
            $this->failCheck('APP_KEY is not set! Run: php artisan key:generate');
        } else {
            $this->pass('APP_KEY is set');
        }

        // Reverb keys check (if using WebSockets)
        $reverbKey = env('REVERB_APP_KEY');
        $reverbSecret = env('REVERB_APP_SECRET');

        if (empty($reverbKey) || $reverbKey === 'your-reverb-app-key-change-me') {
            $this->warn('REVERB_APP_KEY is not set or using default value');
        } else {
            $this->pass('REVERB_APP_KEY is configured');
        }

        if (empty($reverbSecret) || $reverbSecret === 'your-app-secret') {
            $this->warn('REVERB_APP_SECRET is not set or using default value');
        } else {
            $this->pass('REVERB_APP_SECRET is configured');
        }
    }

    protected function checkDebugMode(): void
    {
        $this->newLine();
        $this->info('🐛 Debug Mode');
        $this->newLine();

        if (config('app.debug')) {
            $this->failCheck('APP_DEBUG is enabled! Set APP_DEBUG=false in production');
        } else {
            $this->pass('Debug mode is disabled');
        }
    }

    protected function checkDatabase(): void
    {
        $this->newLine();
        $this->info('💾 Database');
        $this->newLine();

        try {
            DB::connection()->getPdo();
            $this->pass('Database connection successful');

            $driver = config('database.default');
            $this->line("   Driver: {$driver}");

            // Check migrations
            try {
                $pending = DB::table('migrations')->count();
                $this->pass("Migrations table exists ({$pending} migrations)");
            } catch (\Exception $e) {
                $this->warn('Cannot check migrations table');
            }

        } catch (\Exception $e) {
            $this->failCheck('Database connection failed: ' . $e->getMessage());
        }
    }

    protected function checkRedis(): void
    {
        $this->newLine();
        $this->info('📦 Redis');
        $this->newLine();

        try {
            Redis::connection()->ping();
            $this->pass('Redis connection successful');
        } catch (\Exception $e) {
            $this->warn('Redis connection failed (optional if not using): ' . $e->getMessage());
        }
    }

    protected function checkStorage(): void
    {
        $this->newLine();
        $this->info('💿 Storage');
        $this->newLine();

        $storagePath = storage_path();
        if (is_writable($storagePath)) {
            $this->pass('Storage directory is writable');
        } else {
            $this->failCheck('Storage directory is not writable');
        }

        // Check disk space
        $free = disk_free_space($storagePath);
        $total = disk_total_space($storagePath);
        $usedPercent = (($total - $free) / $total) * 100;

        if ($usedPercent > 90) {
            $this->failCheck(sprintf('Disk space critically low: %.1f%% used', $usedPercent));
        } elseif ($usedPercent > 80) {
            $this->warn(sprintf('Disk space low: %.1f%% used', $usedPercent));
        } else {
            $this->pass(sprintf('Disk space OK: %.1f%% used', $usedPercent));
        }
    }

    protected function checkPermissions(): void
    {
        $this->newLine();
        $this->info('🔐 File Permissions');
        $this->newLine();

        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                $this->warn("Directory does not exist: {$dir}");
                continue;
            }

            if (is_writable($dir)) {
                $this->pass(basename($dir) . ' is writable');
            } else {
                $this->failCheck(basename($dir) . ' is not writable');
            }
        }
    }

    protected function checkEnvVariables(): void
    {
        $this->newLine();
        $this->info('⚙️  Critical Environment Variables');
        $this->newLine();

        $required = [
            'DB_DATABASE' => 'Database name',
            'DB_USERNAME' => 'Database username',
            'DB_PASSWORD' => 'Database password',
        ];

        foreach ($required as $key => $description) {
            $value = env($key);
            if (empty($value)) {
                $this->failCheck("{$key} is not set ({$description})");
            } else {
                $this->pass("{$key} is configured");
            }
        }

        // Check optional but recommended
        $recommended = [
            'OPENAI_API_KEY' => 'OpenAI integration',
            'MAIL_FROM_ADDRESS' => 'Email notifications',
            'REDIS_HOST' => 'Redis cache',
        ];

        foreach ($recommended as $key => $description) {
            $value = env($key);
            if (empty($value)) {
                $this->warn("{$key} is not set (needed for: {$description})");
            }
        }
    }

    protected function checkOptimization(): void
    {
        $this->newLine();
        $this->info('⚡ Optimization');
        $this->newLine();

        // Check if config is cached
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->pass('Configuration is cached');
        } else {
            $this->warn('Configuration not cached. Run: php artisan config:cache');
        }

        // Check if routes are cached
        if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            $this->pass('Routes are cached');
        } else {
            $this->warn('Routes not cached. Run: php artisan route:cache');
        }

        // Check if views are cached
        $viewsPath = storage_path('framework/views');
        $viewFiles = glob($viewsPath . '/*.php');
        if (!empty($viewFiles)) {
            $this->pass('Views are compiled');
        } else {
            $this->warn('Views not compiled. Run: php artisan view:cache');
        }
    }

    protected function pass(string $message): void
    {
        $this->line("   <fg=green>✓</> {$message}");
        $this->passed++;
    }

    protected function warn(string $message): void
    {
        $this->line("   <fg=yellow>⚠</> {$message}");
        $this->warnings++;
    }

    protected function failCheck(string $message): void
    {
        $this->line("   <fg=red>✗</> {$message}");
        $this->errors++;
    }
}
