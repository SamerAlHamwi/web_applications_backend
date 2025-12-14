<?php
// app/Repositories/UserRepository.php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Update user
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Get all users (for admin)
     */
    public function getAll(): Collection
    {
        return User::all();
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    // ============================================
    // ✅ NEW METHODS FOR ADMIN CITIZEN API
    // ============================================

    /**
     * Get all citizens with pagination
     * Used by: GET /api/admin/citizens
     */
    /**
     * Get all citizens with pagination
     */
    /**
     * Get all citizens with pagination
     */
    public function getAllCitizens(int $perPage = 15): LengthAwarePaginator
    {
        return User::where('role', 'citizen')
            // ->where('is_active', true) // ← Comment this out temporarily
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search citizens by name or email with pagination
     */
    /**
     * Search citizens by name or email with pagination
     */
    public function searchCitizens(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::where('role', 'citizen');
        // ->where('is_active', true); // ← Comment this out

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
    /**
     * Get total citizens count
     * Used by: GET /api/admin/citizens (summary)
     */
    public function getTotalCitizensCount(): int
    {
        return User::where('role', 'citizen')->count();
    }

    /**
     * Get citizen by ID with complaints count
     * Used by: GET /api/admin/citizens/{id}
     */
    public function findCitizenById(int $id): ?User
    {
        return User::where('role', 'citizen')
            ->withCount('complaints')
            ->find($id);
    }

    /**
     * Search citizens by name or email with pagination
     * Used by: GET /api/admin/citizens?search=query
     */


    /**
     * Get all employees with pagination
     * Bonus method for future admin employee management
     */
    public function getAllEmployees(int $perPage = 15): LengthAwarePaginator
    {
        return User::where('role', 'employee')
            ->with('entity')
            ->withCount('assignedComplaints')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get employees by entity
     */
    public function getEmployeesByEntity(int $entityId): Collection
    {
        return User::where('role', 'employee')
            ->where('entity_id', $entityId)
            ->withCount('assignedComplaints')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user with all relationships loaded
     */
    public function findWithRelations(int $id): ?User
    {
        return User::with(['entity', 'complaints', 'assignedComplaints'])
            ->find($id);
    }

    /**
     * Get active citizens only
     */
    public function getActiveCitizens(int $perPage = 15): LengthAwarePaginator
    {
        return User::where('role', 'citizen')
            ->where('is_active', true)
            ->withCount('complaints')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get verified citizens
     */
    public function getVerifiedCitizens(int $perPage = 15): LengthAwarePaginator
    {
        return User::where('role', 'citizen')
            ->whereNotNull('email_verified_at')
            ->withCount('complaints')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unverified citizens
     */
    public function getUnverifiedCitizens(int $perPage = 15): LengthAwarePaginator
    {
        return User::where('role', 'citizen')
            ->whereNull('email_verified_at')
            ->withCount('complaints')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get citizen statistics
     * Returns counts of total, verified, unverified, active citizens
     */
    public function getCitizenStatistics(): array
    {
        $citizens = User::where('role', 'citizen');

        return [
            'total' => $citizens->count(),
            'verified' => $citizens->clone()->whereNotNull('email_verified_at')->count(),
            'unverified' => $citizens->clone()->whereNull('email_verified_at')->count(),
            'active' => $citizens->clone()->where('is_active', true)->count(),
            'inactive' => $citizens->clone()->where('is_active', false)->count(),
        ];
    }
}
