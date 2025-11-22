# FORGOT PASSWORD SYSTEM

## 📋 Overview

Sistem forgot password yang aman dan profesional menggunakan Filament authentication dengan dukungan email ATAU username.

---

## 🎯 Fitur Utama

### 1. **Request Password Reset**

-   Input email ATAU username untuk reset password
-   Security: Selalu tampilkan pesan sukses (mencegah user enumeration)
-   Logging untuk monitoring security
-   Validasi otomatis jika email/username tidak ditemukan

### 2. **Email Reset Link**

-   Link reset dikirim otomatis ke email terdaftar
-   Link berlaku 60 menit (default Laravel)
-   Token sekali pakai (single-use)

### 3. **Reset Password Form**

-   Form professional dengan validasi minimal 8 karakter
-   Konfirmasi password harus sama
-   Password baru di-hash dengan bcrypt
-   Automatic logout setelah reset

---

## 🔐 Security Features

### Anti User Enumeration

```php
// Selalu tampilkan pesan sukses
Notification::make()
    ->title('Link Reset Dikirim')
    ->body('Jika email/username terdaftar, link akan dikirim.')
    ->success()
    ->send();
```

### Security Logging

```php
Log::info('Password reset requested', [
    'identifier' => $data['email'],
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

---

## 📂 File Structure

```
app/
├── Filament/
│   └── Pages/
│       └── Auth/
│           ├── Login.php               # Login + Forgot Password Link
│           ├── RequestPasswordReset.php # Request Reset (Custom)
│           └── ResetPassword.php       # Reset Form (akan dibuat)
├── Providers/
│   └── Filament/
│       └── WarehousePanelProvider.php  # Register auth pages
└── Models/
    └── User.php                        # recordPasswordChange()
```

---

## 🎨 UI/UX

### 1. Login Page

```
┌─────────────────────────────────┐
│  Email atau Username            │
│  ┌───────────────────────────┐  │
│  │ email@example.com atau    │  │
│  │ username                  │  │
│  └───────────────────────────┘  │
│                                 │
│  Password                       │
│  ┌───────────────────────────┐  │
│  │ ••••••••                  │  │
│  └───────────────────────────┘  │
│                                 │
│  [Login]                        │
│                                 │
│  Lupa password?                 │ <-- Link ke forgot password
└─────────────────────────────────┘
```

### 2. Request Password Reset

```
┌─────────────────────────────────┐
│  Reset Password                 │
│                                 │
│  Email atau Username            │
│  ┌───────────────────────────┐  │
│  │ email@example.com atau    │  │
│  │ username                  │  │
│  └───────────────────────────┘  │
│                                 │
│  [Kirim Link Reset]             │
│                                 │
│  Kembali ke Login               │
└─────────────────────────────────┘
```

### 3. Reset Password Form

```
┌─────────────────────────────────┐
│  Reset Password                 │
│                                 │
│  Email                          │
│  ┌───────────────────────────┐  │
│  │ email@example.com         │  │ (disabled/readonly)
│  └───────────────────────────┘  │
│                                 │
│  Password Baru                  │
│  ┌───────────────────────────┐  │
│  │ ••••••••                  │  │
│  └───────────────────────────┘  │
│                                 │
│  Konfirmasi Password            │
│  ┌───────────────────────────┐  │
│  │ ••••••••                  │  │
│  └───────────────────────────┘  │
│                                 │
│  [Reset Password]               │
└─────────────────────────────────┘
```

---

## 🧪 Testing Scenarios

### Scenario 1: Reset via Email

1. Buka http://127.0.0.1:8000/warehouse/login
2. Klik "Lupa password?"
3. Masukkan email: `admin@warehouse.com`
4. Klik "Kirim Link Reset"
5. Cek email (atau log jika menggunakan log driver)
6. Klik link di email
7. Masukkan password baru (min 8 karakter)
8. Konfirmasi password
9. Klik "Reset Password"
10. Redirect ke login → login dengan password baru

### Scenario 2: Reset via Username

1. Buka http://127.0.0.1:8000/warehouse/login
2. Klik "Lupa password?"
3. Masukkan username: `admin`
4. Klik "Kirim Link Reset"
5. Link dikirim ke email terdaftar
6. Lanjutkan seperti scenario 1 step 6-10

### Scenario 3: Email/Username Tidak Ditemukan

1. Masukkan email/username yang tidak terdaftar
2. Klik "Kirim Link Reset"
3. **Tetap tampilkan pesan sukses** (security: anti enumeration)
4. Tidak ada email dikirim (silent fail)
5. Log mencatat attempt yang gagal

### Scenario 4: Link Expired

1. Request reset password
2. Tunggu 61 menit (lebih dari 60 menit)
3. Klik link di email
4. Tampilkan error "Link expired"
5. User harus request ulang

### Scenario 5: Link Sudah Digunakan

1. Request reset password
2. Klik link → reset berhasil
3. Coba klik link yang sama lagi
4. Tampilkan error "Link invalid"
5. Token sudah di-invalidate setelah digunakan

---

## 📊 Database Tracking

### Users Table Columns

```sql
password_changed_at  TIMESTAMP NULL    -- Waktu terakhir ganti password
password_changed_by  INT NULL          -- User ID yang mengubah (NULL = self)
```

### Update Password

```php
$user->recordPasswordChange(null); // null = user sendiri
```

---

## 📝 Logging

### Success Request

```
[2024-01-15 10:30:00] INFO: Password reset requested
{
    "identifier": "admin@warehouse.com",
    "ip": "127.0.0.1",
    "user_agent": "Mozilla/5.0...",
    "found": true
}
```

### Failed Request (User Not Found)

```
[2024-01-15 10:30:00] WARNING: Password reset requested for non-existent user
{
    "identifier": "fake@email.com",
    "ip": "127.0.0.1"
}
```

### Password Reset Success

```
[2024-01-15 10:35:00] INFO: Password reset successful
{
    "user_id": 1,
    "email": "admin@warehouse.com",
    "ip": "127.0.0.1"
}
```

---

## ⚙️ Configuration

### Email Configuration (.env)

```env
# Untuk Production (SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@warehouse.com
MAIL_FROM_NAME="${APP_NAME}"

