# 🔐 User Security & Management System

Dokumentasi lengkap fitur keamanan dan manajemen user pada Warehouse Management System.

---

## 📋 Fitur Keamanan yang Ditambahkan

### 1. **Auto-Lock System (3x Failed Login)**

Sistem otomatis memblokir akun setelah 3x percobaan login gagal untuk mencegah brute force attack.

### 2. **Password Management Tracking**

Melacak siapa dan kapan password terakhir diubah untuk audit trail.

### 3. **Login Activity Tracking**

Mencatat waktu login pertama, terakhir, dan IP address untuk monitoring aktivitas.

### 4. **Account Status Management**

Empat status akun dengan alasan pemblokiran yang jelas:

-   **Active**: Akun aktif normal
-   **Blocked**: Diblokir otomatis sistem (3x gagal login)
-   **Suspended**: Dibekukan oleh admin
-   **Terminated**: Dihentikan oleh admin

### 5. **Audit Trail**

Melacak siapa yang membuat user (system/admin) untuk accountability.

---

## 🗄️ Struktur Database

### Tabel Users - Field Tambahan

```sql
-- Password Management
password_changed_at        TIMESTAMP     Waktu terakhir ubah password
password_changed_by        BIGINT        ID admin yang mengubah password

-- Login Activity
first_login_at            TIMESTAMP     Waktu login pertama kali
last_login_at             TIMESTAMP     Waktu login terakhir
last_login_ip             VARCHAR(45)   IP address login terakhir

-- Security & Blocking
failed_login_attempts     INT           Jumlah percobaan login gagal (default: 0)
locked_at                 TIMESTAMP     Waktu akun terkunci
account_status           ENUM          Status: active/blocked/suspended/terminated
blocked_reason           TEXT          Alasan diblokir
blocked_by               BIGINT        ID admin yang memblokir
blocked_until            TIMESTAMP     Waktu berakhir temporary block

-- Audit Trail
created_by_type          ENUM          Dibuat oleh: system/admin
created_by_admin_id      BIGINT        ID admin pembuat (jika by admin)

-- Soft Delete
deleted_at               TIMESTAMP     Untuk audit trail
```

---

## 💻 Penggunaan Model User

### Constants

```php
// Account Status
User::STATUS_ACTIVE       // 'active'
User::STATUS_BLOCKED      // 'blocked'
User::STATUS_SUSPENDED    // 'suspended'
User::STATUS_TERMINATED   // 'terminated'

// Created By
User::CREATED_BY_SYSTEM   // 'system'
User::CREATED_BY_ADMIN    // 'admin'

// Max Attempts
User::MAX_FAILED_LOGIN_ATTEMPTS  // 3
```

### Relationships

```php
// Admin yang mengubah password
$user->passwordChanger;

// Admin yang memblokir
$user->blocker;

// Admin yang membuat user
$user->createdByAdmin;
```

### Scopes

```php
// Hanya user aktif
User::active()->get();

// Hanya user terblokir
User::blocked()->get();

// Semua user terkunci (blocked/suspended/terminated)
User::locked()->get();
```

### Helper Methods

#### Check Status

```php
// Cek apakah user aktif
if ($user->isActive()) {
    // User aktif
}

// Cek apakah user diblokir
if ($user->isBlocked()) {
    // User diblokir sistem
}

// Cek apakah user terkunci (any non-active status)
if ($user->isLocked()) {
    // User tidak bisa login
}
```

#### Failed Login Management

```php
// Increment failed attempts (auto-block at 3)
$user->incrementFailedLoginAttempts();

// Reset failed attempts
$user->resetFailedLoginAttempts();
```

#### Account Blocking

```php
// Block account (sistem atau admin)
$user->blockAccount(
    reason: 'Melanggar kebijakan',
    blockedBy: auth()->id(), // ID admin
    status: User::STATUS_SUSPENDED
);

// Unblock account (hanya admin)
$user->unblockAccount();
```

#### Login Recording

```php
// Record successful login
$user->recordLogin(request()->ip());
```

#### Password Change Recording

```php
// Record password change
$user->recordPasswordChange(auth()->id()); // ID admin yang mengubah
```

#### UI Helpers

