<?php

namespace App\Http\Middleware;

use App\Settings\MaintenanceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip maintenance check for admin panel routes (Filament)
        if ($request->is('warehouse') || $request->is('warehouse/*')) {
            return $next($request);
        }

        try {
            $settings = app(MaintenanceSettings::class);

            // Skip maintenance check for allowed IPs
            if (in_array($request->ip(), $settings->allowed_ips ?? [])) {
                return $next($request);
            }

            // Show maintenance page if enabled
            if ($settings->enabled) {
                return response()->view('maintenance', [], 503);
            }
        } catch (\Exception $e) {
            // If settings not configured yet, continue normally
            return $next($request);
        }

        return $next($request);
    }
}
