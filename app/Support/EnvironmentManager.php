<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * ============================================================================
 * ENVIRONMENT MANAGER
 * Advanced environment detection and configuration management
 * ============================================================================
 *
 * Features:
 * - Auto-detect environment (local, staging, production)
 * - Environment-specific configuration loading
 * - Feature flags per environment
 * - Performance optimizations per environment
 * - Debug helpers for development
 *
 * Usage:
 * if (EnvironmentManager::isProduction()) {
 *     // Production-only code
 * }
 *
 * $config = EnvironmentManager::getConfig('feature.new_dashboard');
 */
class EnvironmentManager
{
    /**
     * Environment constants
     */
    public const ENV_LOCAL = 'local';
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_STAGING = 'staging';
    public const ENV_PRODUCTION = 'production';
    public const ENV_TESTING = 'testing';

    /**
     * Cached environment state
     */
    private static ?string $cachedEnvironment = null;

    /**
     * Get current environment
     */
    public static function current(): string
    {
        if (self::$cachedEnvironment === null) {
            self::$cachedEnvironment = App::environment();
        }

        return self::$cachedEnvironment;
    }

    /**
     * Check if running in local environment
     */
    public static function isLocal(): bool
    {
        return self::current() === self::ENV_LOCAL || self::current() === self::ENV_DEVELOPMENT;
    }

    /**
     * Check if running in staging environment
     */
    public static function isStaging(): bool
    {
        return self::current() === self::ENV_STAGING;
    }

    /**
     * Check if running in production environment
     */
    public static function isProduction(): bool
    {
        return self::current() === self::ENV_PRODUCTION;
    }

    /**
     * Check if running in testing environment
     */
    public static function isTesting(): bool
    {
        return self::current() === self::ENV_TESTING;
    }

    /**
     * Check if debug mode is enabled
     */
    public static function isDebugMode(): bool
    {
        return (bool) Config::get('app.debug', false);
    }

    /**
     * Check if maintenance mode should be enabled
     */
    public static function shouldShowMaintenanceMode(): bool
    {
        return file_exists(storage_path('framework/down'));
    }

    /**
     * Get environment-specific configuration
     */
    public static function getConfig(string $key, $default = null)
    {
        $env = self::current();
        $envKey = "env.{$env}.{$key}";

        return Config::get($envKey, Config::get($key, $default));
    }

    /**
     * Feature flags per environment
     */
    public static function featureEnabled(string $feature): bool
    {
        $features = [
            self::ENV_LOCAL => [
                'debug_toolbar' => true,
                'query_logging' => true,
                'profiler' => true,
                'hot_reload' => true,
                'api_docs' => true,
            ],
            self::ENV_STAGING => [
                'debug_toolbar' => true,
                'query_logging' => true,
                'profiler' => true,
                'hot_reload' => false,
                'api_docs' => true,
            ],
            self::ENV_PRODUCTION => [
                'debug_toolbar' => false,
                'query_logging' => false,
                'profiler' => false,
                'hot_reload' => false,
                'api_docs' => false,
            ],
        ];

        $env = self::current();
        return $features[$env][$feature] ?? false;
    }

    /**
     * Get cache TTL based on environment
     */
    public static function getCacheTTL(string $type = 'default'): int
    {
        $ttls = [
            self::ENV_LOCAL => [
                'default' => 300,      // 5 minutes
                'short' => 60,         // 1 minute
                'medium' => 600,       // 10 minutes
                'long' => 1800,        // 30 minutes
            ],
            self::ENV_STAGING => [
                'default' => 600,      // 10 minutes
                'short' => 300,        // 5 minutes
                'medium' => 1800,      // 30 minutes
                'long' => 3600,        // 1 hour
            ],
            self::ENV_PRODUCTION => [
                'default' => 3600,     // 1 hour
                'short' => 900,        // 15 minutes
                'medium' => 3600,      // 1 hour
                'long' => 86400,       // 24 hours
            ],
        ];

        $env = self::current();
        return $ttls[$env][$type] ?? $ttls[$env]['default'];
    }

    /**
     * Get log level based on environment
     */
    public static function getLogLevel(): string
    {
        return match (self::current()) {
            self::ENV_LOCAL, self::ENV_DEVELOPMENT => 'debug',
            self::ENV_STAGING => 'info',
            self::ENV_PRODUCTION => 'error',
            self::ENV_TESTING => 'critical',
            default => 'info',
        };
    }

