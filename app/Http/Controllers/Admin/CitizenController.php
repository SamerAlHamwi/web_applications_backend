<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Repositories\ComplaintRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private ComplaintRepository $complaintRepository
    ) {}

    /**
     * Get all citizens in the system
     * GET /api/admin/citizens
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15);

        $citizens = $search
            ? $this->userRepository->searchCitizens($search, $perPage)
            : $this->userRepository->getAllCitizens($perPage);

        $totalCount = $this->userRepository->getTotalCitizensCount();

        return response()->json([
            'data' => $citizens->map(function($citizen) {
                // Manually count complaints to avoid withCount issues
                $complaintsCount = \App\Models\Complaint::where('user_id', $citizen->id)->count();

                return [
                    'id' => $citizen->id,
                    'first_name' => $citizen->first_name,
                    'last_name' => $citizen->last_name,
                    'full_name' => $citizen->full_name,
                    'email' => $citizen->email,
                    'email_verified_at' => $citizen->email_verified_at,
                    'is_verified' => $citizen->email_verified_at !== null,
                    'complaints_count' => $complaintsCount,
                    'created_at' => $citizen->created_at,
                    'updated_at' => $citizen->updated_at,
                ];
            }),
            'meta' => [
                'current_page' => $citizens->currentPage(),
                'last_page' => $citizens->lastPage(),
                'per_page' => $citizens->perPage(),
                'total' => $citizens->total(),
            ],
            'summary' => [
                'total_citizens' => $totalCount,
            ]
        ], 200);
    }

    /**
     * Get single citizen details
     * GET /api/admin/citizens/{id}
     */
    public function show(int $id): JsonResponse
    {
        $citizen = $this->userRepository->findCitizenById($id);

        if (!$citizen) {
            return response()->json([
                'message' => 'Citizen not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $citizen->id,
                'first_name' => $citizen->first_name,
                'last_name' => $citizen->last_name,
                'full_name' => $citizen->full_name,
                'email' => $citizen->email,
                'email_verified_at' => $citizen->email_verified_at,
                'is_verified' => $citizen->email_verified_at !== null,
                'complaints_count' => $citizen->complaints_count ?? 0,
                'created_at' => $citizen->created_at,
                'updated_at' => $citizen->updated_at,
            ]
        ], 200);
    }

    /**
     * Get all complaints for a specific citizen
     * GET /api/admin/citizens/{id}/complaints
     */
    public function complaints(Request $request, int $id): JsonResponse
    {
        // Verify citizen exists
        $citizen = $this->userRepository->findCitizenById($id);

        if (!$citizen) {
            return response()->json([
                'message' => 'Citizen not found'
            ], 404);
        }

        $perPage = $request->query('per_page', 15);
        $complaints = $this->complaintRepository->getComplaintsByCitizenId($id, $perPage);

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
                'entity' => [
                    'id' => $complaint->entity->id,
                    'name' => $complaint->entity->name,
                    'name_ar' => $complaint->entity->name_ar,
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
            'citizen' => [
                'id' => $citizen->id,
                'full_name' => $citizen->full_name,
                'email' => $citizen->email,
            ]
        ], 200);
    }
}
