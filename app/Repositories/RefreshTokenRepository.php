<?php
// app/Repositories/RefreshTokenRepository.php
namespace App\Repositories;

use App\Models\RefreshToken;
use Carbon\Carbon;

class RefreshTokenRepository
{
    /**
     * Create a new refresh token
     */
    public function create(array $data): RefreshToken
    {
        return RefreshToken::create($data);
    }

    /**
     * Find refresh token by token string
     */
    public function findByToken(string $token): ?RefreshToken
    {
        return RefreshToken::where('token', $token)
            ->where('is_revoked', false)
            ->first();
    }

    /**
     * Revoke a refresh token
     */
    public function revoke(RefreshToken $refreshToken): bool
    {
        return $refreshToken->update(['is_revoked' => true]);
    }

    /**
     * Revoke all user's refresh tokens
     */
    public function revokeAllForUser(int $userId): int
    {
        return RefreshToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired(): int
    {
        return RefreshToken::where('expires_at', '<', Carbon::now())->delete();
    }

    /**
     * Get user's active refresh tokens
     */
    public function getUserActiveTokens(int $userId)
    {
        return RefreshToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->get();
    }
}