```php
// Get status label dalam Bahasa Indonesia
$user->getStatusLabel(); // "Aktif", "Diblokir Sistem", etc

// Get status color untuk UI (success/warning/danger)
$user->getStatusColor(); // "success", "danger", etc
```

---

## 🔧 Implementasi di Controller

### Login Handler dengan Auto-Lock

```php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    // Find user
    $user = User::where('email', $credentials['email'])->first();

    if (!$user) {
        return back()->withErrors(['email' => 'User tidak ditemukan']);
    }

    // Check if account is locked
    if ($user->isLocked()) {
        return back()->withErrors([
            'email' => "Akun Anda {$user->getStatusLabel()}. " .
                      ($user->blocked_reason ?: 'Hubungi admin untuk unblock.')
        ]);
    }

    // Attempt login
    if (Auth::attempt($credentials)) {
        // Success - record login & reset attempts
        $user->recordLogin($request->ip());
        $user->resetFailedLoginAttempts();

        return redirect()->intended('dashboard');
    }

    // Failed - increment attempts (auto-blocks at 3)
    $user->incrementFailedLoginAttempts();

    $remainingAttempts = User::MAX_FAILED_LOGIN_ATTEMPTS - $user->failed_login_attempts;

    if ($remainingAttempts > 0) {
        return back()->withErrors([
            'password' => "Password salah. Sisa percobaan: {$remainingAttempts}x"
        ]);
    } else {
        return back()->withErrors([
            'email' => 'Akun Anda telah diblokir karena 3x percobaan login gagal. Hubungi admin.'
        ]);
    }
}
```

### Admin: Unblock User

```php
public function unblockUser(User $user)
{
    // Pastikan hanya admin yang bisa unblock
    if (!auth()->user()->isAdmin()) {
        abort(403, 'Unauthorized');
    }

    $user->unblockAccount();

    return redirect()->back()->with('success', 'User berhasil di-unblock');
}
```

### Admin: Suspend User

```php
public function suspendUser(Request $request, User $user)
{
    $request->validate([
        'reason' => 'required|string|max:500',
    ]);

    $user->blockAccount(
        reason: $request->reason,
        blockedBy: auth()->id(),
        status: User::STATUS_SUSPENDED
    );

    return redirect()->back()->with('success', 'User berhasil di-suspend');
}
```

### Admin: Change User Password

```php
public function changeUserPassword(Request $request, User $user)
{
    $request->validate([
        'password' => 'required|min:8|confirmed',
    ]);

    $user->update([
        'password' => Hash::make($request->password),
    ]);

    $user->recordPasswordChange(auth()->id());

    return redirect()->back()->with('success', 'Password berhasil diubah');
}
```

---

## 🔴 Integrasi dengan Redis

### Caching Failed Attempts

System otomatis cache failed attempts untuk monitoring cepat:

```php
// Auto-cached saat increment
$user->incrementFailedLoginAttempts();

// Get dari cache (lebih cepat)
$attempts = Cache::get("user_failed_attempts:{$user->id}");

// Auto-cleared saat reset
$user->resetFailedLoginAttempts();
```

### Caching Blocked Status

```php
// Auto-cached saat block
$user->blockAccount('Reason here', $adminId);

// Check dari cache
if (Cache::get("user_blocked:{$user->id}")) {
    // User blocked
}

// Auto-cleared saat unblock
$user->unblockAccount();
```

### Caching Last Login

```php
// Auto-cached saat login
$user->recordLogin($ipAddress);

// Get dari cache
$lastLogin = Cache::get("user_last_login:{$user->id}");
```

---

## 📊 Query Examples

### Get Statistik User

```php
// Total user aktif
$activeUsers = User::active()->count();

// Total user terblokir
$blockedUsers = User::blocked()->count();

// User yang login hari ini
$todayLogins = User::whereDate('last_login_at', today())->count();

// User yang belum pernah login
$neverLoggedIn = User::whereNull('first_login_at')->count();

// User dengan failed attempts
$failedAttempts = User::where('failed_login_attempts', '>', 0)->get();
```

### Monitoring Security

