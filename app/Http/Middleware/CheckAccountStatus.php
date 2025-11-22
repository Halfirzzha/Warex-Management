<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================================
 * CHECK ACCOUNT STATUS MIDDLEWARE
 * Prevent blocked/suspended users from accessing the system
 * ============================================================================
 */
class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Only check for User model
            if ($user instanceof User) {
                // Check if account is locked (blocked, suspended, terminated)
                if ($user->isLocked()) {
                    Auth::logout();

                    // Clear session
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()
                        ->route('filament.warehouse.auth.login')
                        ->with('error', 'Akun Anda telah diblokir. Hubungi administrator untuk informasi lebih lanjut.');
                }

                // Auto-unlock if temporary block expired
                if ($user->blocked_until && now()->gt($user->blocked_until)) {
                    $user->update([
                        'account_status' => User::STATUS_ACTIVE,
                        'blocked_reason' => null,
                        'blocked_by' => null,
                        'locked_at' => null,
                        'failed_login_attempts' => 0,
                        'blocked_until' => null,
                    ]);
                }
            }
        }

        return $next($request);
    }
}
