<?php
// app/Http/Controllers/Auth/VerifyEmailController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerifyEmailController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Verify email with 6-digit code
     * This creates the user account
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'code' => ['required', 'string', 'size:6'],
            ]);

            $result = $this->authService->verifyEmail(
                $request->email,
                $request->code
            );

            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'user' => $result['user'],
                    'tokens' => $result['tokens'],
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Verification failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Verification failed',
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
                'email' => ['required', 'email']
            ]);

            $this->authService->resendVerificationCode($request->email);

            return response()->json([
                'message' => 'Verification code sent successfully'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Failed to resend verification code',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resend verification code',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
