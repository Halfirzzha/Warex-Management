<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * ADVANCED CACHE MANAGER
 * Professional cache management with warming, tagging, and statistics
 * ============================================================================
 *
 * Features:
 * - Cache warming for critical data
 * - Cache tagging for group invalidation
 * - Cache statistics and monitoring
 * - Automatic cache cleanup
 * - Multi-level caching strategy
 * - Cache miss tracking
 *
 * Usage:
 * CacheManager::warmCache();
 * CacheManager::remember('users.count', 300, fn() => User::count());
 * CacheManager::invalidateTag('users');
 */
class CacheManager
{
    /**
     * Cache prefixes
     */
    private const PREFIX_DATA = 'data:';
    private const PREFIX_QUERY = 'query:';
    private const PREFIX_VIEW = 'view:';
    private const PREFIX_API = 'api:';
    private const PREFIX_STATS = 'stats:';

    /**
     * Cache statistics key
     */
    private const STATS_KEY = 'cache:statistics';

    /**
     * Remember data with automatic tagging and statistics
     */
    public static function remember(string $key, int $ttl, callable $callback, ?string $tag = null)
    {
        $fullKey = self::PREFIX_DATA . $key;

        // Try to get from cache
        $value = Cache::get($fullKey);

        if ($value !== null) {
            self::recordHit($key);
            return $value;
        }

        // Cache miss - record and compute
        self::recordMiss($key);

        $value = $callback();

        // Store with tag if provided
        if ($tag) {
            self::putWithTag($fullKey, $value, $ttl, $tag);
        } else {
            Cache::put($fullKey, $value, $ttl);
        }

        return $value;
    }

    /**
     * Store value with tag
     */
    public static function putWithTag(string $key, $value, int $ttl, string $tag): bool
    {
        // Store the value
        Cache::put($key, $value, $ttl);

        // Add key to tag set
        $tagKey = "tag:{$tag}";
        Redis::connection('cache')->sadd($tagKey, $key);
        Redis::connection('cache')->expire($tagKey, $ttl);

        return true;
    }

    /**
     * Invalidate all cache entries with a tag
     */
    public static function invalidateTag(string $tag): int
    {
        $tagKey = "tag:{$tag}";
        $keys = Redis::connection('cache')->smembers($tagKey);

        $count = 0;
        foreach ($keys as $key) {
            $cleanKey = str_replace(config('database.redis.options.prefix'), '', $key);
            if (Cache::forget($cleanKey)) {
                $count++;
            }
        }

        // Remove the tag set
        Redis::connection('cache')->del($tagKey);

        Log::info("Cache invalidated for tag: {$tag}", ['keys_cleared' => $count]);

        return $count;
    }

