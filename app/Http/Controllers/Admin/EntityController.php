<?php
// app/Http/Controllers/Admin/EntityController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Entity;
use App\Services\EntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EntityController extends Controller
{
    public function __construct(
        private EntityService $entityService
    ) {}

    /**
     * Get all entities
     */
    // app/Http/Controllers/Admin/EntityController.php
    public function index(): JsonResponse
    {
        $entities = $this->entityService->getAllEntities();

        return response()->json([
            'data' => $entities->map(fn($entity) => [
                'id' => $entity->id,
                'name' => $entity->name,
                'name_ar' => $entity->name_ar,
                'email' => $entity->email,
                'phone' => $entity->phone,
                'description' => $entity->description,
                'description_ar' => $entity->description_ar,
                'type' => $entity->type,
                'is_active' => $entity->is_active,
                'complaints_count' => $entity->complaints_count ?? 0,
                'employees_count' => $entity->employees_count ?? 0, // ← Add this
                'created_at' => $entity->created_at,
                'updated_at' => $entity->updated_at,
            ]),
            'total' => $entities->count()
        ], 200);
    }

    /**
     * Get single entity
     */
    public function show(int $id): JsonResponse
    {
        $entity = $this->entityService->getEntityById($id);

        if (!$entity) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $entity->id,
                'name' => $entity->name,
                'name_ar' => $entity->name_ar,
                'email' => $entity->email,
                'phone' => $entity->phone,
                'description' => $entity->description,
                'description_ar' => $entity->description_ar,
                'type' => $entity->type,
                'is_active' => $entity->is_active,
                'complaints_count' => $entity->complaints_count ?? 0,
                'created_at' => $entity->created_at,
                'updated_at' => $entity->updated_at,
            ]
        ], 200);
    }

    /**
     * Create new entity
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:entities,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(['ministry', 'government_party', 'department', 'agency'])],
            'is_active' => ['boolean'],
        ]);

        try {
            $entity = $this->entityService->createEntity($validated);

            return response()->json([
                'message' => 'Entity created successfully',
                'data' => [
                    'id' => $entity->id,
                    'name' => $entity->name,
                    'name_ar' => $entity->name_ar,
                    'email' => $entity->email,
                    'phone' => $entity->phone,
                    'description' => $entity->description,
                    'description_ar' => $entity->description_ar,
                    'type' => $entity->type,
                    'is_active' => $entity->is_active,
                    'created_at' => $entity->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create entity',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update entity
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('entities')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in(['ministry', 'government_party', 'department', 'agency'])],
            'is_active' => ['boolean'],
        ]);

        try {
            $entity = $this->entityService->updateEntity($id, $validated);

            return response()->json([
                'message' => 'Entity updated successfully',
                'data' => [
                    'id' => $entity->id,
                    'name' => $entity->name,
                    'name_ar' => $entity->name_ar,
                    'email' => $entity->email,
                    'phone' => $entity->phone,
                    'description' => $entity->description,
                    'description_ar' => $entity->description_ar,
                    'type' => $entity->type,
                    'is_active' => $entity->is_active,
                    'updated_at' => $entity->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update entity',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete entity (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->entityService->deleteEntity($id);

            return response()->json([
                'message' => 'Entity deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete entity',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Toggle entity active status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $entity = $this->entityService->toggleActiveStatus($id);

            return response()->json([
                'message' => 'Entity status updated successfully',
                'data' => [
                    'id' => $entity->id,
                    'name' => $entity->name,
                    'is_active' => $entity->is_active,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle entity status',
                'error' => $e->getMessage()
            ], 422);
        }
    }
    public function complaints(Request $request, int $id): JsonResponse
    {
        // Verify entity exists
        $entity = $this->entityService->getEntityById($id);

        if (!$entity) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }

        $perPage = $request->query('per_page', 15);

        // Inject ComplaintRepository in constructor first
        $complaints = app(ComplaintRepository::class)
            ->getComplaintsByEntityId($id, $perPage);

        // Get statistics
        $statistics = app(ComplaintRepository::class)
            ->getEntityStatistics($id);

        return response()->json([
            'data' => $complaints->map(fn($complaint) => [
                'id' => $complaint->id,
                'tracking_number' => $complaint->tracking_number,
                'complaint_kind' => $complaint->complaint_kind,
                'description' => $complaint->description,
                'location' => $complaint->location,
                'status' => $complaint->status,
                'priority' => $complaint->priority,
                'info_requested' => $complaint->info_requested,
                'is_locked' => $complaint->isLocked(),
                'citizen' => [
                    'id' => $complaint->user->id,
                    'name' => $complaint->user->full_name,
                    'email' => $complaint->user->email,
                ],
                'assigned_employee' => $complaint->assignedEmployee ? [
                    'id' => $complaint->assignedEmployee->id,
                    'name' => $complaint->assignedEmployee->full_name,
                ] : null,
                'attachments_count' => $complaint->attachments->count(),
                'created_at' => $complaint->created_at,
                'updated_at' => $complaint->updated_at,
                'resolved_at' => $complaint->resolved_at,
            ]),
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
            'entity' => [
                'id' => $entity->id,
                'name' => $entity->name,
                'name_ar' => $entity->name_ar,
                'type' => $entity->type,
            ],
            'statistics' => $statistics,
        ], 200);
    }
    public function getComplaints($entityId)
    {
        $entity = Entity::findOrFail($entityId);

        $complaints = Complaint::where('entity_id', $entityId)
            ->with(['user', 'attachments'])  // Changed from 'citizen' to 'user'
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'entity' => $entity,
            'complaints' => $complaints
        ]);
    }
}
