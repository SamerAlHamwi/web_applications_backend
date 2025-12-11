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
    public function boot(): void
    {
        // ============================================
        // GLOBAL API RATE LIMIT
        // ============================================
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // ============================================
        // AUTHENTICATION RATE LIMITS (Prevent Brute Force)
        // ============================================

        // Login attempts - strict limit
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return [
                // By email: 5 attempts per 5 minutes
                Limit::perMinutes(5, 5)->by($email)->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again in 5 minutes.',
                        'retry_after' => 300, // 5 minutes in seconds
                    ], 429);
                }),

                // By IP: 10 attempts per 5 minutes
                Limit::perMinutes(5, 10)->by($request->ip()),
            ];
        });

        // Registration - prevent spam accounts
        RateLimiter::for('register', function (Request $request) {
            return [
                // By IP: 3 registrations per hour
                Limit::perHour(3)->by($request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many registration attempts. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                }),
            ];
        });

        // Email verification resend
        RateLimiter::for('email-verification', function (Request $request) {
            return Limit::perMinute(2)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many verification emails sent. Please wait before requesting another.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // ============================================
        // COMPLAINT CREATION RATE LIMITS
        // ============================================

        // Prevent complaint spam
        RateLimiter::for('create-complaint', function (Request $request) {
            return [
                // Per user: 5 complaints per hour
                Limit::perHour(5)->by($request->user()->id)->response(function () {
                    return response()->json([
                        'message' => 'You have reached the maximum number of complaints per hour. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                }),

                // Per user: 20 complaints per day
                Limit::perDay(20)->by($request->user()->id)->response(function () {
                    return response()->json([
                        'message' => 'You have reached the daily complaint limit. Please try again tomorrow.',
                        'retry_after' => 86400,
                    ], 429);
                }),
            ];
        });

        // Complaint updates
        RateLimiter::for('update-complaint', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many update attempts. Please slow down.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // File uploads - prevent storage abuse
        RateLimiter::for('file-upload', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many file uploads. Please wait before uploading more files.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // ============================================
        // EMPLOYEE RATE LIMITS
        // ============================================

        // Employee actions (accepting, finishing complaints)
        RateLimiter::for('employee-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()->id)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many actions performed. Please slow down.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        // ============================================
        // ADMIN RATE LIMITS
        // ============================================

        // Admin has higher limits
        RateLimiter::for('admin-api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()->id);
        });

        // ============================================
        // SENSITIVE OPERATIONS
        // ============================================

        // Password reset requests
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)->by($request->email)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many password reset attempts. Please try again later.',
                        'retry_after' => 3600,
                    ], 429);
                });
        });

        // FCM token registration
        RateLimiter::for('fcm-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()->id);
        });

        // ============================================
        // PUBLIC ENDPOINTS (Stricter Limits)
        // ============================================

        // Tracking complaints by tracking number (public)
        RateLimiter::for('public-track', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many tracking requests. Please try again later.',
                        'retry_after' => 60,
                    ], 429);
                });
        });
    }
}
