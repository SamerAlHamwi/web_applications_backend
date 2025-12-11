<?php
// app/Repositories/EmailVerificationRepository.php

namespace App\Repositories;

use App\Models\EmailVerification;
use Carbon\Carbon;

class EmailVerificationRepository
{
    /**
     * Create a new verification code
     */
    public function create(array $data): EmailVerification
    {
        return EmailVerification::create($data);
    }

    /**
     * Find verification by user ID and code
     */
    public function findByUserAndCode(int $userId, string $code): ?EmailVerification
    {
        return EmailVerification::where('user_id', $userId)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Find latest verification for user
     */
    public function findLatestByUser(int $userId): ?EmailVerification
    {
        return EmailVerification::where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Mark verification as used
     */
    public function markAsUsed(EmailVerification $verification): bool
    {
        return $verification->markAsUsed();
    }

    /**
     * Delete old verification codes for user
     */
    public function deleteOldCodesForUser(int $userId): int
    {
        return EmailVerification::where('user_id', $userId)
            ->where('is_used', false)
            ->delete();
    }

    /**
     * Delete expired verification codes
     */
    public function deleteExpired(): int
    {
        return EmailVerification::where('expires_at', '<', Carbon::now())->delete();
    }

    /**
     * Count unused codes for user
     */
    public function countUnusedCodesForUser(int $userId): int
    {
        return EmailVerification::where('user_id', $userId)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->count();
    }
}
