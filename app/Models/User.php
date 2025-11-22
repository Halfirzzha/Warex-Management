<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Account Status Constants
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_BLOCKED = 'blocked';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_TERMINATED = 'terminated';

    /**
     * Created By Type Constants
     */
    const CREATED_BY_SYSTEM = 'system';

    const CREATED_BY_ADMIN = 'admin';

    /**
     * Security Constants
     */
    const MAX_FAILED_LOGIN_ATTEMPTS = 3;

    const FAILED_ATTEMPTS_RESET_MINUTES = 30;

    const AUTO_UNLOCK_HOURS = 24;

    const SESSION_TIMEOUT_MINUTES = 120;

    const PASSWORD_CHANGE_LOG_DAYS = 90;

    /**
     * Override password reset notification URL
     * Redirect to Filament password reset page instead of Laravel default
     */
    public function sendPasswordResetNotification($token): void
    {
        // Get Filament password reset URL
        $url = \Filament\Facades\Filament::getPanel('warehouse')
            ->getPasswordResetUrl($token, $this);

        // Send notification with custom URL
        $notification = new ResetPasswordNotification($token);

        // Override the toMail method to use Filament URL
        ResetPasswordNotification::createUrlUsing(function ($notifiable, $token) {
            return \Filament\Facades\Filament::getPanel('warehouse')
                ->getPasswordResetUrl($token, $notifiable);
        });

        $this->notify($notification);

        // Log untuk security monitoring
        Log::info('Password reset notification sent', [
            'user_id' => $this->id,
            'email' => $this->email,
            'ip' => request()->ip(),
            'token_hash' => hash('sha256', $token),
        ]);
    }

    /**
     * Redis Cache Keys
     */
    const CACHE_PREFIX = 'user:';

    const CACHE_FAILED_ATTEMPTS = 'failed_attempts:';

    const CACHE_BLOCKED_STATUS = 'blocked:';

    const CACHE_LAST_LOGIN = 'last_login:';

    const CACHE_LOGIN_HISTORY = 'login_history:';

    const CACHE_ACTIVE_SESSIONS = 'active_sessions:';

    const CACHE_PASSWORD_CHANGES = 'password_changes:';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'avatar',
        'name',
        'username',
        'position',
        'email',
        'password',
        'email_verified_at',
        'password_changed_at',
        'password_changed_by',
        'first_login_at',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_at',
        'account_status',
        'blocked_reason',
        'blocked_by',
        'blocked_until',
        'created_by_type',
        'created_by_admin_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_at' => 'datetime',
            'blocked_until' => 'datetime',
            'failed_login_attempts' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot method - Auto-generate UUID, sync Redis, intelligent monitoring
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID on creation (UPPERCASE for professional display)
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = strtoupper((string) Str::uuid());
            }

            // Auto-set created_by_type if not set
            if (empty($user->created_by_type)) {
                $user->created_by_type = Auth::check() ? self::CREATED_BY_ADMIN : self::CREATED_BY_SYSTEM;
            }

            // Auto-set created_by_admin_id if created by admin
            if ($user->created_by_type === self::CREATED_BY_ADMIN && Auth::check()) {
                $user->created_by_admin_id = Auth::id();
            }

            // Log creation to Redis
            self::logToRedis('user_created', [
                'uuid' => $user->uuid,
                'email' => $user->email,
                'created_by' => $user->created_by_type,
                'timestamp' => now()->toIso8601String(),
                'ip' => request()->ip(),
            ]);
        });

        // Sync to Redis on update
        static::updated(function ($user) {
            $user->syncToRedis();

            // Detect critical changes
            $changes = $user->getDirty();
            if (isset($changes['account_status']) || isset($changes['blocked_reason'])) {
                self::logToRedis('user_status_changed', [
                    'user_id' => $user->id,
                    'old_status' => $user->getOriginal('account_status'),
                    'new_status' => $user->account_status,
                    'reason' => $user->blocked_reason,
                    'changed_by' => Auth::id() ?? 'system',
                    'timestamp' => now()->toIso8601String(),
                    'ip' => request()->ip(),
                ]);
            }
        });

        // Clean Redis cache on delete
        static::deleted(function ($user) {
            $user->clearRedisCache();
        });
    }

    /**
     * ============================================================================
     * RELATIONSHIPS
     * ============================================================================
     */
    public function passwordChanger()
    {
        return $this->belongsTo(User::class, 'password_changed_by');
    }

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * ============================================================================
     * SCOPES
     * ============================================================================
     */
    public function scopeActive($query)
    {
        return $query->where('account_status', self::STATUS_ACTIVE);
    }

    public function scopeBlocked($query)
    {
        return $query->where('account_status', self::STATUS_BLOCKED);
    }

    public function scopeLocked($query)
    {
        return $query->whereIn('account_status', [
            self::STATUS_BLOCKED,
            self::STATUS_SUSPENDED,
            self::STATUS_TERMINATED,
        ]);
    }

    public function scopeRecentlyActive($query, $minutes = 15)
    {
        return $query->where('last_login_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * ============================================================================
     * REDIS CACHE MANAGEMENT - INTELLIGENT CACHING SYSTEM
     * ============================================================================
     */

    /**
     * Sync all user data to Redis for fast access
     */
    public function syncToRedis(): void
    {
        $cacheKey = self::CACHE_PREFIX.$this->id;

        Cache::put($cacheKey, [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'account_status' => $this->account_status,
            'failed_login_attempts' => $this->failed_login_attempts,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'last_login_ip' => $this->last_login_ip,
            'locked_at' => $this->locked_at?->toIso8601String(),
            'blocked_reason' => $this->blocked_reason,
            'synced_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }

    /**
     * Get user data from Redis cache (ultra-fast)
     */
    public function getFromCache(): ?array
    {
        return Cache::get(self::CACHE_PREFIX.$this->id);
    }

    /**
     * Clear all Redis cache for this user
     */
    public function clearRedisCache(): void
    {
        $keys = [
            self::CACHE_PREFIX.$this->id,
            self::CACHE_PREFIX.self::CACHE_FAILED_ATTEMPTS.$this->id,
            self::CACHE_PREFIX.self::CACHE_BLOCKED_STATUS.$this->id,
            self::CACHE_PREFIX.self::CACHE_LAST_LOGIN.$this->id,
            self::CACHE_PREFIX.self::CACHE_LOGIN_HISTORY.$this->id,
            self::CACHE_PREFIX.self::CACHE_ACTIVE_SESSIONS.$this->id,
            self::CACHE_PREFIX.self::CACHE_PASSWORD_CHANGES.$this->id,
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Log activity to Redis for real-time monitoring
     */
    protected static function logToRedis(string $event, array $data): void
    {
        $key = 'warehouse:audit_log:'.date('Y-m-d');
        $logEntry = json_encode(array_merge(['event' => $event], $data));

        Redis::connection('cache')->rpush($key, $logEntry);
        Redis::connection('cache')->expire($key, 86400 * 90); // Keep for 90 days
    }

    /**
     * ============================================================================
     * STATUS CHECK METHODS
     * ============================================================================
     */
    public function isActive(): bool
    {
        // Check cache first for ultra-fast response
        $cached = $this->getFromCache();
        if ($cached && isset($cached['account_status'])) {
            return $cached['account_status'] === self::STATUS_ACTIVE;
        }

        return $this->account_status === self::STATUS_ACTIVE;
    }

    public function isBlocked(): bool
    {
        $cacheKey = self::CACHE_PREFIX.self::CACHE_BLOCKED_STATUS.$this->id;

        return Cache::remember($cacheKey, now()->addHours(1), function () {
            return $this->account_status === self::STATUS_BLOCKED;
        });
    }

    public function isLocked(): bool
    {
        // Auto-unlock if temporary block expired
        if ($this->blocked_until && now()->gt($this->blocked_until)) {
            $this->autoUnlock('Temporary block period expired');

            return false;
        }

        return $this->account_status !== self::STATUS_ACTIVE;
    }

    /**
     * ============================================================================
     * INTELLIGENT FAILED LOGIN MANAGEMENT
     * Auto-block after 3 failed attempts with detailed tracking
     * ============================================================================
     */

    /**
     * Increment failed login attempts with intelligent analysis
     * Will auto-block account after 3 failed attempts
     */
    public function incrementFailedLoginAttempts(): void
    {
        // Increment in database
        $this->failed_login_attempts = ($this->failed_login_attempts ?? 0) + 1;
        $this->save();

        $cacheKey = self::CACHE_PREFIX.self::CACHE_FAILED_ATTEMPTS.$this->id;
        $attempts = $this->failed_login_attempts;

        // Cache with detailed tracking
        Cache::put($cacheKey, [
            'count' => $attempts,
            'last_attempt_at' => now()->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], now()->addMinutes(self::FAILED_ATTEMPTS_RESET_MINUTES));

        // Log suspicious activity
        self::logToRedis('failed_login_attempt', [
            'user_id' => $this->id,
            'email' => $this->email,
            'attempt_number' => $attempts,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // AUTO-BLOCK: Intelligent auto-block at 3 attempts
        if ($attempts >= self::MAX_FAILED_LOGIN_ATTEMPTS) {
            $this->intelligentAutoBlock();
        }
        // WARNING: At 2 attempts
        elseif ($attempts == 2) {
            Log::warning("User {$this->email} has 2 failed login attempts. One more will trigger auto-block.", [
                'user_id' => $this->id,
                'ip' => request()->ip(),
            ]);
        }

        $this->syncToRedis();
    }

    /**
     * Intelligent auto-block with detailed analysis
     */
    protected function intelligentAutoBlock(): void
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        $timestamp = now();

        $reason = sprintf(
            "Akun diblokir otomatis karena %d kali percobaan login gagal.\nIP: %s\nWaktu: %s\nUser Agent: %s",
            self::MAX_FAILED_LOGIN_ATTEMPTS,
            $ip,
            $timestamp->format('d M Y H:i:s'),
            $userAgent
        );

        $this->blockAccount($reason, null, self::STATUS_BLOCKED, now()->addHours(self::AUTO_UNLOCK_HOURS));

        // Alert to admin cache
        $alertKey = 'warehouse:security_alerts:'.date('Y-m-d');
        Redis::connection('cache')->rpush($alertKey, json_encode([
            'type' => 'auto_block',
            'user_id' => $this->id,
            'email' => $this->email,
            'ip' => $ip,
            'timestamp' => $timestamp->toIso8601String(),
            'reason' => 'Multiple failed login attempts',
        ]));
        Redis::connection('cache')->expire($alertKey, 86400 * 7);

        Log::critical("User auto-blocked: {$this->email}", [
            'user_id' => $this->id,
            'ip' => $ip,
            'failed_attempts' => $this->failed_login_attempts,
        ]);
    }

    /**
     * Reset failed login attempts with cleanup
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        Cache::forget(self::CACHE_PREFIX.self::CACHE_FAILED_ATTEMPTS.$this->id);

        self::logToRedis('failed_attempts_reset', [
            'user_id' => $this->id,
            'email' => $this->email,
            'reset_by' => Auth::id() ?? 'system',
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
        ]);

        $this->syncToRedis();
    }

    /**
     * ============================================================================
     * ADVANCED BLOCKING SYSTEM
     * ============================================================================
     */

    /**
     * Block account with comprehensive logging
     */
    public function blockAccount(
        string $reason,
        ?int $blockedBy = null,
        ?string $status = self::STATUS_BLOCKED,
        ?Carbon $blockedUntil = null
    ): void {
        $this->update([
            'account_status' => $status,
            'blocked_reason' => $reason,
            'blocked_by' => $blockedBy,
            'locked_at' => now(),
            'blocked_until' => $blockedUntil,
        ]);

        // Cache blocked status
        $cacheKey = self::CACHE_PREFIX.self::CACHE_BLOCKED_STATUS.$this->id;
        Cache::put($cacheKey, [
            'is_blocked' => true,
            'status' => $status,
            'reason' => $reason,
            'blocked_at' => now()->toIso8601String(),
            'blocked_until' => $blockedUntil?->toIso8601String(),
            'blocked_by' => $blockedBy,
        ], now()->addDays(30));

        self::logToRedis('user_blocked', [
            'user_id' => $this->id,
            'email' => $this->email,
            'status' => $status,
            'reason' => $reason,
            'blocked_by' => $blockedBy ?? 'system',
            'blocked_until' => $blockedUntil?->toIso8601String(),
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
        ]);

        $this->syncToRedis();

        Log::warning("User blocked: {$this->email}", [
            'user_id' => $this->id,
            'status' => $status,
            'reason' => $reason,
            'blocked_by' => $blockedBy,
        ]);
    }

    /**
     * Unblock account (admin only)
     */
    public function unblockAccount(?int $unlockedBy = null): void
    {
        $previousStatus = $this->account_status;
        $previousReason = $this->blocked_reason;

        $this->update([
            'account_status' => self::STATUS_ACTIVE,
            'blocked_reason' => null,
            'blocked_by' => null,
            'locked_at' => null,
            'failed_login_attempts' => 0,
            'blocked_until' => null,
        ]);

        // Clear all related cache
        $this->clearRedisCache();

        self::logToRedis('user_unblocked', [
            'user_id' => $this->id,
            'email' => $this->email,
            'previous_status' => $previousStatus,
            'previous_reason' => $previousReason,
            'unlocked_by' => $unlockedBy ?? Auth::id() ?? 'system',
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
        ]);

        $this->syncToRedis();

        Log::info("User unblocked: {$this->email}", [
            'user_id' => $this->id,
            'unlocked_by' => $unlockedBy,
            'previous_status' => $previousStatus,
        ]);
    }

    /**
     * Auto-unlock for temporary blocks
     */
    protected function autoUnlock(string $reason): void
    {
        $this->update([
            'account_status' => self::STATUS_ACTIVE,
            'blocked_reason' => null,
            'blocked_by' => null,
            'locked_at' => null,
            'failed_login_attempts' => 0,
            'blocked_until' => null,
        ]);

        $this->clearRedisCache();

        self::logToRedis('user_auto_unlocked', [
            'user_id' => $this->id,
            'email' => $this->email,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->syncToRedis();
    }

    /**
     * ============================================================================
     * ADVANCED LOGIN TRACKING & ANALYTICS
     * ============================================================================
     */

    /**
     * Record successful login with comprehensive tracking
     */
    public function recordLogin(string $ipAddress): void
    {
        $isFirstLogin = is_null($this->first_login_at);
        $userAgent = request()->userAgent();

        $this->update([
            'first_login_at' => $isFirstLogin ? now() : $this->first_login_at,
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
            'failed_login_attempts' => 0,
        ]);

        // Cache last login for quick access
        $loginCacheKey = self::CACHE_PREFIX.self::CACHE_LAST_LOGIN.$this->id;
        Cache::put($loginCacheKey, [
            'timestamp' => now()->toIso8601String(),
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'is_first_login' => $isFirstLogin,
        ], now()->addDays(7));

        // Store login history in Redis (keep last 50 logins)
        $historyKey = self::CACHE_PREFIX.self::CACHE_LOGIN_HISTORY.$this->id;
        $loginData = json_encode([
            'timestamp' => now()->toIso8601String(),
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'is_first_login' => $isFirstLogin,
        ]);

        Redis::connection('cache')->lpush($historyKey, $loginData);
        Redis::connection('cache')->ltrim($historyKey, 0, 49); // Keep only 50 latest
        Redis::connection('cache')->expire($historyKey, 86400 * 90); // 90 days

        // Track active session
        $this->trackActiveSession();

        // Log successful login
        self::logToRedis('user_login', [
            'user_id' => $this->id,
            'email' => $this->email,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'is_first_login' => $isFirstLogin,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Clear failed attempts cache
        Cache::forget(self::CACHE_PREFIX.self::CACHE_FAILED_ATTEMPTS.$this->id);

        $this->syncToRedis();

        if ($isFirstLogin) {
            Log::info("First login for user: {$this->email}", [
                'user_id' => $this->id,
                'ip' => $ipAddress,
            ]);
        }
    }

    /**
     * Track active session in Redis
     */
    protected function trackActiveSession(): void
    {
        $sessionKey = self::CACHE_PREFIX.self::CACHE_ACTIVE_SESSIONS.$this->id;
        $sessionId = session()->getId();

        $sessionData = [
            'session_id' => $sessionId,
            'started_at' => now()->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        Cache::put($sessionKey.':'.$sessionId, $sessionData, now()->addMinutes(self::SESSION_TIMEOUT_MINUTES));
    }

    /**
     * Get login history from Redis
     */
    public function getLoginHistory(int $limit = 10): array
    {
        $historyKey = self::CACHE_PREFIX.self::CACHE_LOGIN_HISTORY.$this->id;
        $history = Redis::connection('cache')->lrange($historyKey, 0, $limit - 1);

        return array_map(function ($item) {
            return json_decode($item, true);
        }, $history);
    }

    /**
     * ============================================================================
     * PASSWORD MANAGEMENT
     * ============================================================================
     */

    /**
     * Record password change with comprehensive tracking
     */
    public function recordPasswordChange(?int $changedBy = null): void
    {
        $this->update([
            'password_changed_at' => now(),
            'password_changed_by' => $changedBy,
        ]);

        // Cache password change history
        $changeKey = self::CACHE_PREFIX.self::CACHE_PASSWORD_CHANGES.$this->id;
        $changeData = json_encode([
            'changed_at' => now()->toIso8601String(),
            'changed_by' => $changedBy ?? Auth::id() ?? 'system',
            'ip' => request()->ip(),
        ]);

        Redis::connection('cache')->lpush($changeKey, $changeData);
        Redis::connection('cache')->ltrim($changeKey, 0, 19); // Keep last 20 changes
        Redis::connection('cache')->expire($changeKey, 86400 * self::PASSWORD_CHANGE_LOG_DAYS);

        self::logToRedis('password_changed', [
            'user_id' => $this->id,
            'email' => $this->email,
            'changed_by' => $changedBy ?? Auth::id() ?? 'system',
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
        ]);

        $this->syncToRedis();

        Log::info("Password changed for user: {$this->email}", [
            'user_id' => $this->id,
            'changed_by' => $changedBy,
        ]);
    }

    /**
     * Get password change history from Redis
     */
    public function getPasswordChangeHistory(int $limit = 10): array
    {
        $changeKey = self::CACHE_PREFIX.self::CACHE_PASSWORD_CHANGES.$this->id;
        $history = Redis::connection('cache')->lrange($changeKey, 0, $limit - 1);

        return array_map(function ($item) {
            return json_decode($item, true);
        }, $history);
    }

    /**
     * ============================================================================
     * UI HELPERS
     * ============================================================================
     */
    public function getStatusLabel(): string
    {
        return match ($this->account_status) {
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_BLOCKED => 'Diblokir Sistem',
            self::STATUS_SUSPENDED => 'Dibekukan Admin',
            self::STATUS_TERMINATED => 'Dihentikan Admin',
            default => 'Unknown',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->account_status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_BLOCKED => 'warning',
            self::STATUS_SUSPENDED => 'danger',
            self::STATUS_TERMINATED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * ============================================================================
     * ANALYTICS & INTELLIGENCE
     * ============================================================================
     */

    /**
     * Get user activity score (0-100)
     */
    public function getActivityScore(): int
    {
        $score = 0;

        // Login frequency
        if ($this->last_login_at) {
            $daysSinceLogin = now()->diffInDays($this->last_login_at);
            $score += $daysSinceLogin <= 1 ? 30 : ($daysSinceLogin <= 7 ? 20 : 10);
        }

        // Account age
        if ($this->created_at) {
            $accountAgeDays = now()->diffInDays($this->created_at);
            $score += $accountAgeDays > 30 ? 20 : 10;
        }

        // Security health
        if ($this->failed_login_attempts == 0) {
            $score += 25;
        }

        // Active status
        if ($this->isActive()) {
            $score += 25;
        }

        return min(100, $score);
    }

    /**
     * Check if user is suspicious
     */
    public function isSuspicious(): bool
    {
        $suspicionScore = 0;

        // Multiple failed attempts
        if ($this->failed_login_attempts >= 2) {
            $suspicionScore += 30;
        }

        // Recent logins from different IPs
        $loginHistory = $this->getLoginHistory(5);
        $uniqueIps = array_unique(array_column($loginHistory, 'ip'));
        if (count($uniqueIps) > 3) {
            $suspicionScore += 20;
        }

        // Never logged in but account old
        if (is_null($this->first_login_at) && $this->created_at->diffInDays(now()) > 30) {
            $suspicionScore += 25;
        }

        return $suspicionScore >= 50;
    }
}