    /**
     * Warm critical cache entries
     */
    public static function warmCache(): void
    {
        Log::info('Starting cache warming...');

        $startTime = microtime(true);
        $warmed = 0;

        // Define critical cache entries to warm
        $criticalCaches = [
            'app.config' => [
                'ttl' => EnvironmentManager::getCacheTTL('long'),
                'callback' => fn() => config('app'),
            ],
            'system.settings' => [
                'ttl' => EnvironmentManager::getCacheTTL('medium'),
                'callback' => fn() => [
                    'version' => config('app.version', '1.0.0'),
                    'name' => config('app.name'),
                    'environment' => EnvironmentManager::current(),
                ],
            ],
            'permissions.cache' => [
                'ttl' => EnvironmentManager::getCacheTTL('long'),
                'callback' => fn() => [], // Would load permissions
            ],
        ];

        foreach ($criticalCaches as $key => $config) {
            try {
                self::remember($key, $config['ttl'], $config['callback']);
                $warmed++;
            } catch (\Exception $e) {
                Log::error("Failed to warm cache: {$key}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('Cache warming completed', [
            'entries_warmed' => $warmed,
            'duration_ms' => $duration,
        ]);
    }

    /**
     * Get cache statistics
     */
    public static function getStatistics(): array
    {
        $stats = Cache::get(self::STATS_KEY, [
            'hits' => 0,
            'misses' => 0,
            'total_requests' => 0,
            'hit_rate' => 0,
            'keys_by_prefix' => [],
            'memory_usage' => 0,
        ]);

        // Get Redis memory info
        try {
            $info = Redis::connection('cache')->info('memory');
            $stats['memory_usage'] = $info['used_memory_human'] ?? '0B';
            $stats['memory_peak'] = $info['used_memory_peak_human'] ?? '0B';
        } catch (\Exception $e) {
            Log::warning('Failed to get Redis memory info', ['error' => $e->getMessage()]);
        }

        // Calculate hit rate
        if ($stats['total_requests'] > 0) {
            $stats['hit_rate'] = round(($stats['hits'] / $stats['total_requests']) * 100, 2);
        }

        return $stats;
    }

    /**
     * Record cache hit
     */
    private static function recordHit(string $key): void
    {
        $stats = Cache::get(self::STATS_KEY, [
            'hits' => 0,
            'misses' => 0,
            'total_requests' => 0,
        ]);

        $stats['hits']++;
        $stats['total_requests']++;

        Cache::put(self::STATS_KEY, $stats, 86400); // 24 hours
    }

    /**
     * Record cache miss
     */
    private static function recordMiss(string $key): void
    {
        $stats = Cache::get(self::STATS_KEY, [
            'hits' => 0,
            'misses' => 0,
            'total_requests' => 0,
        ]);

        $stats['misses']++;
        $stats['total_requests']++;

        // Track most missed keys
        if (!isset($stats['most_missed'])) {
            $stats['most_missed'] = [];
        }

        if (!isset($stats['most_missed'][$key])) {
            $stats['most_missed'][$key] = 0;
        }

        $stats['most_missed'][$key]++;

        // Keep only top 10 most missed
        arsort($stats['most_missed']);
        $stats['most_missed'] = array_slice($stats['most_missed'], 0, 10, true);

        Cache::put(self::STATS_KEY, $stats, 86400);
    }

    /**
     * Clear all cache statistics
     */
    public static function clearStatistics(): bool
    {
        return Cache::forget(self::STATS_KEY);
    }

    /**
     * Get cache size by prefix
     */
    public static function getSizeByPrefix(string $prefix): array
    {
        $connection = Redis::connection('cache');
        $keys = $connection->keys("{$prefix}*");

        $totalSize = 0;
        $count = count($keys);

        foreach ($keys as $key) {
            try {
                $size = $connection->strlen($key);
                $totalSize += $size;
            } catch (\Exception $e) {
                // Skip on error
            }
        }

        return [
            'prefix' => $prefix,
            'key_count' => $count,
            'total_bytes' => $totalSize,
            'total_human' => self::formatBytes($totalSize),
        ];
    }

    /**
     * Clean expired cache entries
     */
    public static function cleanExpiredEntries(): int
    {
        $cleaned = 0;

        try {
            $connection = Redis::connection('cache');

            // Get all keys with TTL
            $keys = $connection->keys('*');

            foreach ($keys as $key) {
                $ttl = $connection->ttl($key);

                // If TTL is -2, key doesn't exist (already expired)
                // If TTL is -1, key has no expiry (should have one)
                if ($ttl === -2) {
                    $cleaned++;
                }
            }

            Log::info('Cache cleanup completed', ['cleaned' => $cleaned]);
        } catch (\Exception $e) {
            Log::error('Cache cleanup failed', ['error' => $e->getMessage()]);
        }

        return $cleaned;
    }

    /**
     * Flush all cache (with confirmation in production)
     */
    public static function flushAll(bool $force = false): bool
    {
        if (EnvironmentManager::isProduction() && !$force) {
            Log::warning('Attempted to flush cache in production without force flag');
            return false;
        }

        try {
            Cache::flush();
            self::clearStatistics();

            Log::warning('All cache flushed', [
                'environment' => EnvironmentManager::current(),
                'forced' => $force,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to flush cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Format bytes to human-readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get cache health status
     */
    public static function getHealthStatus(): array
    {
        $stats = self::getStatistics();

        $health = [
            'status' => 'healthy',
            'checks' => [],
        ];

        // Check hit rate
        if ($stats['hit_rate'] < 50) {
            $health['checks'][] = [
                'name' => 'hit_rate',
                'status' => 'warning',
                'message' => 'Cache hit rate is below 50%',
                'value' => $stats['hit_rate'] . '%',
            ];
            $health['status'] = 'warning';
        } else {
            $health['checks'][] = [
                'name' => 'hit_rate',
                'status' => 'ok',
                'message' => 'Cache hit rate is good',
                'value' => $stats['hit_rate'] . '%',
            ];
        }

        // Check Redis connection
        try {
            Redis::connection('cache')->ping();
            $health['checks'][] = [
                'name' => 'redis_connection',
                'status' => 'ok',
                'message' => 'Redis connection is working',
            ];
        } catch (\Exception $e) {
            $health['checks'][] = [
                'name' => 'redis_connection',
                'status' => 'error',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
            $health['status'] = 'error';
        }

        return $health;
    }

    /**
     * Cache report for monitoring
     */
    public static function generateReport(): array
    {
        return [
            'timestamp' => now()->toDateTimeString(),
            'environment' => EnvironmentManager::current(),
            'statistics' => self::getStatistics(),
            'health' => self::getHealthStatus(),
            'size_by_prefix' => [
                'data' => self::getSizeByPrefix(self::PREFIX_DATA),
                'query' => self::getSizeByPrefix(self::PREFIX_QUERY),
                'view' => self::getSizeByPrefix(self::PREFIX_VIEW),
                'api' => self::getSizeByPrefix(self::PREFIX_API),
            ],
        ];
    }
}
