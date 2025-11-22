<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * ============================================================================
 * HEALTH CHECK SYSTEM
 * Professional application health monitoring
 * ============================================================================
 *
 * Features:
 * - Database connection check
 * - Redis connection check
 * - Filesystem check
 * - Queue system check
 * - Memory usage check
 * - Disk space check
 * - Performance metrics
 *
 * Usage:
 * $status = HealthCheck::check();
 * $detailed = HealthCheck::detailed();
 */
class HealthCheck
{
    /**
     * Health status constants
     */
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNHEALTHY = 'unhealthy';

    /**
     * Quick health check
     */
    public static function check(): array
    {
        $checks = [
            'database' => self::checkDatabase(),
            'redis' => self::checkRedis(),
            'filesystem' => self::checkFilesystem(),
        ];

        $status = self::STATUS_HEALTHY;
        $failedChecks = [];

        foreach ($checks as $name => $check) {
            if ($check['status'] === 'error') {
                $status = self::STATUS_UNHEALTHY;
                $failedChecks[] = $name;
            } elseif ($check['status'] === 'warning' && $status === self::STATUS_HEALTHY) {
                $status = self::STATUS_DEGRADED;
            }
        }

        return [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'environment' => EnvironmentManager::current(),
            'checks' => $checks,
            'failed_checks' => $failedChecks,
        ];
    }

    /**
     * Detailed health check with metrics
     */
    public static function detailed(): array
    {
        $basic = self::check();

        return array_merge($basic, [
            'metrics' => [
                'memory' => self::getMemoryMetrics(),
                'disk' => self::getDiskMetrics(),
                'performance' => self::getPerformanceMetrics(),
            ],
            'queue' => self::checkQueue(),
            'cache' => CacheManager::getHealthStatus(),
        ]);
    }

    /**
     * Check database connection and performance
     */
    private static function checkDatabase(): array
    {
        try {
            $start = microtime(true);

            // Test connection
            DB::connection()->getPdo();

            // Test simple query
            $result = DB::select('SELECT 1 as test');

            $duration = round((microtime(true) - $start) * 1000, 2);

            $status = 'ok';
            $message = 'Database connection is working';

            // Warning if query is slow
            if ($duration > 100) {
                $status = 'warning';
                $message = 'Database response is slow';
            }

            return [
                'status' => $status,
                'message' => $message,
                'response_time_ms' => $duration,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connections
     */
    private static function checkRedis(): array
    {
        $connections = ['default', 'cache', 'queue', 'session'];
        $results = [];
        $overallStatus = 'ok';

        foreach ($connections as $connection) {
            try {
                $start = microtime(true);

                Redis::connection($connection)->ping();

                $duration = round((microtime(true) - $start) * 1000, 2);

                $results[$connection] = [
                    'status' => 'ok',
                    'response_time_ms' => $duration,
                ];

                if ($duration > 50) {
                    $results[$connection]['status'] = 'warning';
                    $overallStatus = 'warning';
                }
            } catch (\Exception $e) {
                $results[$connection] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
                $overallStatus = 'error';
            }
        }

        return [
            'status' => $overallStatus,
            'message' => $overallStatus === 'ok' ? 'All Redis connections working' : 'Some Redis connections have issues',
            'connections' => $results,
        ];
    }

    /**
     * Check filesystem (storage directories)
     */
    private static function checkFilesystem(): array
    {
        $directories = [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        $issues = [];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                $issues[] = "Directory does not exist: {$dir}";
            } elseif (!File::isWritable($dir)) {
                $issues[] = "Directory is not writable: {$dir}";
            }
        }

        return [
            'status' => empty($issues) ? 'ok' : 'error',
            'message' => empty($issues) ? 'All directories are accessible' : 'Some directories have issues',
            'issues' => $issues,
        ];
    }

    /**
     * Check queue system
     */
    private static function checkQueue(): array
    {
        try {
            // Get queue size
            $size = Redis::connection('queue')->llen('queues:default');

            $status = 'ok';
            $message = 'Queue system is working';

            // Warning if queue is too large
            if ($size > 1000) {
                $status = 'warning';
                $message = 'Queue size is large, may need more workers';
            }

            return [
                'status' => $status,
                'message' => $message,
                'queue_size' => $size,
                'connection' => config('queue.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue system check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get memory usage metrics
     */
    private static function getMemoryMetrics(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        // Convert memory limit to bytes
        $limitBytes = self::convertToBytes($memoryLimit);
        $usagePercent = $limitBytes > 0 ? round(($memoryUsage / $limitBytes) * 100, 2) : 0;

        $status = 'ok';
        if ($usagePercent > 80) {
            $status = 'warning';
        } elseif ($usagePercent > 95) {
            $status = 'critical';
        }

        return [
            'status' => $status,
            'current' => self::formatBytes($memoryUsage),
            'peak' => self::formatBytes($memoryPeak),
            'limit' => $memoryLimit,
            'usage_percent' => $usagePercent,
        ];
    }

    /**
     * Get disk space metrics
     */
    private static function getDiskMetrics(): array
    {
        $storagePath = storage_path();
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = round(($usedSpace / $totalSpace) * 100, 2);

        $status = 'ok';
        if ($usagePercent > 80) {
            $status = 'warning';
        } elseif ($usagePercent > 95) {
            $status = 'critical';
        }

        return [
            'status' => $status,
            'free' => self::formatBytes($freeSpace),
            'used' => self::formatBytes($usedSpace),
            'total' => self::formatBytes($totalSpace),
            'usage_percent' => $usagePercent,
        ];
    }

    /**
     * Get performance metrics
     */
    private static function getPerformanceMetrics(): array
    {
        return [
            'uptime' => self::getUptime(),
            'request_count' => Cache::get('metrics:request_count', 0),
            'avg_response_time' => Cache::get('metrics:avg_response_time', 0),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    /**
     * Get application uptime
     */
    private static function getUptime(): string
    {
        $uptimeFile = storage_path('framework/uptime');

        if (!File::exists($uptimeFile)) {
            File::put($uptimeFile, time());
        }

        $startTime = (int) File::get($uptimeFile);
        $uptime = time() - $startTime;

        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $minutes = floor(($uptime % 3600) / 60);

        return "{$days}d {$hours}h {$minutes}m";
    }

    /**
     * Convert memory limit string to bytes
     */
    private static function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Format bytes to human-readable format
     */
    private static function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Health check endpoint response
     */
    public static function endpoint(): array
    {
        $health = self::check();

        // Add response format
        $httpStatus = match($health['status']) {
            self::STATUS_HEALTHY => 200,
            self::STATUS_DEGRADED => 200,
            self::STATUS_UNHEALTHY => 503,
            default => 500,
        };

        return [
            'http_status' => $httpStatus,
            'data' => $health,
        ];
    }

    /**
     * Detailed health check endpoint response
     */
    public static function detailedEndpoint(): array
    {
        $health = self::detailed();

        $httpStatus = match($health['status']) {
            self::STATUS_HEALTHY => 200,
            self::STATUS_DEGRADED => 200,
            self::STATUS_UNHEALTHY => 503,
            default => 500,
        };

        return [
            'http_status' => $httpStatus,
            'data' => $health,
        ];
    }
}