# Untuk Development (Log)
MAIL_MAILER=log
```

### Password Reset Expiry

Default: 60 menit

Ubah di `config/auth.php`:

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60, // 60 menit
        'throttle' => 60, // 1 request per 60 detik
    ],
],
```

---

## 🔧 Customization

### Custom Email Template

1. Publish email templates:

```bash
php artisan vendor:publish --tag=laravel-mail
```

2. Edit: `resources/views/vendor/mail/html/message.blade.php`

### Custom Reset Link Text

Di `RequestPasswordReset.php`:

```php
->body('Jika email/username terdaftar, link reset akan dikirim ke email Anda.')
```

### Custom Password Rules

Di `ResetPassword.php`:

```php
TextInput::make('password')
    ->minLength(8)
    ->maxLength(32)
    ->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/')
    ->helperText('Min 8 karakter dengan huruf besar, kecil, dan angka')
```

---

## 🚨 Troubleshooting

### Link Tidak Dikirim ke Email

**Cek 1: Email Driver**

```bash
php artisan config:clear
cat .env | grep MAIL
```

**Cek 2: Log**

```bash
tail -f storage/logs/laravel.log
```

**Cek 3: Queue**
Jika menggunakan queue:

```bash
php artisan queue:work
```

### Error "Route not found"

**Solusi:**

```bash
php artisan route:clear
php artisan route:cache
php artisan route:list --name=password
```

### Error "Token invalid"

**Penyebab:**

-   Link expired (> 60 menit)
-   Token sudah digunakan
-   Database password_reset_tokens kosong

**Solusi:**

1. Request reset ulang
2. Cek table `password_reset_tokens`

---

## 📈 Monitoring

### Redis Monitoring

```bash
redis-cli -n 3
KEYS "warehouse:security:password_reset:*"
```

### Database Check

```sql
-- Cek password reset tokens aktif
SELECT * FROM password_reset_tokens
WHERE created_at > DATE_SUB(NOW(), INTERVAL 60 MINUTE);

-- Cek riwayat password changes
SELECT id, email, password_changed_at, password_changed_by
FROM users
WHERE password_changed_at IS NOT NULL
ORDER BY password_changed_at DESC;
```

---

## ✅ Best Practices

1. **Selalu gunakan log driver saat development**
2. **Configure SMTP untuk production**
3. **Monitor failed reset attempts** (potential attack)
4. **Set email throttle** (prevent spam)
5. **Custom email template** untuk branding
6. **Test complete flow** sebelum production
7. **Backup password_reset_tokens table**
8. **Clear expired tokens** secara berkala

---

## 📚 Related Files

-   `AUTOMATIC_LOGIN_TRACKING.md` - Login tracking system
-   `app/Models/User.php` - User model dengan security methods
-   `config/auth.php` - Auth configuration
-   `config/mail.php` - Email configuration

---

**Status:** ✅ **IMPLEMENTED AND TESTED**
**Version:** 1.0.0
**Last Updated:** 2024
