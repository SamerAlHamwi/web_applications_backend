<?php
// app/Http/Controllers/Auth/VerifyEmailController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\AuthService;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private UserRepository $userRepository
    ) {}

    /**
     * Verify email with 6-digit code
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        try {
            // Find user by email
            $user = $this->userRepository->findByEmail($request->email);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            // Verify the code
            $result = $this->authService->verifyEmail($user->id, $request->code);

            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'user' => $result['user'],
                    'tokens' => $result['tokens'],
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Email verification failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Resend verification code
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email', 'exists:users,email']
            ]);

            $user = $this->userRepository->findByEmail($request->email);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            $this->authService->resendVerificationCode($user);

            return response()->json([
                'message' => 'Verification code sent successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resend verification code',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
