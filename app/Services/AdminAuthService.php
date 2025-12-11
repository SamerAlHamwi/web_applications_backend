<?php
// app/Services/AdminAuthService.php
namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\RefreshTokenRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AdminAuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private RefreshTokenRepository $refreshTokenRepository
    ) {}

    /**
     * Admin login with JWT (NOT session)
     */
    public function login(array $credentials): array
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($credentials['email']);

        // Validate credentials
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        // 🔴 Check if user is admin
        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'email' => ['Access denied. Admin privileges required.']
            ]);
        }

        // 🔴 Revoke all existing tokens (single active session)
        $this->refreshTokenRepository->revokeAllForUser($user->id);

        // 🔴 Generate JWT tokens (same as regular users)
        $tokens = $this->generateTokensForUser($user);

        return [
            'user' => $this->formatUserData($user),
            'tokens' => $tokens,
        ];
    }

    /**
     * Logout admin (revoke tokens)
     */
    public function logout(int $userId): void
    {
        // Invalidate current access token (blacklist)
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token might already be invalid
        }

        // Revoke all refresh tokens
        $this->refreshTokenRepository->revokeAllForUser($userId);
    }

    /**
     * Get authenticated admin from JWT
     */
    public function getAuthenticatedAdmin(): ?User
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Verify user is admin
            if ($user && $user->role === 'admin') {
                return $user;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if authenticated user is admin
     */
    public function check(): bool
    {
        $user = $this->getAuthenticatedAdmin();
        return $user !== null;
    }

    /**
     * Generate JWT tokens for admin
     */
    private function generateTokensForUser(User $user): array
    {
        // Generate access token (JWT)
        $accessToken = JWTAuth::fromUser($user);

        // Generate refresh token
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // in seconds
        ];
    }

    /**
     * Create refresh token
     */
    private function createRefreshToken(User $user)
    {
        return $this->refreshTokenRepository->create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addMinutes(config('jwt.refresh_ttl')),
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
        ];
    }
}
