# 🚀 ADVANCED SYSTEM ARCHITECTURE

## Overview

Dokumentasi lengkap sistem advanced yang telah diimplementasikan untuk production, staging, dan local development yang stabil dan professional.

---

## 🏗️ System Components

### 1. **Environment Manager** (`App\Support\EnvironmentManager`)

Advanced environment detection dan configuration management.

**Features:**

-   Auto-detect environment (local, staging, production)
-   Environment-specific configuration loading
-   Feature flags per environment
-   Performance optimizations per environment
-   Debug helpers for development

**Usage:**

```php
use App\Support\EnvironmentManager;

// Check environment
if (EnvironmentManager::isProduction()) {
    // Production-only code
}

// Get environment-specific cache TTL
$ttl = EnvironmentManager::getCacheTTL('long'); // 86400 in production, 1800 in local

// Check feature flags
if (EnvironmentManager::featureEnabled('debug_toolbar')) {
    // Enable debug toolbar
}

// Get performance settings
$settings = EnvironmentManager::getPerformanceSettings();

// Safe dump (only works in local)
EnvironmentManager::dump($data);
```

**Cache TTL by Environment:**

```
Local:
- default: 5 minutes
- short: 1 minute
- medium: 10 minutes
- long: 30 minutes

Staging:
- default: 10 minutes
- short: 5 minutes
- medium: 30 minutes
- long: 1 hour

Production:
- default: 1 hour
- short: 15 minutes
- medium: 1 hour
- long: 24 hours
```

---

### 2. **Cache Manager** (`App\Support\CacheManager`)

Professional cache management dengan warming, tagging, dan statistics.

**Features:**

-   Cache warming for critical data
-   Cache tagging for group invalidation
-   Cache statistics and monitoring
-   Automatic cache cleanup
-   Multi-level caching strategy
-   Cache miss tracking

**Usage:**

```php
use App\Support\CacheManager;

// Remember with automatic statistics
$users = CacheManager::remember('users.count', 300, function() {
    return User::count();
});

// Store with tag for group invalidation
CacheManager::putWithTag('user:1', $userData, 3600, 'users');

// Invalidate all cache entries with tag
CacheManager::invalidateTag('users'); // Clears all user-related cache

// Warm critical caches
CacheManager::warmCache();

// Get cache statistics
$stats = CacheManager::getStatistics();
// Returns: hits, misses, hit_rate, memory_usage, most_missed keys

// Get cache health
$health = CacheManager::getHealthStatus();

// Generate comprehensive report
$report = CacheManager::generateReport();

// Clean expired entries
$cleaned = CacheManager::cleanExpiredEntries();
```

**Artisan Commands:**

```bash
# Warm cache
php artisan cache:warm

# Clear cache statistics
php artisan tinker
>>> CacheManager::clearStatistics();
```

---

### 3. **Health Check System** (`App\Support\HealthCheck`)

Professional application health monitoring.

**Features:**

-   Database connection check
-   Redis connection check (all connections)
-   Filesystem check
-   Queue system check
-   Memory usage check
-   Disk space check
-   Performance metrics

**Usage:**

```php
use App\Support\HealthCheck;

// Quick health check
$status = HealthCheck::check();
// Returns: status (healthy/degraded/unhealthy), checks, failed_checks

// Detailed health check with metrics
$detailed = HealthCheck::detailed();
// Includes: memory, disk, performance metrics, queue status, cache health
```

**Artisan Commands:**

```bash
# Basic health check
php artisan health:check

# Detailed health check
php artisan health:check --detailed

# JSON output
php artisan health:check --json
```

**HTTP Endpoints:**

```
GET  /health                    - Basic health check
GET  /health/detailed           - Detailed health check
GET  /health/cache              - Cache statistics
GET  /health/environment        - Environment info
POST /health/cache/warm         - Warm cache
GET  /health/cache/report       - Cache report
```

**Response Format:**

