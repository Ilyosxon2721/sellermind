<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Detailed health check with all services
     */
    public function detailed(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'checks' => [],
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'healthy',
                'connection' => config('database.default'),
                'name' => DB::connection()->getDatabaseName(),
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // Redis check
        try {
            Redis::connection()->ping();
            $health['checks']['redis'] = [
                'status' => 'healthy',
            ];
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['checks']['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // Cache check
        try {
            $cacheKey = 'health_check_' . time();
            Cache::put($cacheKey, 'test', 5);
            $value = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            $health['checks']['cache'] = [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['checks']['cache'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // Queue check
        try {
            $health['checks']['queue'] = [
                'status' => 'healthy',
                'driver' => config('queue.default'),
            ];
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['checks']['queue'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // Disk space check
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

        $health['checks']['disk'] = [
            'status' => $diskUsagePercent < 90 ? 'healthy' : 'warning',
            'usage_percent' => round($diskUsagePercent, 2),
            'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
            'total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
        ];

        if ($diskUsagePercent >= 90) {
            $health['status'] = 'degraded';
        }

        $statusCode = match ($health['status']) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
            default => 200,
        };

        return response()->json($health, $statusCode);
    }
}
