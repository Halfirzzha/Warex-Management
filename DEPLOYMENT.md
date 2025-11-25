# 🚀 DEPLOYMENT GUIDE - WareX Management System

## ✅ PRE-DEPLOYMENT CHECKLIST

### 1. **Environment Setup**

```bash
# Copy production environment
cp .env.production .env

# Generate application key
php artisan key:generate

# Set production values
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### 2. **Database Configuration**

```bash
# Create production database
mysql -u root -p
CREATE DATABASE warehouse_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'warehouse_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON warehouse_production.* TO 'warehouse_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env
DB_DATABASE=warehouse_production
DB_USERNAME=warehouse_user
DB_PASSWORD=strong_password

# Run migrations
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
```

### 3. **Redis Configuration**

```bash
# Install Redis
sudo apt-get install redis-server

# Configure Redis password
sudo nano /etc/redis/redis.conf
# Add: requirepass your_redis_password

# Restart Redis
sudo systemctl restart redis-server

# Update .env
REDIS_PASSWORD=your_redis_password
```

### 4. **Composer Dependencies**

```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev

# Optimize autoload
composer dump-autoload -o
```

### 5. **File Permissions**

```bash
# Set ownership
sudo chown -R www-data:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 775 storage bootstrap/cache

# Storage link
php artisan storage:link
```

### 6. **Cache Optimization**

```bash
# Clear all caches
php artisan optimize:clear

# Warm production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 7. **Queue Workers (Supervisor)**

```bash
# Create supervisor config
sudo nano /etc/supervisor/conf.d/warehouse-worker.conf
```

```ini
[program:warehouse-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/warehouse/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
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

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start warehouse-worker:*
```

### 8. **Nginx Configuration**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    root /var/www/warehouse/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Logging
    access_log /var/log/nginx/warehouse-access.log;
    error_log /var/log/nginx/warehouse-error.log;

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=warehouse:10m rate=10r/s;
    limit_req zone=warehouse burst=20 nodelay;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 4 32k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 9. **Cron Jobs (Scheduler)**

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/warehouse && php artisan schedule:run >> /dev/null 2>&1
```

### 10. **SSL Certificate (Let's Encrypt)**

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo certbot renew --dry-run
```

## 🔒 SECURITY CHECKLIST

-   ✅ Set `APP_DEBUG=false`
-   ✅ Generate new `APP_KEY`
-   ✅ Use strong database passwords
-   ✅ Enable Redis authentication
-   ✅ Configure CSRF protection
-   ✅ Enable HTTPS/SSL
-   ✅ Set secure session cookies
-   ✅ Configure firewall (UFW)
-   ✅ Disable directory listing
-   ✅ Hide server version
-   ✅ Setup fail2ban
-   ✅ Regular security updates

## 📊 MONITORING SETUP

### Application Monitoring

```bash
# Install Laravel Telescope (dev only)
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Server Monitoring

-   Setup **New Relic** or **DataDog**
-   Configure **Sentry** for error tracking
-   Setup **Uptime Robot** for availability monitoring
-   Configure **CloudWatch** for AWS deployments

## 💾 BACKUP STRATEGY

### Automated Backups

```bash
# Backup configuration already in place (Spatie Backup)
# Run manual backup
php artisan backup:run

# Schedule daily backups (already in Kernel)
# 02:00 AM daily → Full backup (database + files)
# Check backup status
php artisan backup:list
```

### Backup Storage

-   Local: `/storage/app/backups/`
-   Remote: AWS S3 / DigitalOcean Spaces
-   Retention: 7 daily backups

## 🔄 DEPLOYMENT WORKFLOW

### Initial Deployment

```bash
# 1. Clone repository
git clone https://github.com/Halfirzzha/Warex-Management.git warehouse
cd warehouse

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 3. Configure environment
cp .env.production .env
php artisan key:generate

# 4. Setup database
php artisan migrate --force
php artisan shield:install --fresh
php artisan db:seed

# 5. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 7. Start services
sudo supervisorctl start warehouse-worker:*
sudo systemctl restart nginx
```

### Update Deployment

```bash
# 1. Enable maintenance mode
php artisan down

# 2. Pull latest changes
git pull origin main

# 3. Update dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear & warm caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo supervisorctl restart warehouse-worker:*
sudo systemctl reload php8.4-fpm

# 7. Disable maintenance mode
php artisan up
```

## 🧪 POST-DEPLOYMENT VERIFICATION

```bash
# Check application status
php artisan about

# Verify routes
php artisan route:list

# Check queue workers
sudo supervisorctl status warehouse-worker:*

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check cache
php artisan cache:clear
php artisan config:clear

# Verify permissions
ls -la storage bootstrap/cache
```

## 📝 TROUBLESHOOTING

### Common Issues

1. **500 Internal Server Error**

    - Check `.env` configuration
    - Verify file permissions
    - Check Laravel logs: `storage/logs/laravel.log`
    - Check Nginx error log: `/var/log/nginx/warehouse-error.log`

2. **Queue Jobs Not Processing**

    - Check supervisor status: `sudo supervisorctl status`
    - Restart workers: `sudo supervisorctl restart warehouse-worker:*`
    - Check Redis connection: `redis-cli ping`

3. **Cache Issues**

    - Clear all caches: `php artisan optimize:clear`
    - Restart PHP-FPM: `sudo systemctl restart php8.4-fpm`
    - Clear Redis: `redis-cli FLUSHALL`

4. **Database Connection Failed**
    - Verify MySQL is running: `sudo systemctl status mysql`
    - Check credentials in `.env`
    - Test connection: `mysql -u username -p`

## 🎯 PERFORMANCE OPTIMIZATION

1. **Enable OPcache** (php.ini)

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

2. **PHP-FPM Tuning**

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 10
pm.max_spare_servers = 20
```

3. **Redis Tuning**

```conf
maxmemory 512mb
maxmemory-policy allkeys-lru
```

## 📞 SUPPORT

-   **Email**: support@your-domain.com
-   **Documentation**: https://docs.your-domain.com
-   **GitHub Issues**: https://github.com/Halfirzzha/Warex-Management/issues

---

**Version**: 1.0.0  
**Last Updated**: November 25, 2025  
**Deployment Status**: ✅ Production Ready
