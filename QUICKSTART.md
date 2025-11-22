# 🚀 Quick Start Guide - Warehouse Management System

## Langkah Cepat Memulai

### 1️⃣ Install Redis (Jika Belum Ada)

**macOS dengan Homebrew:**

```bash
brew install redis
brew services start redis
```

**Verifikasi Redis:**

```bash
redis-cli ping
# Harus menampilkan: PONG
```

### 2️⃣ Jalankan Aplikasi

```bash
# Buka terminal di folder project
cd /Users/halfirzzha/Desktop/Management-WareHouse

# Jalankan server
php artisan serve
```

### 3️⃣ Akses Admin Panel

Buka browser dan akses:

```
http://127.0.0.1:8000/warehouse
```

**Login dengan:**

-   **Email:** Developer@Warex.id
-   **Password:** (password yang dibuat saat instalasi)

### 4️⃣ Test Redis Connection

Buka terminal baru:

```bash
php artisan redis:monitor
```

Anda akan melihat informasi Redis seperti:

-   ✓ Health status
-   Server version
-   Memory usage
-   Connected clients
-   Database size

### 5️⃣ (Optional) Jalankan Queue Worker

Untuk background jobs:

```bash
php artisan queue:work redis
```

---

## 📋 Checklist Instalasi

-   [ ] PHP 8.2+ terinstall
-   [ ] Composer terinstall
-   [ ] MySQL/XAMPP berjalan
-   [ ] Database `dev_management` sudah dibuat
-   [ ] Migrasi database berhasil
-   [ ] Redis terinstall dan berjalan
-   [ ] User admin Filament sudah dibuat
-   [ ] Server Laravel berjalan
-   [ ] Bisa akses admin panel

---

## 🔧 Command Berguna

### Development

```bash
# Jalankan server
php artisan serve

# Monitor Redis
php artisan redis:monitor

# Clear cache
php artisan cache:clear
php artisan redis:clear --warehouse

# View routes
php artisan route:list

# Tinker (testing)
php artisan tinker
```

### Production

```bash
# Optimize aplikasi
php artisan optimize

# Jalankan queue worker
php artisan queue:work redis --daemon

# Monitor queue
php artisan queue:monitor redis
```

---

## 🐛 Troubleshooting

### Redis Connection Refused

```bash
# Cek apakah Redis berjalan
redis-cli ping

# Start Redis (macOS)
brew services start redis

# Atau manual
redis-server
```

### Database Connection Error

-   Pastikan XAMPP MySQL berjalan
-   Cek kredensial di file `.env`
-   Pastikan database `dev_management` sudah ada

### Filament 404 Error

-   Jalankan: `php artisan config:clear`
-   Jalankan: `php artisan route:clear`
-   Cek apakah WarehousePanelProvider terdaftar di `bootstrap/providers.php`

### Cache Tidak Update

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan redis:clear --force
```

---

## 📱 Testing Fitur

### Test Cache

```bash
php artisan tinker
```

Kemudian di Tinker:

```php
// Test cache
Cache::put('test_key', 'Hello Redis!', 60);
Cache::get('test_key'); // Harus return: "Hello Redis!"

// Test RedisService
use App\Services\RedisService;
RedisService::cacheWarehouseData('test', ['name' => 'Product 1'], 300);
RedisService::getWarehouseData('test');

// Check health
RedisService::isHealthy(); // Harus return: true
```

### Test Queue

Buat job test:

```bash
php artisan make:job TestJob
```

Dispatch job di Tinker:

```php
dispatch(new App\Jobs\TestJob());
```

Lihat di queue:

```bash
php artisan queue:work redis --once
```

---

## 🎯 Next Steps

1. ✅ **Customize Admin Panel** - Edit `app/Providers/Filament/WarehousePanelProvider.php`
2. ✅ **Buat Resource** - `php artisan make:filament-resource Product`
3. ✅ **Setup Redis untuk Production** - Tambah password di `.env`
4. ✅ **Configure Supervisor** - Untuk queue worker auto-restart
5. ✅ **Setup Backup** - Install spatie/laravel-backup

---

## 📚 Dokumentasi Lengkap

Lihat file berikut untuk informasi detail:

-   **README.md** - Dokumentasi lengkap proyek
-   **REDIS_SETUP.md** - Panduan Redis lengkap dengan best practices

---

## ❓ Butuh Bantuan?

### Redis Commands

```bash
php artisan redis:monitor          # Monitor status
php artisan redis:clear             # Clear cache
php artisan redis:clear --warehouse # Clear warehouse cache only
```

### Laravel Commands

```bash
php artisan list                    # List semua command
php artisan help [command]          # Help untuk command tertentu
```

### Redis CLI

```bash
redis-cli                           # Connect ke Redis
PING                                # Test connection
KEYS *                              # List all keys
GET key_name                        # Get key value
INFO                                # Server info
```

---

## ✅ Selesai!

Aplikasi Anda sekarang sudah siap dengan:

-   ✅ **Laravel 11** dengan struktur profesional
-   ✅ **Redis Ecosystem** untuk cache, session, dan queue
-   ✅ **Filament Admin Panel** untuk manajemen data
-   ✅ **MySQL Database** untuk persistent storage
-   ✅ **Helper Commands** untuk monitoring dan maintenance

**Happy Coding! 🚀**
