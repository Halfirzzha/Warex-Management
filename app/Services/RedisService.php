<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Redis Service Helper
 *
 * Provides utility methods for Redis operations
 * in Warehouse Management System
 */
class RedisService
{
    /**
     * Cache prefix for warehouse data
     */
    private const CACHE_PREFIX = 'warehouse:';

    /**
     * Cache a warehouse item with automatic prefix
     *
     * @param  mixed  $value
     * @param  int  $ttl  Time to live in seconds
     */
    public static function cacheWarehouseData(string $key, $value, int $ttl = 3600): bool
    {
        return Cache::put(self::CACHE_PREFIX.$key, $value, now()->addSeconds($ttl));
    }

    /**
     * Get cached warehouse data
     *
     * @param  mixed  $default
     * @return mixed
     */
    public static function getWarehouseData(string $key, $default = null)
    {
        return Cache::get(self::CACHE_PREFIX.$key, $default);
    }

    /**
     * Remember warehouse data (cache if not exists)
     *
     * @return mixed
     */
    public static function rememberWarehouseData(string $key, int $ttl, callable $callback)
    {
        return Cache::remember(self::CACHE_PREFIX.$key, $ttl, $callback);
    }

    /**
     * Forget warehouse cache
     */
    public static function forgetWarehouseData(string $key): bool
    {
        return Cache::forget(self::CACHE_PREFIX.$key);
    }

    /**
     * Flush all warehouse cache
     */
    public static function flushWarehouseCache(): bool
    {
        $keys = Redis::connection('cache')->keys(self::CACHE_PREFIX.'*');

        if (empty($keys)) {
            return true;
        }

        foreach ($keys as $key) {
            // Remove prefix from key
            $cleanKey = str_replace(config('database.redis.options.prefix'), '', $key);
            Cache::forget($cleanKey);
        }

        return true;
    }

    /**
     * Cache warehouse statistics
     */
    public static function cacheStatistics(array $stats, int $ttl = 300): bool
    {
        return self::cacheWarehouseData('statistics', $stats, $ttl);
    }

    /**
     * Get cached warehouse statistics
     */
    public static function getStatistics(): ?array
    {
        return self::getWarehouseData('statistics');
    }

    /**
     * Increment warehouse counter
     */
    public static function incrementCounter(string $key, int $increment = 1): int
    {
        $fullKey = self::CACHE_PREFIX.'counter:'.$key;

        return Redis::connection('cache')->incrby($fullKey, $increment);
    }

    /**
     * Decrement warehouse counter
     */
    public static function decrementCounter(string $key, int $decrement = 1): int
    {
        $fullKey = self::CACHE_PREFIX.'counter:'.$key;

        return Redis::connection('cache')->decrby($fullKey, $decrement);
    }

    /**
     * Get counter value
     */
    public static function getCounter(string $key): int
    {
        $fullKey = self::CACHE_PREFIX.'counter:'.$key;

        return (int) Redis::connection('cache')->get($fullKey) ?? 0;
    }

    /**
     * Set with expiration (EX) - optimized for temporary data
     *
     * @param  mixed  $value
     */
    public static function setex(string $key, $value, int $seconds): bool
    {
        $fullKey = self::CACHE_PREFIX.$key;

        return Redis::connection('cache')->setex($fullKey, $seconds, json_encode($value));
    }

    /**
     * Get multiple keys at once
     */
    public static function getMultiple(array $keys): array
    {
        $prefixedKeys = array_map(function ($key) {
            return self::CACHE_PREFIX.$key;
        }, $keys);

        $values = Cache::many($prefixedKeys);

        // Remove prefix from returned keys
        $result = [];
        foreach ($values as $key => $value) {
            $cleanKey = str_replace(self::CACHE_PREFIX, '', $key);
            $result[$cleanKey] = $value;
        }

        return $result;
    }

    /**
     * Set multiple keys at once
     */
    public static function setMultiple(array $values, int $ttl = 3600): bool
    {
        foreach ($values as $key => $value) {
            self::cacheWarehouseData($key, $value, $ttl);
        }

        return true;
    }

    /**
     * Check if cache key exists
     */
    public static function exists(string $key): bool
    {
        return Cache::has(self::CACHE_PREFIX.$key);
    }

    /**
     * Get all warehouse cache keys
     */
    public static function getAllKeys(): array
    {
        $keys = Redis::connection('cache')->keys(self::CACHE_PREFIX.'*');

        return array_map(function ($key) {
            return str_replace(
                config('database.redis.options.prefix').self::CACHE_PREFIX,
                '',
                $key
            );
        }, $keys);
    }

    /**
     * Get Redis connection info
     */
    public static function getConnectionInfo(string $connection = 'cache'): array
    {
        $info = Redis::connection($connection)->info();

        return [
            'redis_version' => $info['redis_version'] ?? 'unknown',
            'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
            'connected_clients' => $info['connected_clients'] ?? 0,
            'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
            'total_connections_received' => $info['total_connections_received'] ?? 0,
            'total_commands_processed' => $info['total_commands_processed'] ?? 0,
        ];
    }

    /**
     * Check Redis health
     */
    public static function isHealthy(): bool
    {
        try {
            Redis::connection('cache')->ping();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get database size (number of keys)
     */
    public static function getDatabaseSize(string $connection = 'cache'): int
    {
        return Redis::connection($connection)->dbsize();
    }

    /**
     * Flush specific connection database
     */
    public static function flushDatabase(string $connection = 'cache'): bool
    {
        try {
            Redis::connection($connection)->flushdb();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
