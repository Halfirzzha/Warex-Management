# 🚀 Production Deployment Guide

Panduan lengkap untuk deploy Warehouse Management System ke production server.

---

## 📋 Pre-Deployment Checklist

### Server Requirements

-   [ ] PHP 8.2 or higher
-   [ ] MySQL 8.0 or higher
-   [ ] Redis 5.0 or higher
-   [ ] Composer 2.x
-   [ ] Node.js 18.x or higher
-   [ ] Nginx or Apache
-   [ ] SSL Certificate (recommended)
-   [ ] Supervisor (for queue workers)

### Security Checklist

-   [ ] APP_DEBUG=false
-   [ ] APP_ENV=production
-   [ ] Redis password configured
-   [ ] Database user with limited privileges
-   [ ] Firewall rules configured
-   [ ] SSL/TLS enabled
-   [ ] Sensitive files protected

---

## 🔧 Environment Configuration

### 1. Update .env for Production

```env
# Application
APP_NAME="Warehouse Management System"
APP_ENV=production
APP_KEY=base64:your_production_key_here
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=production_warehouse
DB_USERNAME=warehouse_user
DB_PASSWORD=strong_password_here

# Redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=strong_redis_password_here
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_SESSION_DB=3
REDIS_PREFIX=warehouse_prod

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Queue
QUEUE_CONNECTION=redis

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_STACK=daily
```

### 2. Generate Production Key

```bash
php artisan key:generate --force
```

---

## 🗄️ Database Setup

### 1. Create Production Database

```sql
CREATE DATABASE production_warehouse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'warehouse_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON production_warehouse.* TO 'warehouse_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Run Migrations

```bash
php artisan migrate --force
```

### 3. Seed Initial Data (if needed)

```bash
php artisan db:seed --force
```

---

## 🔐 Redis Security

### 1. Configure Redis Password

Edit `/etc/redis/redis.conf`:

```conf
# Require password
requirepass your_strong_redis_password

# Bind to localhost only (if Redis on same server)
bind 127.0.0.1

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG ""
rename-command SHUTDOWN ""
rename-command BGSAVE ""
rename-command BGREWRITEAOF ""

# Persistence
save 900 1
save 300 10
save 60 10000

# Max memory policy
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 2. Restart Redis

```bash
sudo systemctl restart redis
```

### 3. Test Connection

```bash
redis-cli -a your_strong_redis_password ping
```

---

## 🌐 Web Server Configuration

### Nginx Configuration

Create: `/etc/nginx/sites-available/warehouse`

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/warehouse/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Logging
    access_log /var/log/nginx/warehouse-access.log;
    error_log /var/log/nginx/warehouse-error.log;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Increase timeout for long requests
        fastcgi_read_timeout 300;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to config files
    location ~ /\.env {
        deny all;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/warehouse /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 👷 Supervisor Configuration (Queue Workers)

Create: `/etc/supervisor/conf.d/warehouse-worker.conf`

```ini
[program:warehouse-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/warehouse/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/warehouse/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start warehouse-worker:*
```

Monitor workers:

```bash
sudo supervisorctl status warehouse-worker:*
```

---

## 📦 Deployment Steps

### 1. Clone/Upload Code

```bash
cd /var/www
git clone your-repo-url warehouse
cd warehouse
```

### 2. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/warehouse
sudo chmod -R 755 /var/www/warehouse
sudo chmod -R 775 /var/www/warehouse/storage
sudo chmod -R 775 /var/www/warehouse/bootstrap/cache
```

### 3. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 4. Configure Environment

```bash
cp .env.example .env
nano .env
# Edit configuration
php artisan key:generate
```

### 5. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

### 6. Run Migrations

```bash
php artisan migrate --force
```

### 7. Create Admin User

```bash
php artisan make:filament-user
```

### 8. Test Application

```bash
php artisan redis:monitor
php artisan about
```

---

## 🔄 Update/Deployment Script

Create: `deploy.sh`

```bash
#!/bin/bash

echo "🚀 Starting deployment..."

# Enable maintenance mode
php artisan down --message="Updating application..." --retry=60

# Pull latest changes
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Restart queue workers
sudo supervisorctl restart warehouse-worker:*

# Disable maintenance mode
php artisan up

echo "✅ Deployment completed!"
```

Make executable:

```bash
chmod +x deploy.sh
```

---

## 📊 Monitoring & Maintenance

### 1. Laravel Telescope (Optional)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access: `https://yourdomain.com/telescope`

### 2. Log Monitoring

```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Monitor Nginx access logs
tail -f /var/log/nginx/warehouse-access.log

# Monitor worker logs
tail -f storage/logs/worker.log
```

### 3. Cron Jobs

Add to crontab:

```bash
* * * * * cd /var/www/warehouse && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Backup Strategy

```bash
# Daily database backup
0 2 * * * /usr/bin/mysqldump -u warehouse_user -p'password' production_warehouse > /backups/db_$(date +\%Y\%m\%d).sql

# Weekly full backup
0 3 * * 0 tar -czf /backups/full_$(date +\%Y\%m\%d).tar.gz /var/www/warehouse
```

---

## 🔍 Health Checks

### 1. Application Health

```bash
php artisan redis:monitor
curl https://yourdomain.com/warehouse/login
```

### 2. Redis Health

```bash
redis-cli -a password ping
redis-cli -a password INFO
```

### 3. Queue Health

```bash
php artisan queue:monitor redis
php artisan queue:failed
```

---

## 🐛 Troubleshooting

### Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan redis:clear --force
```

### Permission Issues

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Queue Not Processing

```bash
sudo supervisorctl restart warehouse-worker:*
sudo supervisorctl tail -f warehouse-worker:warehouse-worker_00
```

### 502 Bad Gateway

```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm

# Check Nginx
sudo nginx -t
sudo systemctl restart nginx
```

---

## 📈 Performance Optimization

### 1. OPcache Configuration

Edit `/etc/php/8.2/fpm/conf.d/opcache.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### 2. PHP-FPM Tuning

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### 3. MySQL Optimization

```sql
-- Add indexes
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
```

---

## ✅ Post-Deployment Checklist

-   [ ] Application accessible via HTTPS
-   [ ] Admin panel accessible at /warehouse
-   [ ] Redis connection working
-   [ ] Database connection working
-   [ ] Queue workers running
-   [ ] Cron jobs configured
-   [ ] Logs being written
-   [ ] Backups configured
-   [ ] Monitoring setup
-   [ ] SSL certificate valid
-   [ ] All tests passing
-   [ ] Performance acceptable

---

## 📞 Support

For issues or questions:

1. Check logs: `storage/logs/laravel.log`
2. Run diagnostics: `php artisan redis:monitor`
3. Review documentation: README.md, REDIS_SETUP.md

---

**Good luck with your deployment! 🚀**
