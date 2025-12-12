<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BlockSuspiciousIps
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Check if IP is blocked
        if ($this->isBlocked($ip)) {
            return response()->json([
                'message' => 'Your IP has been temporarily blocked due to suspicious activity.',
                'contact' => 'Please contact support if you believe this is an error.',
            ], 403);
        }

        // Track failed attempts
        $this->trackAttempt($request);

        return $next($request);
    }

    /**
     * Check if IP is blocked
     */
    private function isBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }

    /**
     * Track suspicious activity
     */
    private function trackAttempt(Request $request)
    {
        $ip = $request->ip();
        $key = "suspicious_attempts:{$ip}";

        $attempts = Cache::get($key, 0);

        // If too many attempts, block IP for 1 hour
        if ($attempts > 100) { // 100 requests in 10 minutes
            Cache::put("blocked_ip:{$ip}", true, now()->addHour());
            Cache::forget($key);

            \Log::warning("IP blocked due to suspicious activity", [
                'ip' => $ip,
                'attempts' => $attempts,
            ]);
        } else {
            Cache::put($key, $attempts + 1, now()->addMinutes(10));
        }
    }
}