```php
// User yang mendekati limit (2 failed attempts)
$nearLimit = User::where('failed_login_attempts', 2)
    ->where('account_status', User::STATUS_ACTIVE)
    ->get();

// User yang baru diblokir hari ini
$blockedToday = User::where('account_status', User::STATUS_BLOCKED)
    ->whereDate('locked_at', today())
    ->get();

// User yang password-nya belum pernah diubah
$neverChangedPassword = User::whereNull('password_changed_at')->get();
```

### Admin Activity

```php
// User yang dibuat oleh admin
$adminCreated = User::where('created_by_type', User::CREATED_BY_ADMIN)
    ->with('createdByAdmin')
    ->get();

// User yang password-nya diubah admin
$passwordChangedByAdmin = User::whereNotNull('password_changed_by')
    ->with('passwordChanger')
    ->get();
```

---

## 🎨 Implementasi di Filament

### UserResource - Table Columns

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

TextColumn::make('account_status')
    ->label('Status')
    ->badge()
    ->color(fn (User $record) => $record->getStatusColor())
    ->formatStateUsing(fn (User $record) => $record->getStatusLabel()),

TextColumn::make('failed_login_attempts')
    ->label('Failed Attempts')
    ->badge()
    ->color(fn (int $state) => $state >= 2 ? 'danger' : 'success'),

TextColumn::make('last_login_at')
    ->label('Last Login')
    ->dateTime('d M Y H:i')
    ->sortable(),

TextColumn::make('blocker.name')
    ->label('Blocked By')
    ->placeholder('-'),
```

### UserResource - Actions

```php
use Filament\Tables\Actions\Action;

Action::make('unblock')
    ->label('Unblock')
    ->icon('heroicon-o-lock-open')
    ->requiresConfirmation()
    ->visible(fn (User $record) => $record->isLocked())
    ->action(fn (User $record) => $record->unblockAccount())
    ->successNotification('User berhasil di-unblock'),

Action::make('suspend')
    ->label('Suspend')
    ->icon('heroicon-o-ban')
    ->requiresConfirmation()
    ->form([
        Textarea::make('reason')
            ->label('Alasan Suspend')
            ->required()
            ->maxLength(500),
    ])
    ->visible(fn (User $record) => $record->isActive())
    ->action(function (User $record, array $data) {
        $record->blockAccount(
            $data['reason'],
            auth()->id(),
            User::STATUS_SUSPENDED
        );
    }),
```

---

## ⚠️ Best Practices

### 1. Selalu Log Admin Actions

```php
// Log saat admin melakukan action penting
Log::info('User suspended', [
    'user_id' => $user->id,
    'admin_id' => auth()->id(),
    'reason' => $reason,
]);
```

### 2. Notifikasi ke User

```php
// Kirim email saat akun diblokir
$user->notify(new AccountBlockedNotification($reason));
```

### 3. Rate Limiting di Route

```php
// Proteksi endpoint login
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per minute
```

### 4. Monitor Failed Attempts

```php
// Buat job untuk monitor dan alert admin
if ($user->failed_login_attempts >= 2) {
    AlertAdminJob::dispatch($user);
}
```

### 5. Cleanup Old Data

```php
// Scheduler untuk cleanup old soft-deleted users
Schedule::command('model:prune')->daily();
```

---

## 🔍 Troubleshooting

### User Tidak Bisa Login

1. Cek `account_status`: `SELECT account_status FROM users WHERE email = '...'`
2. Cek `failed_login_attempts`: Mungkin sudah di-block
3. Cek `locked_at`: Kapan terakhir dikunci
4. Lihat `blocked_reason`: Alasan pemblokiran

### Reset Manual (Via Tinker)

```php
$user = User::where('email', 'user@example.com')->first();
$user->unblockAccount();
```

### Clear Cache

```php
php artisan redis:clear --warehouse
// atau
php artisan cache:clear
```

---

## 📚 Summary

**Fitur Keamanan yang Sudah Diimplementasi:**
✅ Auto-block setelah 3x failed login  
✅ Password change tracking  
✅ Login activity monitoring  
✅ IP address tracking  
✅ Multiple account status (active/blocked/suspended/terminated)  
✅ Admin action tracking  
✅ Audit trail lengkap  
✅ Redis caching untuk performa  
✅ Soft delete untuk data retention  
✅ Helper methods lengkap

**Status:** Production Ready ✅

---

**Last Updated:** November 21, 2024