    /**
     * Get performance settings
     */
    public static function getPerformanceSettings(): array
    {
        return match (self::current()) {
            self::ENV_LOCAL => [
                'cache_config' => false,
                'cache_routes' => false,
                'cache_views' => false,
                'optimize' => false,
                'eager_loading' => true,
            ],
            self::ENV_STAGING => [
                'cache_config' => true,
                'cache_routes' => true,
                'cache_views' => true,
                'optimize' => true,
                'eager_loading' => true,
            ],
            self::ENV_PRODUCTION => [
                'cache_config' => true,
                'cache_routes' => true,
                'cache_views' => true,
                'optimize' => true,
                'eager_loading' => true,
            ],
            default => [
                'cache_config' => false,
                'cache_routes' => false,
                'cache_views' => false,
                'optimize' => false,
                'eager_loading' => true,
            ],
        };
    }

    /**
     * Get database connection settings
     */
    public static function getDatabaseSettings(): array
    {
        return match (self::current()) {
            self::ENV_LOCAL => [
                'strict' => false,
                'slow_query_log' => true,
                'slow_query_threshold' => 1000, // ms
                'query_logging' => true,
            ],
            self::ENV_STAGING => [
                'strict' => true,
                'slow_query_log' => true,
                'slow_query_threshold' => 500,
                'query_logging' => true,
            ],
            self::ENV_PRODUCTION => [
                'strict' => true,
                'slow_query_log' => true,
                'slow_query_threshold' => 100,
                'query_logging' => false,
            ],
            default => [
                'strict' => false,
                'slow_query_log' => false,
                'slow_query_threshold' => 1000,
                'query_logging' => false,
            ],
        };
    }

    /**
     * Get rate limit settings
     */
    public static function getRateLimitSettings(): array
    {
        return match (self::current()) {
            self::ENV_LOCAL => [
                'api' => 1000,
                'web' => 500,
                'login' => 50,
            ],
            self::ENV_STAGING => [
                'api' => 500,
                'web' => 300,
                'login' => 20,
            ],
            self::ENV_PRODUCTION => [
                'api' => 60,
                'web' => 60,
                'login' => 10,
            ],
            default => [
                'api' => 60,
                'web' => 60,
                'login' => 5,
            ],
        };
    }

    /**
     * Check if should use CDN
     */
    public static function shouldUseCDN(): bool
    {
        return self::isProduction() && Config::get('app.cdn_enabled', false);
    }

    /**
     * Get asset URL (with CDN support)
     */
    public static function assetUrl(string $path): string
    {
        if (self::shouldUseCDN()) {
            $cdnUrl = Config::get('app.cdn_url');
            return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
        }

        return asset($path);
    }

    /**
     * Dump and die helper for development only
     *
     * @param mixed ...$args
     */
    public static function dd(...$args): void
    {
        if (self::isLocal()) {
            dd(...$args);
        }

        // In production, just log instead
        \Illuminate\Support\Facades\Log::error('dd() called in production', [
            'data' => $args,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
        ]);
    }

    /**
     * Dump helper for development only
     *
     * @param mixed ...$args
     * @return void
     */
    public static function dump(...$args): void
    {
        if (self::isLocal()) {
            dump(...$args);
        }
    }

    /**
     * Get environment summary
     */
    public static function getSummary(): array
    {
        return [
            'environment' => self::current(),
            'is_local' => self::isLocal(),
            'is_staging' => self::isStaging(),
            'is_production' => self::isProduction(),
            'debug_mode' => self::isDebugMode(),
            'maintenance_mode' => self::shouldShowMaintenanceMode(),
            'performance_settings' => self::getPerformanceSettings(),
            'database_settings' => self::getDatabaseSettings(),
            'rate_limits' => self::getRateLimitSettings(),
            'cache_ttls' => [
                'default' => self::getCacheTTL('default'),
                'short' => self::getCacheTTL('short'),
                'medium' => self::getCacheTTL('medium'),
                'long' => self::getCacheTTL('long'),
            ],
            'log_level' => self::getLogLevel(),
        ];
    }
}
