<?php

use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * PUBLIC ROUTES - No Authentication Required
 * ============================================================================
 */

// Redirect to admin panel
Route::get('/', function () {
    return redirect('/warehouse');
})->middleware([
    'throttle:60,1', // Max 60 requests per minute
])->name('warehouse');

/**
 * ============================================================================
 * HEALTH CHECK & MONITORING ROUTES
 * ============================================================================
 */

use App\Http\Controllers\HealthController;

// Basic health check - Available in all environments
Route::get('/health', [HealthController::class, 'index'])
    ->name('health.check')
    ->middleware('throttle:30,1');

// Detailed health check - Protected in production
Route::get('/health/detailed', [HealthController::class, 'detailed'])
    ->name('health.detailed')
    ->middleware('throttle:10,1');

// Cache statistics
Route::get('/health/cache', [HealthController::class, 'cache'])
    ->name('health.cache')
    ->middleware('throttle:10,1');

// Environment information
Route::get('/health/environment', [HealthController::class, 'environment'])
    ->name('health.environment')
    ->middleware('throttle:10,1');

// Cache warming endpoint
Route::post('/health/cache/warm', [HealthController::class, 'warmCache'])
    ->name('health.cache.warm')
    ->middleware('throttle:5,1');

// Cache report
Route::get('/health/cache/report', [HealthController::class, 'cacheReport'])
    ->name('health.cache.report')
    ->middleware('throttle:10,1');

/**
 * ============================================================================
 * SECURITY CONFIGURATION
 * ============================================================================
 *
 * All Filament routes are protected by:
 * 1. CSRF Token Verification
 * 2. Rate Limiting (throttle middleware)
 * 3. Session Management
 * 4. Account Status Check (CheckAccountStatus middleware)
 * 5. Auto-block after 3 failed login attempts
 *
 * Password Reset Security:
 * - Token expires in 60 minutes (config/auth.php)
 * - Single-use tokens (deleted after reset)
 * - Anti user enumeration (always show success message)
 * - Rate limited to 1 request per 60 seconds
 *
 * Login Security:
 * - Auto-block after 3 failed attempts
 * - 24-hour automatic unlock
 * - IP tracking and logging
 * - Session timeout: 120 minutes
 *
 * Filament Routes:
 * - /warehouse/login - Custom login with email/username
 * - /warehouse/password-reset/request - Request reset link
 * - /warehouse/password-reset/reset - Reset password form
 * - /warehouse/* - All admin panel routes (authenticated)
 * ============================================================================
 */
