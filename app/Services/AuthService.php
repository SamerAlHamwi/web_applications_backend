<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\User;
use App\Models\EmailVerification;
use App\Repositories\UserRepository;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\EmailVerificationRepository;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private RefreshTokenRepository $refreshTokenRepository,
        private EmailVerificationRepository $emailVerificationRepository
    ) {}

    /**
     * Register a new user and send verification code
     */
    public function register(array $data): array
    {
        if ($this->userRepository->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered.']
            ]);
        }

        $data['password'] = Hash::make($data['password']);
        unset($data['password_confirmation']);
        $data['role'] = $data['role'] ?? 'citizen';

        // Create user (email_verified_at will be null)
        $user = $this->userRepository->create($data);

        // Generate and send verification code
        $this->sendVerificationCode($user);

        return [
            'user' => $this->formatUserData($user),
            'message' => 'Registration successful! A 6-digit verification code has been sent to your email.',
        ];
    }

    /**
     * Send verification code to user's email
     */
    public function sendVerificationCode(User $user): EmailVerification
    {
        // Delete old unused codes for this user
        $this->emailVerificationRepository->deleteOldCodesForUser($user->id);

        // Generate new verification code
        $code = EmailVerification::generateCode();

        // Store verification code
        $verification = $this->emailVerificationRepository->create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(
                config('auth.verification.expire', 60)
            ),
        ]);

        // Send email
        Mail::to($user->email)->send(new VerifyEmailMail($user, $verification));

        return $verification;
    }

    /**
     * Verify email with code
     */
    public function verifyEmail(int $userId, string $code): array
    {
        // Find user
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not found.']
            ]);
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email is already verified.']
            ]);
        }

        // Find verification code
        $verification = $this->emailVerificationRepository->findByUserAndCode($userId, $code);

        if (!$verification) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.']
            ]);
        }

        // Mark code as used
        $this->emailVerificationRepository->markAsUsed($verification);

        // Mark email as verified
        $user->markEmailAsVerified();

        // Generate tokens now that user is verified
        $tokens = $this->generateTokensForUser($user);

        return [
            'message' => 'Email verified successfully!',
            'user' => $this->formatUserData($user),
            'tokens' => $tokens,
        ];
    }

    /**
     * Resend verification code
     */
    public function resendVerificationCode(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email is already verified.']
            ]);
        }

        // Check rate limiting (prevent spam)
        $recentCodesCount = $this->emailVerificationRepository->countUnusedCodesForUser($user->id);

        if ($recentCodesCount >= 3) {
            throw ValidationException::withMessages([
                'code' => ['Too many verification attempts. Please try again later.']
            ]);
        }

        $this->sendVerificationCode($user);
    }

    /**
     * Login user with credentials
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        // Check if email is verified (optional - uncomment to enforce)
        // if (!$user->hasVerifiedEmail()) {
        //     throw ValidationException::withMessages([
        //         'email' => ['Please verify your email address first.']
        //     ]);
        // }

        $tokens = $this->generateTokensForUser($user);

        return [
            'user' => $this->formatUserData($user),
            'tokens' => $tokens,
        ];
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        $tokenRecord = $this->refreshTokenRepository->findByToken($refreshToken);

        if (!$tokenRecord || !$tokenRecord->isValid()) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired refresh token.']
            ]);
        }

        $user = $tokenRecord->user;
        $accessToken = JWTAuth::fromUser($user);

        $this->refreshTokenRepository->revoke($tokenRecord);
        $newRefreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken->token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
        ];
    }

    /**
     * Logout user (revoke tokens)
     */
    public function logout(string $refreshToken = null): void
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token might already be invalid
        }

        if ($refreshToken) {
            $tokenRecord = $this->refreshTokenRepository->findByToken($refreshToken);
            if ($tokenRecord) {
                $this->refreshTokenRepository->revoke($tokenRecord);
            }
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutFromAllDevices(int $userId): void
    {
        $this->refreshTokenRepository->revokeAllForUser($userId);
    }

    /**
     * Get authenticated user from JWT token
     */
    public function getAuthenticatedUser(): ?User
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate both access and refresh tokens for user
     */
    private function generateTokensForUser(User $user): array
    {
        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = $this->createRefreshToken($user);
        $ttl = (int) config('jwt.ttl', 60);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'bearer',
            'expires_in' => $ttl * 60,
        ];
    }

    /**
     * Create a refresh token
     */
    private function createRefreshToken(User $user): \App\Models\RefreshToken
    {
        $refreshTtl = (int) config('jwt.refresh_ttl', 20160);

        return $this->refreshTokenRepository->create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addMinutes($refreshTtl),
            'device_name' => request()->header('User-Agent'),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Format user data for response
     */
    private function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
        ];
    }
}
