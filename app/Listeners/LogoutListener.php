<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * LOGOUT LISTENER
 * Track user logout for security monitoring
 * ============================================================================
 */
class LogoutListener
{
    /**
     * Handle the event - Track user logout
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (!$user instanceof User) {
            return;
        }

        Log::info('Logout Listener: User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