```json
{
    "status": "healthy",
    "timestamp": "2025-11-22T10:00:00+00:00",
    "environment": "production",
    "checks": {
        "database": {
            "status": "ok",
            "message": "Database connection is working",
            "response_time_ms": 5.23,
            "connection": "mysql"
        },
        "redis": {
            "status": "ok",
            "message": "All Redis connections working",
            "connections": {
                "default": { "status": "ok", "response_time_ms": 1.5 },
                "cache": { "status": "ok", "response_time_ms": 2.1 },
                "queue": { "status": "ok", "response_time_ms": 1.8 },
                "session": { "status": "ok", "response_time_ms": 2.3 }
            }
        },
        "filesystem": {
            "status": "ok",
            "message": "All directories are accessible",
            "issues": []
        }
    },
    "metrics": {
        "memory": {
            "status": "ok",
            "current": "45.2 MB",
            "peak": "52.8 MB",
            "limit": "256M",
            "usage_percent": 17.66
        },
        "disk": {
            "status": "ok",
            "free": "125.4 GB",
            "used": "32.1 GB",
            "total": "157.5 GB",
            "usage_percent": 20.38
        }
    }
}
```

---

## 📊 Monitoring & Observability

### Health Check Monitoring

**Setup Uptime Monitoring:**

```bash
# Use external services to monitor /health endpoint
# Examples: UptimeRobot, Pingdom, StatusCake

URL to monitor: https://yourdomain.com/health
Expected response: 200 OK
Check interval: 5 minutes
```

**Alerting Rules:**

```yaml
# Example for monitoring tool
alerts:
    - name: "Application Unhealthy"
      condition: status == "unhealthy"
      action: send_email, send_slack

    - name: "Application Degraded"
      condition: status == "degraded"
      action: send_slack

    - name: "High Memory Usage"
      condition: memory.usage_percent > 80
      action: send_email

    - name: "Low Disk Space"
      condition: disk.usage_percent > 90
      action: send_email, send_pagerduty
```

### Cache Monitoring

**Dashboard Metrics:**

```
- Cache Hit Rate: > 80% (good), < 50% (needs improvement)
- Total Requests: Trending over time
- Most Missed Keys: Identify patterns
- Memory Usage: Monitor growth
- Redis Response Time: < 10ms (excellent), < 50ms (good)
```

**Automated Cache Warming:**

```bash
# Add to crontab
# Run every 30 minutes
*/30 * * * * php /path/to/artisan cache:warm > /dev/null 2>&1

# Or use Laravel scheduler in app/Console/Kernel.php
$schedule->command('cache:warm')->everyThirtyMinutes();
```

---

## 🔧 Configuration per Environment

### Local Development

**Optimizations:**

```php
// .env
APP_ENV=local
APP_DEBUG=true

// Features enabled
- Debug toolbar: YES
- Query logging: YES
- Profiler: YES
- Hot reload: YES
- API docs: YES

// Performance
- Config caching: NO
- Route caching: NO
- View caching: NO
- Eager loading: YES

// Cache TTL: Short (5-30 minutes)
```

**Development Tools:**

```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog();

# Check queries
>>> DB::getQueryLog();

# Monitor cache
http://localhost:8000/health/cache

# Check environment
http://localhost:8000/health/environment
```

### Staging Environment

**Optimizations:**

```php
// .env
APP_ENV=staging
APP_DEBUG=true

// Features enabled
- Debug toolbar: YES
- Query logging: YES
- Profiler: YES
- Hot reload: NO
- API docs: YES

// Performance
- Config caching: YES
- Route caching: YES
- View caching: YES
- Eager loading: YES

// Cache TTL: Medium (10-60 minutes)
```

**Testing:**

```bash
# Run health checks
php artisan health:check --detailed

# Warm cache
php artisan cache:warm

# Monitor
curl https://staging.domain.com/health/detailed
```

### Production Environment

**Optimizations:**

```php
// .env
APP_ENV=production
APP_DEBUG=false

// Features enabled
- Debug toolbar: NO
- Query logging: NO
- Profiler: NO
- Hot reload: NO
- API docs: NO

// Performance
- Config caching: YES
- Route caching: YES
- View caching: YES
- Eager loading: YES

// Cache TTL: Long (1-24 hours)
```

**Optimization Commands:**

```bash
# Full optimization
php artisan optimize

# Individual optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Warm cache after deployment
php artisan cache:warm
```

---

