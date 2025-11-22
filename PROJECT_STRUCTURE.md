# 📁 Project Structure - Warehouse Management System

Dokumentasi struktur file dan folder dalam proyek.

---

## 🗂️ Root Directory Structure

```
Management-WareHouse/
├── 📄 README.md                    # Dokumentasi utama proyek
├── 📄 REDIS_SETUP.md              # Panduan lengkap Redis
├── 📄 QUICKSTART.md               # Quick start guide
├── 📄 DEPLOYMENT.md               # Production deployment guide
├── 📄 CHANGELOG.md                # Version history & release notes
├── 📄 SETUP_COMPLETE.md           # Summary setup yang telah dilakukan
├── 📄 PROJECT_STRUCTURE.md        # File ini - struktur proyek
│
├── 📄 .env                        # Environment configuration (Redis, DB, etc)
├── 📄 .env.example                # Environment template
├── 📄 composer.json               # PHP dependencies
├── 📄 package.json                # NPM dependencies
├── 📄 artisan                     # Laravel CLI tool
├── 📄 phpunit.xml                 # PHPUnit configuration
├── 📄 vite.config.js              # Vite build configuration
│
├── 📁 app/                        # Application code
├── 📁 bootstrap/                  # Bootstrap files
├── 📁 config/                     # Configuration files
├── 📁 database/                   # Migrations, seeders, factories
├── 📁 public/                     # Public assets
├── 📁 resources/                  # Views, CSS, JS
├── 📁 routes/                     # Route definitions
├── 📁 storage/                    # Generated files, logs, cache
├── 📁 tests/                      # Test files
└── 📁 vendor/                     # Composer dependencies
```

---

## 📦 App Directory (Detailed)

```
app/
├── 📁 Console/
│   ├── 📁 Commands/
│   │   ├── 🔴 RedisMonitor.php        # Command: redis:monitor
│   │   └── 🔴 RedisClear.php          # Command: redis:clear
│   └── Kernel.php                     # Console kernel
│
├── 📁 Http/
│   ├── 📁 Controllers/
│   │   ├── Controller.php             # Base controller
│   │   └── 🔴 ExampleRedisController.php  # Redis usage examples
│   │
│   ├── 📁 Middleware/
│   │   └── [Middleware files]
│   │
│   └── Kernel.php                     # HTTP kernel
│
├── 📁 Models/
│   └── User.php                       # User model
│
├── 📁 Providers/
│   ├── AppServiceProvider.php         # Main service provider
│   └── 📁 Filament/
│       └── 🎨 WarehousePanelProvider.php  # Filament panel config
│
└── 📁 Services/
    └── 🔴 RedisService.php            # Redis helper service ⭐
```

### 🔴 Redis-Related Files

#### 1. **RedisService.php** ⭐

**Location:** `app/Services/RedisService.php`  
**Purpose:** Helper service untuk operasi Redis  
**Key Methods:**

-   `cacheWarehouseData()` - Cache data with prefix
-   `getWarehouseData()` - Get cached data
-   `rememberWarehouseData()` - Cache if not exists
-   `forgetWarehouseData()` - Remove cache
-   `flushWarehouseCache()` - Clear all warehouse cache
-   `incrementCounter()` - Increment counter
-   `decrementCounter()` - Decrement counter
-   `isHealthy()` - Health check
-   `getConnectionInfo()` - Get Redis info

#### 2. **RedisMonitor.php**

**Location:** `app/Console/Commands/RedisMonitor.php`  
**Command:** `php artisan redis:monitor`  
**Purpose:** Monitor Redis server status  
**Features:**

-   Health check
-   Server information
-   Memory usage
-   Connection status
-   Database size

#### 3. **RedisClear.php**

**Location:** `app/Console/Commands/RedisClear.php`  
**Command:** `php artisan redis:clear`  
**Purpose:** Clear Redis cache  
**Options:**

-   `--connection=` - Specify connection
-   `--warehouse` - Clear only warehouse cache
-   `--force` - Skip confirmation

#### 4. **ExampleRedisController.php**

**Location:** `app/Http/Controllers/ExampleRedisController.php`  
**Purpose:** 15 contoh penggunaan Redis dalam aplikasi  
**Examples:**

1. Cache statistics
2. Paginated products
3. Page view counter
4. Temporary data
5. Multiple cache operations
6. Search results
7. Rate limiting
8. User preferences
9. Top selling products
10. And more...

---

## 🎨 Filament Structure

