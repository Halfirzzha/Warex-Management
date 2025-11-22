# 🏢 Warehouse Management System

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Professional Warehouse Management System with Redis Ecosystem & Filament Admin Panel</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red?logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/Filament-3.x-orange?logo=filament" alt="Filament">
  <img src="https://img.shields.io/badge/Redis-7.x-red?logo=redis" alt="Redis">
  <img src="https://img.shields.io/badge/MySQL-8.x-blue?logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/PHP-8.2+-purple?logo=php" alt="PHP">
</p>

---

## 📋 Daftar Isi

-   [Tentang Proyek](#-tentang-proyek)
-   [Fitur Utama](#-fitur-utama)
-   [Teknologi Stack](#-teknologi-stack)
-   [Persyaratan Sistem](#-persyaratan-sistem)
-   [Instalasi](#-instalasi)
-   [Konfigurasi Redis](#-konfigurasi-redis)
-   [Menjalankan Aplikasi](#-menjalankan-aplikasi)
-   [Filament Admin Panel](#-filament-admin-panel)
-   [Command Artisan](#-command-artisan)
-   [Dokumentasi](#-dokumentasi)

---

## 🎯 Tentang Proyek

Warehouse Management System adalah sistem manajemen gudang yang dibangun dengan Laravel 11, menggunakan ekosistem Redis profesional untuk cache, session, dan queue management. Sistem ini dilengkapi dengan Filament Admin Panel untuk kemudahan pengelolaan data.

### Arsitektur Redis

Sistem ini menggunakan **Redis dengan multi-database architecture** untuk pemisahan concern yang optimal:

| Database   | Fungsi            | Connection |
| ---------- | ----------------- | ---------- |
| Redis DB 0 | Default/General   | `default`  |
| Redis DB 1 | Application Cache | `cache`    |
| Redis DB 2 | Queue Management  | `queue`    |
| Redis DB 3 | Session Storage   | `session`  |

---

## ✨ Fitur Utama

### 🚀 Performance & Caching

-   ✅ **Redis Cache** - Caching layer profesional dengan Predis client
-   ✅ **Multi-Database Redis** - Pemisahan cache, session, dan queue
-   ✅ **Cache Helper Service** - RedisService untuk operasi cache yang mudah
-   ✅ **Optimized Configuration** - Konfigurasi production-ready

### 🎨 Admin Panel (Filament)

-   ✅ **Filament v3.x** - Modern admin panel framework
-   ✅ **Panel ID: warehouse** - Custom panel untuk warehouse management
-   ✅ **User Management** - Built-in user authentication
-   ✅ **Responsive Design** - Mobile-friendly interface

### 🔧 Developer Tools

-   ✅ **Redis Monitor Command** - Monitor Redis status real-time
-   ✅ **Redis Clear Command** - Clear cache dengan mudah
-   ✅ **Queue Worker Ready** - Background job processing
-   ✅ **Comprehensive Documentation** - Dokumentasi lengkap

---

## 🛠️ Teknologi Stack

### Backend

-   **Laravel 11.x** - PHP Framework
-   **PHP 8.2+** - Programming Language
-   **MySQL 8.x** - Database
-   **Redis** - Cache & Queue Driver

### Packages

-   **Predis** - PHP Redis Client
-   **Filament 3.x** - Admin Panel Framework
-   **Livewire 3.x** - Dynamic UI Components

### Development Tools

-   **XAMPP** - Local Development Environment
-   **Composer** - Dependency Management
-   **NPM/Yarn** - Frontend Asset Management

---

## 📦 Persyaratan Sistem

### Software Requirements

-   PHP >= 8.2
-   Composer >= 2.0
-   MySQL >= 8.0
-   Redis Server >= 5.0
-   Node.js >= 18.x (untuk asset compilation)

### PHP Extensions

-   BCMath
-   Ctype
-   Fileinfo
-   JSON
-   Mbstring
-   OpenSSL
-   PDO
-   Tokenizer
-   XML

---

## 🚀 Instalasi

### 1. Clone Repository (atau buka folder existing)

```bash
cd /Users/halfirzzha/Desktop/Management-WareHouse
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install
```

### 3. Environment Configuration

File `.env` sudah dikonfigurasi dengan:

-   Database: `dev_management`
-   Redis: Multi-database setup
-   Cache Driver: Redis
-   Session Driver: Redis
-   Queue Connection: Redis

### 4. Generate Application Key (jika belum ada)

```bash
php artisan key:generate
```

### 5. Buat Database

Jika menggunakan XAMPP:

```bash
/Applications/XAMPP/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS dev_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Install Redis

**macOS (Homebrew):**

```bash
brew install redis
brew services start redis
```

**Docker:**

```bash
docker run -d -p 6379:6379 --name redis-warehouse redis:alpine
```

### 8. Compile Assets

```bash
npm run build
# atau untuk development
npm run dev
```

---

## ⚙️ Konfigurasi Redis

### Environment Variables

```env
# Redis Configuration
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_SESSION_DB=3
REDIS_PREFIX=warehouse

# Drivers
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Test Redis Connection

```bash
# Ping Redis
redis-cli ping

# Test dengan Tinker
php artisan tinker
>>> Cache::put('test', 'Hello Redis!', 60);
>>> Cache::get('test');
```

Lihat dokumentasi lengkap di [REDIS_SETUP.md](REDIS_SETUP.md)

---

## 🎮 Menjalankan Aplikasi

### Development Server

```bash
php artisan serve
```

Aplikasi akan berjalan di: `http://127.0.0.1:8000`

### Queue Worker

```bash
php artisan queue:work redis
```

### Asset Watching

```bash
npm run dev
```

---

## 👤 Filament Admin Panel

### Access Admin Panel

**URL:** `http://127.0.0.1:8000/warehouse`

### Default Admin Credentials

Buat user admin dengan:

```bash
php artisan make:filament-user
```

Atau gunakan kredensial yang sudah dibuat:

-   **Email:** Developer@Warex.id
-   **Password:** (password yang Anda input saat instalasi)

### Fitur Admin Panel

-   Dashboard analytics
-   User management
-   CRUD operations
-   Data tables dengan filter & search
-   Form builder
-   Notifications
-   Real-time updates

---

## 🔨 Command Artisan

### Redis Management Commands

#### Monitor Redis

```bash
# Monitor default cache connection
php artisan redis:monitor

# Monitor specific connection
php artisan redis:monitor --connection=queue
```

Output:

-   Redis health status
-   Server information
-   Memory usage
-   Connected clients
-   Database size
-   All connections status

#### Clear Redis Cache

```bash
# Clear cache connection (with confirmation)
php artisan redis:clear

# Clear specific connection
php artisan redis:clear --connection=queue

# Clear only warehouse cache
php artisan redis:clear --warehouse

# Force clear without confirmation
php artisan redis:clear --force
```

### Standard Laravel Commands

```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Optimize application
php artisan optimize

# View routes
php artisan route:list

# Queue commands
php artisan queue:work
php artisan queue:failed
php artisan queue:retry {id}
```

---

## 📚 Dokumentasi

### Dokumentasi Proyek

-   [Redis Setup & Usage](REDIS_SETUP.md) - Panduan lengkap Redis
-   [Filament Documentation](https://filamentphp.com/docs) - Official Filament docs
-   [Laravel Documentation](https://laravel.com/docs) - Official Laravel docs

### Helper Service

Gunakan `RedisService` untuk operasi cache yang lebih mudah:

```php
use App\Services\RedisService;

// Cache warehouse data
RedisService::cacheWarehouseData('products', $products, 3600);

// Get warehouse data
$products = RedisService::getWarehouseData('products');

// Remember (cache if not exists)
$stats = RedisService::rememberWarehouseData('statistics', 300, function() {
    return calculateStatistics();
});

// Counters
RedisService::incrementCounter('page_views');
$views = RedisService::getCounter('page_views');

// Health check
if (RedisService::isHealthy()) {
    // Redis is up and running
}
```

---

## 🎨 Filament User Resource - Enterprise Features

### Advanced User Management Interface

UserResource telah dikonfigurasi dengan fitur enterprise-level:

#### 🖼️ Form Layout Features

-   **Grid Layout**: Responsive 3-column grid untuk layout profesional
-   **File Upload**: Avatar upload dengan image editor & circle cropper
-   **Section Grouping**: Organized sections dengan icons dan collapse
-   **Smart Fields**:
    -   Select dengan native(false) untuk UX lebih baik
    -   DateTimePicker dengan format Indonesia
    -   Textarea untuk alasan pemblokiran
    -   Placeholder informatif di semua field

#### 📊 Table Display Features

-   **Badge Columns**: Status akun dengan warna dinamis (success/warning/danger)
-   **Image Column**: Avatar circular dengan default image
-   **Icon Support**: Icons di setiap kolom untuk visual clarity
-   **Toggleable Columns**: Hide/show columns sesuai kebutuhan
-   **Description**: Email muncul di bawah nama, IP di bawah last login
-   **Smart Formatting**: Number formatting untuk failed attempts

#### 🎯 Advanced Actions

1. **View Action**: Dedicated view page dengan Infolist
2. **Edit Action**: Full edit capabilities
3. **Reset Failed Login**: Reset counter percobaan login
4. **Block/Unblock**: Block user dengan form alasan & jenis block
5. **Soft Delete**: Delete dengan kemampuan restore
6. **Force Delete**: Permanent delete

#### 🔍 Smart Filters

-   **Status Filter**: Multi-select untuk account_status
-   **Created By Filter**: Filter berdasarkan system/admin
-   **Failed Attempts**: Quick filter untuk user dengan login issues
-   **Never Logged In**: Identify dormant accounts
-   **Locked Status**: View all locked accounts
-   **Trashed Filter**: Manage soft deleted records

#### 📈 Dashboard Widgets

1. **UserStatsOverview**: 6 stat cards dengan real-time data

    - Total Users
    - Active Users (dengan persentase)
    - Recently Active (15 menit terakhir)
    - Blocked/Locked Users
    - Failed Login Attempts
    - Never Logged In

2. **UserLoginActivityChart**: Line chart 7 hari login activity

#### 🎭 View Page (Infolist)

Dedicated view page dengan:

-   **Section-based Layout**: Organized information sections
-   **Image Entry**: Large circular avatar (120px)
-   **Badge Entries**: Colored badges untuk status
-   **Copy Actions**: One-click copy untuk UUID, email, username, IP
-   **Smart Visibility**: Fields muncul conditional berdasarkan data
-   **Rich Formatting**: Date formatting, placeholder handling
-   **Relationship Display**: Show related user names (blocker, password changer, creator)

#### 🚀 Performance Features

-   **Redis Integration**: All data synced to Redis untuk ultra-fast access
-   **Auto Polling**: Table auto-refresh setiap 30 detik
-   **Defer Loading**: Lazy load untuk performance optimal
-   **Session Persistence**: Filter, search, sort tersimpan di session
-   **SPA Mode**: Single Page Application untuk smooth transitions

### Navigation & Branding

```php
- Navigation Group: 'User Management'
- Navigation Icon: 'heroicon-o-user-group'
- Navigation Badge: Real-time user count dengan color (warning jika > 50)
- Record Title: User's name untuk breadcrumb
```

### Access URLs

-   **List Users**: http://127.0.0.1:8080/warehouse/users
-   **Create User**: http://127.0.0.1:8080/warehouse/users/create
-   **View User**: http://127.0.0.1:8080/warehouse/users/{id}
-   **Edit User**: http://127.0.0.1:8080/warehouse/users/{id}/edit

---

## 🔧 Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

### Database Seeding

```bash
php artisan db:seed
```

---

## 📝 Notes

### Credential Admin

-   **Nama:** Founder Creative Trees
-   **Email:** Developer@warex.id
-   **Panel URL:** `/warehouse`

### Database

-   **Database Name:** dev_management
-   **Connection:** MySQL (XAMPP)
-   **Host:** 127.0.0.1
-   **Port:** 3306

### Redis

-   **Host:** 127.0.0.1
-   **Port:** 6379
-   **Client:** Predis
-   **Databases:** 0 (default), 1 (cache), 2 (queue), 3 (session)

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 👨‍💻 Developer

**Professional Full Stack Developer**

Made with ❤️ for Warehouse Management

---

## 🔗 Quick Links

-   **Admin Panel:** http://127.0.0.1:8080/warehouse
-   **Application:** http://127.0.0.1:8080
-   **Redis Monitor:** `php artisan redis:monitor`
-   **Documentation:** [REDIS_SETUP.md](REDIS_SETUP.md)
-   **User Security:** [USER_SECURITY.md](USER_SECURITY.md)

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
