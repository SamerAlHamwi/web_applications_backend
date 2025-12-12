<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    // app/Providers/RateLimitServiceProvider.php

    public function boot(): void
    {
        // ============================================
        // AUTHENTICATION RATE LIMITS
        // ============================================

        // Registration - INDUSTRY STANDARD APPROACH
        RateLimiter::for('register', function (Request $request) {
            $email = (string) $request->input('email', '');

            return [
                // ✅ Per IP: Allow up to 10 registrations per hour
                // (Allows multiple legitimate users from same location)
                Limit::perHour(10)->by($request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many registration attempts from this location. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                }),

                // ✅ Per Email: Prevent duplicate/spam email attempts
                // If same email tries multiple times, block it
                Limit::perHour(3)->by($email)->response(function () {
                    return response()->json([
                        'message' => 'This email has been used too many times. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                }),
            ];
        });

        // Login - Already good, but let's optimize
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');

            return [
                // Per email: Prevent brute force on specific account
                Limit::perMinutes(5, 5)->by($email)->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts for this account. Please try again in 5 minutes.',
                        'retry_after' => 300,
                    ], 429);
                }),

                // Per IP: Prevent distributed brute force
                Limit::perMinutes(5, 20)->by($request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts from this location. Please try again later.',
                        'retry_after' => 300,
                    ], 429);
                }),
            ];
        });

        // Email verification resend
        RateLimiter::for('email-verification', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many verification requests. Please wait before requesting another.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // ============================================
        // COMPLAINT RATE LIMITS
        // ============================================

        // Create complaint - Allow reasonable usage
        RateLimiter::for('create-complaint', function (Request $request) {
            return [
                // Per user: 10 complaints per hour (increased from 5)
                Limit::perHour(10)->by($request->user()->id)->response(function () {
                    return response()->json([
                        'message' => 'You have reached the hourly complaint limit. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                }),

                // Per user: 30 complaints per day (increased from 20)
                Limit::perDay(30)->by($request->user()->id)->response(function () {
                    return response()->json([
                        'message' => 'You have reached the daily complaint limit. Please try again tomorrow.',
                        'retry_after' => 86400,
                    ], 429);
                }),
            ];
        });

        // Update complaint - Reasonable limit
        RateLimiter::for('update-complaint', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()->id)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many update attempts. Please slow down.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // File upload - Prevent abuse
        RateLimiter::for('file-upload', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()->id)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many file operations. Please wait before trying again.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // ============================================
        // EMPLOYEE RATE LIMITS
        // ============================================

        RateLimiter::for('employee-actions', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()->id)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many actions performed. Please slow down.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // ============================================
        // ADMIN RATE LIMITS (Higher)
        // ============================================

        RateLimiter::for('admin-api', function (Request $request) {
            return Limit::perMinute(200)->by($request->user()->id);
        });

        // ============================================
        // GENERAL API RATE LIMIT
        // ============================================

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Public tracking (no auth required)
        RateLimiter::for('public-track', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many tracking requests. Please try again later.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // FCM token registration
        RateLimiter::for('fcm-register', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        // Password reset
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)->by($request->input('email', ''))
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many password reset attempts. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                });
        });
    }
}
