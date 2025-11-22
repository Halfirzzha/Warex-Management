<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * LOGIN SUCCESS LISTENER
 * Automatically track successful logins with comprehensive data
 * ============================================================================
 */
class LoginSuccessListener
{
    /**
     * Handle the event - Track successful login
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Only process for User model
        if (!$user instanceof User) {
            return;
        }

        $ipAddress = request()->ip();

        // Record login with comprehensive tracking
        $user->recordLogin($ipAddress);

        Log::info('Login Success Listener: User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $ipAddress,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
