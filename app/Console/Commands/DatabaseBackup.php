<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--disk=local : The disk to store the backup}
                            {--keep=7 : Number of days to keep backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database backup...');

        $connection = config('database.default');
        $database = config("database.connections.{$connection}");
        $disk = $this->option('disk');
        $keepDays = (int) $this->option('keep');

        if (!$database) {
            $this->error('Database connection not found!');
            return self::FAILURE;
        }

        $filename = sprintf(
            'backup-%s-%s.sql',
            $database['database'] ?? 'database',
            now()->format('Y-m-d-H-i-s')
        );

        $backupPath = storage_path("app/backups/{$filename}");

        // Ensure backup directory exists
        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        try {
            // Create backup based on database driver
            match ($connection) {
                'mysql', 'mariadb' => $this->backupMysql($database, $backupPath),
                'pgsql' => $this->backupPostgres($database, $backupPath),
                'sqlite' => $this->backupSqlite($database, $backupPath),
                default => throw new \Exception("Backup not supported for {$connection}"),
            };

            $this->info("✓ Backup created: {$filename}");

            // Compress backup
            if (file_exists($backupPath)) {
                $gzPath = $backupPath . '.gz';
                $this->compressFile($backupPath, $gzPath);
                unlink($backupPath);
                $this->info("✓ Backup compressed: {$filename}.gz");
            }

            // Clean old backups
            $this->cleanOldBackups($keepDays);

            $this->info('✓ Database backup completed successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Backup MySQL database
     */
    protected function backupMysql(array $database, string $path): void
    {
        $host = $database['host'] ?? '127.0.0.1';
        $port = $database['port'] ?? 3306;
        $dbName = $database['database'];
        $username = $database['username'];
        $password = $database['password'] ?? '';

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($dbName),
            escapeshellarg($path)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('MySQL backup failed');
        }
    }

    /**
     * Backup PostgreSQL database
     */
    protected function backupPostgres(array $database, string $path): void
    {
        $host = $database['host'] ?? '127.0.0.1';
        $port = $database['port'] ?? 5432;
        $dbName = $database['database'];
        $username = $database['username'];
        $password = $database['password'] ?? '';

        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --dbname=%s > %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($dbName),
            escapeshellarg($path)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('PostgreSQL backup failed');
        }
    }

    /**
     * Backup SQLite database
     */
    protected function backupSqlite(array $database, string $path): void
    {
        $dbPath = $database['database'];

        if (!file_exists($dbPath)) {
            throw new \Exception("SQLite database not found: {$dbPath}");
        }

        if (!copy($dbPath, $path)) {
            throw new \Exception('SQLite backup failed');
        }
    }

    /**
     * Compress file using gzip
     */
    protected function compressFile(string $source, string $destination): void
    {
        $bufferSize = 4096;
        $file = fopen($source, 'rb');
        $gzFile = gzopen($destination, 'wb9');

        while (!feof($file)) {
            gzwrite($gzFile, fread($file, $bufferSize));
        }

        fclose($file);
        gzclose($gzFile);
    }

    /**
     * Clean old backups
     */
    protected function cleanOldBackups(int $keepDays): void
    {
        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/backup-*.sql.gz');
        $cutoffTime = now()->subDays($keepDays)->timestamp;

        $deleted = 0;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("✓ Cleaned {$deleted} old backup(s)");
        }
    }
}
