<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitController extends Controller
{
    /**
     * Get rate limit statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'login_attempts' => [
                'key' => 'login',
                'description' => 'Login attempts',
                'limit' => '5 per 5 minutes',
            ],
            'registration_attempts' => [
                'key' => 'register',
                'description' => 'Registration attempts',
                'limit' => '3 per hour',
            ],
            'complaint_creation' => [
                'key' => 'create-complaint',
                'description' => 'Complaint creation',
                'limit' => '5 per hour, 20 per day',
            ],
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get blocked IPs
     */
    public function blockedIps(): JsonResponse
    {
        // This is a simplified version
        // In production, you'd want to store blocked IPs in database

        $blockedIps = []; // Retrieve from Cache or Database

        return response()->json([
            'data' => $blockedIps,
            'total' => count($blockedIps),
        ]);
    }

    /**
     * Unblock IP
     */
    public function unblockIp(string $ip): JsonResponse
    {
        Cache::forget("blocked_ip:{$ip}");
        Cache::forget("suspicious_attempts:{$ip}");

        return response()->json([
            'message' => "IP {$ip} has been unblocked",
        ]);
    }
}
