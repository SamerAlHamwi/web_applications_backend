<?php

// app/Http/Controllers/Auth/RefreshTokenController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class RefreshTokenController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $tokens = $this->authService->refreshToken(
                $request->input('refresh_token')
            );

            return response()->json([
                'message' => 'Token refreshed successfully',
                'data' => $tokens,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
