<?php

// app/Http/Controllers/Auth/LogoutController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle logout request
     * Only needs Authorization header with access token
     */
    public function logout(): JsonResponse
    {
        // Get authenticated user from access token
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Logout (will revoke all tokens for this user)
        $this->authService->logout($user->id);

        return response()->json([
            'message' => 'Logout successful'
        ], 200);
    }

    /**
     * Logout from all devices (same as regular logout in single session mode)
     */
    public function logoutAll(): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $this->authService->logoutFromAllDevices($user->id);

        return response()->json([
            'message' => 'Logged out from all devices'
        ], 200);
    }
}
