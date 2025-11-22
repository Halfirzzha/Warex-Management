# Redis Ecosystem Configuration for Warehouse Management System

## 🚀 Arsitektur Redis yang Telah Dikonfigurasi

Sistem warehouse management ini telah dikonfigurasi dengan ekosistem Redis profesional yang memisahkan setiap fungsi ke dalam database Redis yang berbeda untuk performa optimal dan pengelolaan yang lebih baik.

### Database Redis yang Digunakan

| Database | Fungsi | Environment Variable |
|----------|--------|---------------------|
| DB 0 | Default/General Purpose | `REDIS_DB=0` |
| DB 1 | Cache | `REDIS_CACHE_DB=1` |
| DB 2 | Queue | `REDIS_QUEUE_DB=2` |
| DB 3 | Session | `REDIS_SESSION_DB=3` |

## 📋 Konfigurasi yang Telah Diterapkan

### 1. Cache Driver
- **Driver**: Redis
- **Database**: Redis DB 1
- **Connection**: cache
- **Prefix**: warehouse_cache

### 2. Session Driver
- **Driver**: Redis
- **Database**: Redis DB 3
- **Connection**: session
- **Lifetime**: 120 menit

### 3. Queue Driver
- **Driver**: Redis
- **Database**: Redis DB 2
- **Connection**: queue
- **Retry After**: 90 detik

## 🔧 Cara Menggunakan Redis

### Testing Redis Connection

```bash
# Cek apakah Redis berjalan
redis-cli ping
# Expected: PONG

# Monitoring Redis
redis-cli monitor
```

### Cache Commands

```php
// Store cache
Cache::put('key', 'value', now()->addMinutes(10));

// Retrieve cache
$value = Cache::get('key');

// Remember (cache jika belum ada)
$users = Cache::remember('users', 3600, function () {
    return DB::table('users')->get();
});

// Forget cache
Cache::forget('key');

// Clear all cache
Cache::flush();
```

### Queue Commands

```bash
# Start queue worker
php artisan queue:work redis --queue=default

# Start queue worker dengan daemon
php artisan queue:work redis --daemon

# Process specific queue
php artisan queue:work redis --queue=high,default

# Monitor failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job_id}

# Clear all failed jobs
php artisan queue:flush
```

### Session Commands

```php
// Store session data
session(['key' => 'value']);

// Retrieve session data
$value = session('key');

// Flash data (tersedia hanya untuk request berikutnya)
session()->flash('status', 'Task was successful!');

// Regenerate session ID (security)
session()->regenerate();
```

## 🛠️ Instalasi Redis (Jika Belum Terinstall)

### macOS (dengan Homebrew)
```bash
brew install redis
brew services start redis
```

### Menggunakan Docker
```bash
docker run -d -p 6379:6379 --name redis-warehouse redis:alpine
```

## 📊 Monitoring & Debugging

### Redis CLI Commands

```bash
# Connect to Redis
redis-cli

# Select database
SELECT 1

# List all keys
KEYS *

# Get key value
GET key_name

# Check key type
TYPE key_name

# Get TTL (time to live)
TTL key_name

# Delete key
DEL key_name

# Clear current database
FLUSHDB

# Clear all databases
FLUSHALL

# Get database info
INFO
```

### Laravel Redis Commands

```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Optimize application (create cache)
php artisan optimize
```

## 🔐 Security Best Practices

1. **Production Environment**:
   ```env
   REDIS_PASSWORD=your_strong_password_here
   ```

2. **Firewall Configuration**:
   - Hanya izinkan koneksi Redis dari localhost atau server aplikasi
   - Port default: 6379

3. **Redis Config** (`/etc/redis/redis.conf`):
   ```conf
   # Bind to localhost only
   bind 127.0.0.1
   
   # Require password
   requirepass your_strong_password
   
   # Disable dangerous commands
   rename-command FLUSHDB ""
   rename-command FLUSHALL ""
   rename-command CONFIG ""
   ```

## 📈 Performance Tuning

### Config untuk Production

Update `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'warehouse'),
        'persistent' => true, // Enable persistent connection
    ],
    
    // ... connections
],
```

### Queue Worker Optimization

```bash
# Multiple workers for better throughput
php artisan queue:work redis --queue=high --tries=3 --timeout=60 &
php artisan queue:work redis --queue=default --tries=3 --timeout=60 &

# Using Supervisor (recommended for production)
# Create /etc/supervisor/conf.d/warehouse-worker.conf
```

### Supervisor Configuration Example

```ini
[program:warehouse-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/application/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/application/storage/logs/worker.log
stopwaitsecs=3600
```

## 🎯 Tips & Tricks

### 1. Cache Tags (untuk invalidasi cache yang lebih baik)
```php
Cache::tags(['users', 'admins'])->put('key', $value, 3600);
Cache::tags(['users'])->flush(); // Clear semua cache dengan tag 'users'
```

### 2. Rate Limiting dengan Redis
```php
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 3. Redis Pub/Sub untuk Broadcasting
Update `.env`:
```env
BROADCAST_DRIVER=redis
```

### 4. Atomic Operations
```php
use Illuminate\Support\Facades\Redis;

// Increment
Redis::incr('page_views');

// Decrement
Redis::decr('items_count');

// Atomic operations with pipeline
Redis::pipeline(function ($pipe) {
    $pipe->set('key1', 'value1');
    $pipe->set('key2', 'value2');
    $pipe->incr('counter');
});
```

## 📝 Troubleshooting

### Connection Refused
```bash
# Check if Redis is running
redis-cli ping

# Start Redis (macOS)
brew services start redis

# Start Redis (Docker)
docker start redis-warehouse
```

### Memory Issues
```bash
# Check memory usage
redis-cli INFO memory

# Set max memory in redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### Slow Performance
```bash
# Enable slow log
redis-cli CONFIG SET slowlog-log-slower-than 10000

# View slow logs
redis-cli SLOWLOG GET 10
```

## 🚀 Next Steps

1. Install Redis jika belum terinstall
2. Jalankan aplikasi: `php artisan serve`
3. Test Redis connection: `php artisan tinker` kemudian `Cache::put('test', 'value')`
4. Setup queue worker untuk background jobs
5. Configure Redis untuk production dengan password dan firewall

## 📚 Resources

- [Laravel Cache Documentation](https://laravel.com/docs/cache)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Session Documentation](https://laravel.com/docs/session)
- [Redis Documentation](https://redis.io/documentation)
- [Predis Documentation](https://github.com/predis/predis)

---

**Note**: Pastikan Redis service berjalan sebelum menjalankan aplikasi. Untuk development, Anda bisa menggunakan `redis-cli` untuk monitoring real-time koneksi dan operasi Redis.
