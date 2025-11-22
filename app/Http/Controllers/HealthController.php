<?php

namespace App\Http\Controllers;

use App\Support\HealthCheck;
use App\Support\CacheManager;
use App\Support\EnvironmentManager;
use Illuminate\Http\JsonResponse;

/**
 * ============================================================================
 * HEALTH & MONITORING CONTROLLER
 * Endpoints for system health monitoring
 * ============================================================================
 */
class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     * GET /health
     */
    public function index(): JsonResponse
    {
        $result = HealthCheck::endpoint();

        return response()->json($result['data'], $result['http_status']);
    }

    /**
     * Detailed health check endpoint
     * GET /health/detailed
     */
    public function detailed(): JsonResponse
    {
        // Only allow in non-production or with proper auth
        if (EnvironmentManager::isProduction()) {
            return response()->json([
                'error' => 'Detailed health check requires authentication',
            ], 403);
        }

        $result = HealthCheck::detailedEndpoint();

        return response()->json($result['data'], $result['http_status']);
    }

    /**
     * Cache statistics endpoint
     * GET /health/cache
     */
    public function cache(): JsonResponse
    {
        if (EnvironmentManager::isProduction()) {
            return response()->json([
                'error' => 'Cache stats requires authentication',
            ], 403);
        }

        $stats = CacheManager::getStatistics();
        $health = CacheManager::getHealthStatus();

        return response()->json([
            'statistics' => $stats,
            'health' => $health,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Environment information endpoint
     * GET /health/environment
     */
    public function environment(): JsonResponse
    {
        if (EnvironmentManager::isProduction()) {
            return response()->json([
                'environment' => EnvironmentManager::current(),
                'debug' => EnvironmentManager::isDebugMode(),
            ]);
        }

        return response()->json(EnvironmentManager::getSummary());
    }

    /**
     * Cache warming endpoint
     * POST /health/cache/warm
     */
    public function warmCache(): JsonResponse
    {
        if (EnvironmentManager::isProduction()) {
            return response()->json([
                'error' => 'Cache warming requires authentication',
            ], 403);
        }

        CacheManager::warmCache();

        return response()->json([
            'message' => 'Cache warming completed',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Cache report endpoint
     * GET /health/cache/report
     */
    public function cacheReport(): JsonResponse
    {
        if (EnvironmentManager::isProduction()) {
            return response()->json([
                'error' => 'Cache report requires authentication',
            ], 403);
        }

        $report = CacheManager::generateReport();

        return response()->json($report);
    }
}
