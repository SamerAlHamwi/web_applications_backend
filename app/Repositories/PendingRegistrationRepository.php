<?php
// app/Repositories/PendingRegistrationRepository.php

namespace App\Repositories;

use App\Models\PendingRegistration;
use Carbon\Carbon;

class PendingRegistrationRepository
{
    /**
     * Create a new pending registration
     */
    public function create(array $data): PendingRegistration
    {
        return PendingRegistration::create($data);
    }

    /**
     * Find by email and code
     */
    public function findByEmailAndCode(string $email, string $code): ?PendingRegistration
    {
        return PendingRegistration::where('email', $email)
            ->where('code', $code)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Find by email
     */
    public function findByEmail(string $email): ?PendingRegistration
    {
        return PendingRegistration::where('email', $email)
            ->latest()
            ->first();
    }

    /**
     * Delete by email (cleanup old pending registrations)
     */
    public function deleteByEmail(string $email): int
    {
        return PendingRegistration::where('email', $email)->delete();
    }

    /**
     * Delete expired registrations
     */
    public function deleteExpired(): int
    {
        return PendingRegistration::where('expires_at', '<', Carbon::now())->delete();
    }

    /**
     * Count pending registrations for email
     */
    public function countByEmail(string $email): int
    {
        return PendingRegistration::where('email', $email)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->count();
    }
}
