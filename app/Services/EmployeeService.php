<?php
// app/Services/EmployeeService.php

namespace App\Services;

use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Repositories\EntityRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

class EmployeeService
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private EntityRepository $entityRepository
    ) {}

    /**
     * Get all employees with their entity
     */
    public function getAllEmployees(): Collection
    {
        return $this->employeeRepository->allWithEntity();
    }

    /**
     * Get employee by ID with entity relationship
     */
    public function getEmployeeById(int $id): ?User
    {
        return $this->employeeRepository->findWithEntity($id);
    }

    /**
     * Get employees by entity
     */
    public function getEmployeesByEntity(int $entityId): Collection
    {
        return $this->employeeRepository->findByEntity($entityId);
    }

    /**
     * Create new employee
     */
    public function createEmployee(array $data): User
    {
        // Verify entity exists and is active
        $entity = $this->entityRepository->findById($data['entity_id']);

        if (!$entity) {
            throw ValidationException::withMessages([
                'entity_id' => ['The selected entity does not exist.']
            ]);
        }

        if (!$entity->is_active) {
            throw ValidationException::withMessages([
                'entity_id' => ['The selected entity is not active.']
            ]);
        }

        // Check if email already exists
        if ($this->employeeRepository->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered.']
            ]);
        }

        // Prepare employee data
        $employeeData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'employee',
            'entity_id' => $data['entity_id'],
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(), // Admin-created accounts are pre-verified
        ];

        return $this->employeeRepository->create($employeeData);
    }

    /**
     * Update employee
     */
    public function updateEmployee(int $id, array $data): User
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee' => ['Employee not found.']
            ]);
        }

        // If updating entity, verify it exists and is active
        if (isset($data['entity_id'])) {
            $entity = $this->entityRepository->findById($data['entity_id']);

            if (!$entity) {
                throw ValidationException::withMessages([
                    'entity_id' => ['The selected entity does not exist.']
                ]);
            }

            if (!$entity->is_active) {
                throw ValidationException::withMessages([
                    'entity_id' => ['The selected entity is not active.']
                ]);
            }
        }

        // Check email uniqueness (excluding current employee)
        if (isset($data['email']) && $this->employeeRepository->emailExists($data['email'], $id)) {
            throw ValidationException::withMessages([
                'email' => ['This email is already in use.']
            ]);
        }

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $this->employeeRepository->update($employee, $data);

        return $employee->fresh(['entity']);
    }

    /**
     * Delete employee (soft delete)
     */
    public function deleteEmployee(int $id): bool
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee' => ['Employee not found.']
            ]);
        }

        // Optional: Check if employee has assigned complaints
        // $assignedComplaints = $employee->assignedComplaints()->count();
        // if ($assignedComplaints > 0) {
        //     throw ValidationException::withMessages([
        //         'employee' => ["Cannot delete employee with {$assignedComplaints} assigned complaints."]
        //     ]);
        // }

        return $this->employeeRepository->delete($employee);
    }

    /**
     * Restore soft deleted employee
     */
    public function restoreEmployee(int $id): bool
    {
        return $this->employeeRepository->restore($id);
    }

    /**
     * Toggle employee active status
     */
    public function toggleActiveStatus(int $id): User
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee' => ['Employee not found.']
            ]);
        }

        // Get current status and toggle it
        $newStatus = !$employee->is_active;

        $this->employeeRepository->update($employee, [
            'is_active' => $newStatus
        ]);

        return $employee->fresh(['entity']);
    }

    /**
     * Activate employee
     */
    public function activateEmployee(int $id): User
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee' => ['Employee not found.']
            ]);
        }

        $this->employeeRepository->update($employee, ['is_active' => true]);

        return $employee->fresh(['entity']);
    }

    /**
     * Deactivate employee
     */
    public function deactivateEmployee(int $id): User
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee' => ['Employee not found.']
            ]);
        }

        $this->employeeRepository->update($employee, ['is_active' => false]);

        return $employee->fresh(['entity']);
    }

    /**
     * Get active employees only
     */
    public function getActiveEmployees(): Collection
    {
        return $this->employeeRepository->allActive();
    }

    /**
     * Get inactive employees only
     */
    public function getInactiveEmployees(): Collection
    {
        return $this->employeeRepository->allInactive();
    }

    /**
     * Count employees by entity
     */
    public function countEmployeesByEntity(int $entityId): int
    {
        return $this->employeeRepository->countByEntity($entityId);
    }
}