## 🚀 Deployment Workflow

### Pre-Deployment Checklist

```bash
# 1. Run health check
php artisan health:check --detailed

# 2. Check cache stats
php artisan tinker
>>> CacheManager::generateReport();

# 3. Test database connection
php artisan db:show

# 4. Test Redis connections
redis-cli ping

# 5. Run tests (if available)
php artisan test
```

### Deployment Script (deploy.sh)

```bash
#!/bin/bash

echo "🚀 Starting deployment..."

# Enable maintenance mode
php artisan down --message="Updating..." --retry=60

# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Warm cache
php artisan cache:warm

# Restart services
sudo supervisorctl restart warehouse-worker:*
sudo systemctl reload php8.4-fpm

# Health check
php artisan health:check

# Disable maintenance mode
php artisan up

echo "✅ Deployment completed!"
```

---

## 📈 Performance Benchmarks

### Target Metrics

```
Response Times:
- Health Check: < 50ms
- Database Query: < 100ms
- Redis Operation: < 10ms
- API Endpoint: < 200ms
- Page Load: < 1s

Cache Performance:
- Hit Rate: > 80%
- Miss Rate: < 20%
- Memory Usage: < 80%

System Health:
- Uptime: > 99.9%
- Memory Usage: < 80%
- Disk Usage: < 85%
- CPU Usage: < 70%
```

### Monitoring Tools

**Recommended:**

-   **New Relic** - APM and monitoring
-   **DataDog** - Infrastructure monitoring
-   **Sentry** - Error tracking
-   **Laravel Telescope** - Development debugging
-   **Redis Insight** - Redis monitoring

---

## 🔒 Security Considerations

### Health Endpoints

**Production:**

```php
// Limit detailed information in production
GET /health - Public (basic check only)
GET /health/detailed - Protected (requires auth)
GET /health/cache - Protected
GET /health/environment - Limited info
```

**Rate Limiting:**

```php
// Already applied
'throttle:30,1' - Basic health check
'throttle:10,1' - Detailed endpoints
'throttle:5,1'  - Cache warming
```

### Cache Security

```php
// Never cache sensitive data without encryption
// Use cache tags for easy invalidation
// Monitor cache statistics for unusual patterns
// Set appropriate TTLs
```

---

## 📚 Best Practices

### 1. **Environment Management**

-   Always use `EnvironmentManager` instead of direct `env()` calls
-   Set appropriate feature flags per environment
-   Use environment-specific cache TTLs

### 2. **Cache Strategy**

-   Warm critical caches on deployment
-   Use cache tags for related data
-   Monitor hit rates and adjust TTLs
-   Clear cache after data updates

### 3. **Health Monitoring**

-   Set up external monitoring for `/health` endpoint
-   Configure alerting for unhealthy status
-   Review health checks regularly
-   Monitor trends over time

### 4. **Performance**

-   Enable all optimizations in production
-   Use eager loading to prevent N+1 queries
-   Cache expensive queries
-   Monitor slow queries

### 5. **Maintenance**

-   Run health checks before and after deployments
-   Warm cache after clearing
-   Monitor disk space regularly
-   Rotate logs automatically

---

## 🆘 Troubleshooting

### Health Check Failed

```bash
# Check detailed health
php artisan health:check --detailed --json

# Check specific services
redis-cli ping
mysql -u root -p -e "SELECT 1"

# Check logs
tail -f storage/logs/laravel.log
```

### Cache Issues

```bash
# Clear all cache
php artisan cache:clear

# Warm cache again
php artisan cache:warm

# Check Redis
redis-cli
> INFO memory
> DBSIZE
```

### Performance Issues

```bash
# Check memory usage
php artisan health:check --detailed | grep -A5 "memory"

# Check slow queries (if enabled)
tail -f storage/logs/laravel.log | grep "slow query"

# Monitor Redis
redis-cli --latency
redis-cli --stat
```

---

## ✅ Status

**Implementation:** ✅ **COMPLETE**
**Testing:** ✅ **READY**
**Documentation:** ✅ **COMPLETE**
**Production Ready:** ✅ **YES**

**Version:** 2.0.0
**Last Updated:** November 22, 2025
