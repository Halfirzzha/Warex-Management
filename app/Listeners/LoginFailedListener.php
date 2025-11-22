<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * LOGIN FAILED LISTENER
 * Automatically track failed login attempts and auto-block after 3 attempts
 * ============================================================================
 */
class LoginFailedListener
{
    /**
     * Handle the event - Track failed login and auto-block if needed
     */
    public function handle(Failed $event): void
    {
        // Get user by email or username
        $credentials = $event->credentials;
        $identifier = $credentials['email'] ?? $credentials['username'] ?? null;

        if (!$identifier) {
            return;
        }

        // Find user by email or username
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user) {
            Log::warning('Login Failed Listener: User not found', [
                'identifier' => $identifier,
                'ip' => request()->ip(),
            ]);
            return;
        }

        // Increment failed attempts (will auto-block at 3 attempts)
        $user->incrementFailedLoginAttempts();

        Log::warning('Login Failed Listener: Failed login attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'failed_attempts' => $user->failed_login_attempts,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