```
app/Providers/Filament/
└── WarehousePanelProvider.php         # Panel configuration
    ├── Panel ID: warehouse
    ├── Route: /warehouse
    ├── Navigation
    ├── Colors & Branding
    └── Authentication

bootstrap/providers.php
└── Registered: WarehousePanelProvider  # ✅ Auto-registered

public/
├── 📁 js/filament/                    # Filament JS assets
└── 📁 css/filament/                   # Filament CSS assets
```

### Filament Routes

```
/warehouse              → Dashboard
/warehouse/login        → Login page
/warehouse/logout       → Logout action
```

---

## ⚙️ Configuration Files (Detailed)

```
config/
├── 🔴 database.php                    # ⭐ Redis connections configured
│   ├── redis.client = predis
│   ├── redis.default (DB 0)
│   ├── redis.cache (DB 1)
│   ├── redis.queue (DB 2)
│   └── redis.session (DB 3)
│
├── 🔴 cache.php                       # ⭐ Cache driver = redis
│   └── stores.redis.connection = cache
│
├── 🔴 session.php                     # ⭐ Session driver = redis
│   └── connection = session
│
├── 🔴 queue.php                       # ⭐ Queue connection = redis
│   └── connections.redis.connection = queue
│
├── app.php                            # Application config
├── auth.php                           # Authentication
├── filesystems.php                    # File storage
├── logging.php                        # Logging config
├── mail.php                           # Mail config
└── services.php                       # External services
```

---

## 🗄️ Database Structure

```
database/
├── 📁 factories/
│   └── UserFactory.php                # User factory for testing
│
├── 📁 migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   └── 0001_01_01_000002_create_jobs_table.php
│
└── 📁 seeders/
    └── DatabaseSeeder.php             # Database seeder
```

### Tables Created

-   `users` - User accounts
-   `password_reset_tokens`
-   `sessions` - Session data
-   `cache` - Cache fallback (if Redis down)
-   `cache_locks`
-   `jobs` - Queue jobs
-   `job_batches`
-   `failed_jobs`

---

## 📚 Documentation Files

```
📄 README.md                 (9.7 KB)
   └── Complete project documentation
       ├── Overview
       ├── Features
       ├── Installation
       ├── Configuration
       ├── Usage examples
       └── Commands reference

📄 REDIS_SETUP.md            (7.0 KB)
   └── Comprehensive Redis guide
       ├── Architecture
       ├── Configuration
       ├── Usage examples
       ├── Monitoring
       ├── Best practices
       ├── Security
       └── Troubleshooting

📄 QUICKSTART.md             (4.8 KB)
   └── Quick start guide
       ├── Installation steps
       ├── Setup checklist
       ├── Common commands
       ├── Testing
       └── Troubleshooting

📄 DEPLOYMENT.md             (10.5 KB)
   └── Production deployment guide
       ├── Server requirements
       ├── Environment setup
       ├── Web server config
       ├── Supervisor setup
       ├── Security
       └── Monitoring

📄 CHANGELOG.md              (5.9 KB)
   └── Version history
       ├── Release notes
       ├── Feature list
       ├── Planned features
       └── Version tracking

📄 SETUP_COMPLETE.md         (13.1 KB)
   └── Setup summary
       ├── What's installed
       ├── Configuration details
       ├── Custom tools
       ├── Quick start
       └── Next steps

📄 PROJECT_STRUCTURE.md      (This file)
   └── Project structure documentation
```

---

## 🔧 Bootstrap Directory

```
bootstrap/
├── app.php                            # Application bootstrap
├── providers.php                      # Service providers registry
│   ├── AppServiceProvider
│   └── 🎨 WarehousePanelProvider     # ✅ Filament registered
│
└── 📁 cache/
    ├── packages.php                   # Package cache
    └── services.php                   # Services cache
```

---

## 📦 Public Directory

```
public/
├── index.php                          # Entry point
├── robots.txt                         # Search engine directives
│
├── 📁 js/filament/                    # Filament JavaScript
│   ├── forms/
│   ├── tables/
│   ├── widgets/
│   ├── notifications/
│   └── support/
│
└── 📁 css/filament/                   # Filament CSS
    ├── forms/
    └── support/
```

---

## 🎨 Resources Directory

```
resources/
├── 📁 css/
│   └── app.css                        # Application CSS
│
├── 📁 js/
│   ├── app.js                         # Application JS
│   └── bootstrap.js                   # Bootstrap JS
│
└── 📁 views/
    └── welcome.blade.php              # Welcome page
```

---

## 🛣️ Routes Directory

```
routes/
├── console.php                        # Console routes (Artisan commands)
└── web.php                            # Web routes
```

---

## 💾 Storage Directory

