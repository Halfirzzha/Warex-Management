<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================================
 * SECURITY HEADERS MIDDLEWARE
 * Add professional security headers to all responses
 * ============================================================================
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP)
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Filament needs unsafe-eval
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ]));

        // Strict Transport Security (HTTPS only in production)
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Permissions Policy
        $response->headers->set('Permissions-Policy', implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
        ]));

        // Log suspicious activities
        $this->logSuspiciousActivity($request);

        return $response;
    }

    /**
     * Log suspicious activities
     */
    private function logSuspiciousActivity(Request $request): void
    {
        // Log requests with suspicious patterns
        $suspiciousPatterns = [
            'script',
            'SELECT',
            'UNION',
            'DROP',
            'INSERT',
            '../',
            '<?php',
            '<script',
        ];

        $queryString = $request->getQueryString();
        if ($queryString) {
            foreach ($suspiciousPatterns as $pattern) {
                if (stripos($queryString, $pattern) !== false) {
                    Log::warning('Suspicious request pattern detected', [
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl(),
                        'user_agent' => $request->userAgent(),
                        'pattern' => $pattern,
                    ]);
                    break;
                }
            }
        }

        // Log multiple failed attempts from same IP
        $failedKey = 'security:failed_requests:'.$request->ip();
        $failedCount = \Illuminate\Support\Facades\Cache::get($failedKey, 0);

        if ($failedCount > 10) {
            Log::alert('Multiple suspicious requests from IP', [
                'ip' => $request->ip(),
                'count' => $failedCount,
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}
