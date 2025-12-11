<?php
// app/Http/Controllers/Admin/EmployeeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    /**
     * Get all employees
     */
    public function index(): JsonResponse
    {
        $employees = $this->employeeService->getAllEmployees();

        return response()->json([
            'data' => $employees->map(fn($employee) => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'is_active' => $employee->is_active,
                'entity_id' => $employee->entity_id,
                'entity' => $employee->entity ? [
                    'id' => $employee->entity->id,
                    'name' => $employee->entity->name,
                    'name_ar' => $employee->entity->name_ar,
                ] : null,
                'created_at' => $employee->created_at,
                'updated_at' => $employee->updated_at,
            ]),
            'total' => $employees->count()
        ], 200);
    }

    /**
     * Get single employee
     */
    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'is_active' => $employee->is_active,
                'entity_id' => $employee->entity_id,
                'entity' => $employee->entity ? [
                    'id' => $employee->entity->id,
                    'name' => $employee->entity->name,
                    'name_ar' => $employee->entity->name_ar,
                    'email' => $employee->entity->email,
                    'type' => $employee->entity->type,
                ] : null,
                'created_at' => $employee->created_at,
                'updated_at' => $employee->updated_at,
            ]
        ], 200);
    }

    /**
     * Create new employee
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'is_active' => ['boolean'],
        ]);

        try {
            $employee = $this->employeeService->createEmployee($validated);

            return response()->json([
                'message' => 'Employee created successfully',
                'data' => [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'is_active' => $employee->is_active,
                    'entity_id' => $employee->entity_id,
                    'entity' => $employee->entity ? [
                        'id' => $employee->entity->id,
                        'name' => $employee->entity->name,
                    ] : null,
                    'created_at' => $employee->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create employee',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update employee
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => ['sometimes', 'string', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            'entity_id' => ['sometimes', 'integer', 'exists:entities,id'],
            'is_active' => ['boolean'],
        ]);

        try {
            $employee = $this->employeeService->updateEmployee($id, $validated);

            return response()->json([
                'message' => 'Employee updated successfully',
                'data' => [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'is_active' => $employee->is_active,
                    'entity_id' => $employee->entity_id,
                    'updated_at' => $employee->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update employee',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete employee (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->employeeService->deleteEmployee($id);

            return response()->json([
                'message' => 'Employee deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete employee',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Toggle employee active status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $employee = $this->employeeService->toggleActiveStatus($id);

            return response()->json([
                'message' => 'Employee status updated successfully',
                'data' => [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'is_active' => $employee->is_active,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle employee status',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get employees by entity
     */
    public function getByEntity(int $entityId): JsonResponse
    {
        $employees = $this->employeeService->getEmployeesByEntity($entityId);

        return response()->json([
            'data' => $employees->map(fn($employee) => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'is_active' => $employee->is_active,
                'created_at' => $employee->created_at,
            ]),
            'total' => $employees->count()
        ], 200);
    }
}