```
storage/
├── 📁 app/
│   ├── private/                       # Private files
│   └── public/                        # Public files (symlinked)
│
├── 📁 framework/
│   ├── cache/                         # Framework cache
│   ├── sessions/                      # File sessions (not used, using Redis)
│   ├── testing/                       # Testing files
│   └── views/                         # Compiled views
│
└── 📁 logs/
    └── laravel.log                    # Application logs
```

---

## 🧪 Tests Directory

```
tests/
├── TestCase.php                       # Base test case
│
├── 📁 Feature/
│   └── ExampleTest.php                # Feature tests
│
└── 📁 Unit/
    └── ExampleTest.php                # Unit tests
```

---

## 🎯 Key Files to Remember

### Most Important Files (⭐)

1. **RedisService.php** ⭐⭐⭐

    - Path: `app/Services/RedisService.php`
    - Purpose: Main Redis helper
    - Use: Import and use in any controller/service

2. **WarehousePanelProvider.php** ⭐⭐⭐

    - Path: `app/Providers/Filament/WarehousePanelProvider.php`
    - Purpose: Filament panel configuration
    - Use: Customize admin panel here

3. **.env** ⭐⭐⭐

    - Path: `.env`
    - Purpose: Environment configuration
    - Contains: Redis, Database, Cache settings

4. **database.php** ⭐⭐

    - Path: `config/database.php`
    - Purpose: Redis connections
    - Contains: 4 Redis database connections

5. **README.md** ⭐⭐
    - Path: `README.md`
    - Purpose: Main documentation
    - Read first for overview

---

## 📋 File Naming Conventions

### Laravel Conventions

-   Controllers: `*Controller.php` (PascalCase)
-   Models: `*.php` (PascalCase)
-   Migrations: `yyyy_mm_dd_hhmmss_*.php`
-   Commands: `*.php` (PascalCase)
-   Services: `*Service.php` (PascalCase)

### Documentation

-   Markdown files: `UPPERCASE.md`
-   Example: `README.md`, `QUICKSTART.md`

---

## 🔍 Finding Files

### Quick Search Commands

```bash
# Find all Redis-related files
find . -name "*Redis*" -o -name "*redis*"

# Find all Filament files
find . -name "*Filament*" -o -name "*filament*"

# Find all documentation
ls -la *.md

# Find controllers
find app/Http/Controllers -name "*.php"

# Find commands
find app/Console/Commands -name "*.php"

# Find services
find app/Services -name "*.php"
```

---

## 📊 File Statistics

### Lines of Code (Approx)

```
RedisService.php:           ~300 lines
RedisMonitor.php:           ~110 lines
RedisClear.php:             ~75 lines
ExampleRedisController.php: ~280 lines
WarehousePanelProvider.php: ~50 lines (generated)
```

### Documentation

```
README.md:          ~350 lines
REDIS_SETUP.md:     ~280 lines
QUICKSTART.md:      ~180 lines
DEPLOYMENT.md:      ~380 lines
CHANGELOG.md:       ~210 lines
SETUP_COMPLETE.md:  ~450 lines
```

### Total Custom Code: ~800+ lines

### Total Documentation: ~1850+ lines

---

## 🎯 Where to Add New Features

### Adding New Models

```
app/Models/
└── YourModel.php
```

### Adding New Controllers

```
app/Http/Controllers/
└── YourController.php
```

### Adding New Commands

```
app/Console/Commands/
└── YourCommand.php
```

### Adding New Services

```
app/Services/
└── YourService.php
```

### Adding Filament Resources

```bash
php artisan make:filament-resource Product
```

Creates:

```
app/Filament/Resources/
├── ProductResource.php
└── ProductResource/
    ├── Pages/
    │   ├── CreateProduct.php
    │   ├── EditProduct.php
    │   └── ListProducts.php
    └── RelationManagers/
```

---

## 🔗 Dependencies Map

```
┌─────────────────────────────────────┐
│         Application Layer           │
├─────────────────────────────────────┤
│  Controllers → Services → Models    │
│       ↓           ↓                 │
│  RedisService  Database             │
└─────────────────────────────────────┘
           ↓           ↓
    ┌──────────┐  ┌─────────┐
    │  Redis   │  │  MySQL  │
    │  (Predis)│  │         │
    └──────────┘  └─────────┘
```

---

## ✅ Summary

**Total Custom Files Created:** 7

-   3 PHP classes (Service + 2 Commands)
-   1 Controller example
-   6 Documentation files

**Total Modified Files:** 5

-   .env
-   config/database.php
-   config/cache.php
-   config/queue.php
-   config/session.php

**Status:** ✅ All organized and documented

---

**Last Updated:** November 21, 2024  
**Project Status:** Ready for Development
