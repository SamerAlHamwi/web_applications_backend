<?php
// app/Repositories/EmployeeRepository.php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository
{
    /**
     * Get all employees
     */
    public function all(): Collection
    {
        return User::where('role', 'employee')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Get all employees with entity relationship
     */
    public function allWithEntity(): Collection
    {
        return User::where('role', 'employee')
            ->with('entity')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Get active employees only
     */
    public function allActive(): Collection
    {
        return User::where('role', 'employee')
            ->where('is_active', true)
            ->with('entity')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Get inactive employees only
     */
    public function allInactive(): Collection
    {
        return User::where('role', 'employee')
            ->where('is_active', false)
            ->with('entity')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Find employee by ID
     */
    public function findById(int $id): ?User
    {
        return User::where('role', 'employee')->find($id);
    }

    /**
     * Find employee by ID with entity relationship
     */
    public function findWithEntity(int $id): ?User
    {
        return User::where('role', 'employee')
            ->with('entity')
            ->find($id);
    }

    /**
     * Find employee by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('role', 'employee')
            ->where('email', $email)
            ->first();
    }

    /**
     * Find employees by entity
     */
    public function findByEntity(int $entityId): Collection
    {
        return User::where('role', 'employee')
            ->where('entity_id', $entityId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Find active employees by entity
     */
    public function findActiveByEntity(int $entityId): Collection
    {
        return User::where('role', 'employee')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Create employee
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update employee
     */
    public function update(User $employee, array $data): bool
    {
        // Ensure boolean values are properly cast
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        return $employee->update($data);
    }

    /**
     * Soft delete employee
     */
    public function delete(User $employee): bool
    {
        return $employee->delete();
    }

    /**
     * Restore soft deleted employee
     */
    public function restore(int $id): bool
    {
        $employee = User::where('role', 'employee')
            ->withTrashed()
            ->find($id);

        return $employee ? $employee->restore() : false;
    }

    /**
     * Force delete employee (permanent)
     */
    public function forceDelete(User $employee): bool
    {
        return $employee->forceDelete();
    }

    /**
     * Check if email exists (for employees only)
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = User::where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Count employees by entity
     */
    public function countByEntity(int $entityId): int
    {
        return User::where('role', 'employee')
            ->where('entity_id', $entityId)
            ->count();
    }

    /**
     * Count active employees by entity
     */
    public function countActiveByEntity(int $entityId): int
    {
        return User::where('role', 'employee')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get employees with complaints count
     */
    public function allWithComplaintsCount(): Collection
    {
        return User::where('role', 'employee')
            ->with('entity')
            ->withCount('assignedComplaints')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Find employee with complaints
     */
    public function findWithComplaints(int $id): ?User
    {
        return User::where('role', 'employee')
            ->with(['entity', 'assignedComplaints'])
            ->find($id);
    }

    /**
     * Search employees by name or email
     */
    public function search(string $query): Collection
    {
        return User::where('role', 'employee')
            ->with('entity')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Get employees paginated
     */
    public function paginate(int $perPage = 15)
    {
        return User::where('role', 'employee')
            ->with('entity')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage);
    }
}
