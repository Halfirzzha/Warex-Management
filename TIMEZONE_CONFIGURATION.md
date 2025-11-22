# Timezone Configuration - Asia/Jakarta (WIB)

## ✅ Implemented Changes

### 1. **Application Timezone** ✅

-   **File**: `config/app.php`
-   **Before**: `'timezone' => 'UTC'`
-   **After**: `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')`
-   **Impact**: All Laravel datetime functions now use Jakarta timezone by default

### 2. **Environment Variable** ✅

-   **File**: `.env`
-   **Added**: `APP_TIMEZONE=Asia/Jakarta`
-   **Benefit**: Easy to change timezone without modifying code

### 3. **DateTime Helper Functions** ✅

-   **File**: `app/Helpers/DateTimeHelper.php`
-   **Auto-loaded**: Added to `composer.json` autoload files

**Available Functions:**

```php
// Format datetime with timezone
format_datetime($date)                    // 22 Nov 2025 14:30:45
format_datetime($date, 'd/m/Y H:i')      // 22/11/2025 14:30

// Format date only
format_date($date)                        // 22 Nov 2025
format_date($date, 'l, d F Y')           // Friday, 22 November 2025

// Format time only
format_time($date)                        // 14:30:45
format_time($date, 'H:i')                // 14:30

// Human readable
format_datetime_human($date)              // "2 hours ago", "in 3 days"

// Get current time in Jakarta
now_jakarta()                             // Carbon instance
today_jakarta()                           // Carbon instance (today 00:00:00)
```

### 4. **Filament UserResource** ✅

-   **Updated columns** with timezone:
    -   `last_login_at`
    -   `first_login_at`
    -   `locked_at`
    -   `blocked_until`
    -   `created_at`
    -   `updated_at`
    -   `deleted_at`

**Format**: `d M Y H:i:s` (e.g., 22 Nov 2025 14:30:45)
**Timezone**: Explicitly set to `Asia/Jakarta` using `->timezone('Asia/Jakarta')`

### 5. **ViewUser Page** ✅

-   All datetime fields updated with timezone
-   Consistent formatting across all sections
-   Proper timezone handling for all TextEntry fields

## 🕐 Time Display Format

### Standard Formats

-   **Full DateTime**: `d M Y H:i:s` → `22 Nov 2025 14:30:45`
-   **Date Only**: `d M Y` → `22 Nov 2025`
-   **Time Only**: `H:i:s` → `14:30:45`
-   **Long Date**: `l, d F Y` → `Friday, 22 November 2025`

### Language

-   **Months**: English (Jan, Feb, Mar, etc.)
-   **Days**: English (Mon, Tue, Wed, etc.)
-   **Timezone**: WIB (GMT+7) / Asia/Jakarta

## 📊 Timezone Details

### Indonesia Time Zones

-   **WIB** (Western Indonesia Time): GMT+7 - Jakarta, Sumatra, West/Central Java
-   **WITA** (Central Indonesia Time): GMT+8 - Kalimantan, Sulawesi, Bali
-   **WIT** (Eastern Indonesia Time): GMT+9 - Papua, Maluku

**Current Setting**: `Asia/Jakarta` (WIB - GMT+7)

### Time Difference

-   **UTC**: +7 hours
-   **Singapore**: Same (SGT also GMT+7)
-   **Tokyo**: -2 hours
-   **London**: +7 hours
-   **New York**: +12 hours

## 🔧 Usage Examples

### In Blade Views

```php
<!-- Using helper functions -->
{{ format_datetime($user->created_at) }}
{{ format_date($order->order_date) }}
{{ format_datetime_human($notification->created_at) }}

<!-- Manual timezone conversion -->
{{ $user->created_at->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}
```

### In Controllers

```php
use Carbon\Carbon;

// Get current time in Jakarta
$now = now_jakarta();

// Format datetime
$formatted = format_datetime($user->last_login_at);

// Parse and convert
$date = Carbon::parse($input)->timezone('Asia/Jakarta');

// Compare times
if (now_jakarta()->gt($deadline)) {
    // Deadline passed
}
```

### In Models

```php
// Cast to Carbon automatically uses app timezone
protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];

// Access with timezone
$user->created_at->format('d M Y H:i:s'); // Already in Asia/Jakarta
```

### In Database Queries

```php
// Laravel automatically converts to app timezone
User::whereDate('created_at', today_jakarta())->get();

// Manual timezone conversion
User::where('created_at', '>=', now_jakarta()->subDays(7))->get();
```

## ✨ Benefits

### 1. **Accuracy** ✅

-   All timestamps now show **exact WIB time**
-   No more confusion between UTC and local time
-   Consistent across entire application

### 2. **User Experience** ✅

-   Users see familiar local time
-   No mental conversion needed
-   Professional and accurate

### 3. **Development** ✅

-   Helper functions make formatting easy
-   Consistent datetime handling
-   Less code duplication

### 4. **Maintenance** ✅

-   Centralized timezone configuration
-   Easy to change via environment variable
-   Well documented

## 🔒 Database Storage

**Important**: Laravel stores all timestamps in database as **UTC**, but displays them in **Asia/Jakarta** timezone.

-   **Storage**: UTC (database)
-   **Display**: Asia/Jakarta (application)
-   **Conversion**: Automatic (handled by Laravel)

This is the **best practice** because:

1. Database remains timezone-independent
2. Easy to support multiple timezones in future
3. Daylight saving time changes don't affect stored data

## 🚀 Verification

### Test Timezone

```bash
php artisan tinker
```

```php
// Check app timezone
config('app.timezone')  // Asia/Jakarta

// Current time
now()                   // Carbon instance in Asia/Jakarta
now()->format('Y-m-d H:i:s T')  // 2025-11-22 14:30:45 WIB

// Convert UTC to Jakarta
Carbon::parse('2025-11-22 07:30:45', 'UTC')
    ->timezone('Asia/Jakarta')
    ->format('Y-m-d H:i:s')  // 2025-11-22 14:30:45
```

## 📝 Notes

### For Future Developers

1. **Always use** `now()` or `now_jakarta()` for current time
2. **Don't hardcode** timezones in views - use helper functions
3. **Database migrations** use UTC by default (keep it that way)
4. **API responses** might need explicit timezone conversion
5. **Unit tests** should account for timezone

### Common Pitfalls to Avoid

❌ Don't use `date('Y-m-d H:i:s')` - it uses server timezone
❌ Don't forget `->timezone()` in Filament columns
❌ Don't store timezone in datetime column - use app config
✅ Use `now()` or Carbon methods
✅ Use helper functions for formatting
✅ Set timezone explicitly in Filament columns

---

**Status**: ✅ All datetime displays are now accurate with Asia/Jakarta (WIB) timezone
**Language**: English (as requested)
**Format**: Professional and consistent
**Last Updated**: 2025-11-22 14:15:23 WIB
