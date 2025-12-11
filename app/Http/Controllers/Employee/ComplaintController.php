<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinishComplaintRequest;
use App\Http\Requests\DeclineComplaintRequest;
use App\Http\Requests\RequestInfoRequest;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function __construct(
        private ComplaintService $complaintService
    ) {}

    /**
     * Get all complaints for employee's entity
     */
    public function index(Request $request): JsonResponse
    {
        $employee = auth()->user();

        $status = $request->query('status'); // Filter by status (optional)
        $complaints = $this->complaintService->getEntityComplaints(
            $employee->entity_id,
            $status,
            $request->query('per_page', 15)
        );

        return response()->json([
            'data' => $complaints->map(fn($c) => $this->formatComplaintListItem($c)),
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ], 200);
    }

    /**
     * Get complaints assigned to this employee
     */
    public function myAssignedComplaints(Request $request): JsonResponse
    {
        $employee = auth()->user();

        $complaints = $this->complaintService->getEmployeeAssignedComplaints(
            $employee->id,
            $request->query('per_page', 15)
        );

        return response()->json([
            'data' => $complaints->map(fn($c) => $this->formatComplaintListItem($c)),
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ], 200);
    }

    /**
     * View single complaint details
     */
    public function show(string $trackingNumber): JsonResponse
    {
        $employee = auth()->user();
        $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

        if (!$complaint) {
            return response()->json(['message' => 'Complaint not found'], 404);
        }

        // Check if complaint belongs to employee's entity
        if ($complaint->entity_id !== $employee->entity_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $this->formatComplaintResponse($complaint),
        ], 200);
    }

    /**
     * Accept complaint (change status to in_progress)
     */
    public function accept(string $trackingNumber): JsonResponse
    {
        try {
            $employee = auth()->user();
            $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

            if (!$complaint) {
                return response()->json(['message' => 'Complaint not found'], 404);
            }

            $updatedComplaint = $this->complaintService->acceptComplaint($complaint, $employee);

            return response()->json([
                'message' => 'Complaint accepted successfully',
                'data' => $this->formatComplaintResponse($updatedComplaint),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to accept complaint',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Finish complaint
     */
    public function finish(FinishComplaintRequest $request, string $trackingNumber): JsonResponse
    {
        try {
            $employee = auth()->user();
            $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

            if (!$complaint) {
                return response()->json(['message' => 'Complaint not found'], 404);
            }

            $updatedComplaint = $this->complaintService->finishComplaint(
                $complaint,
                $employee,
                $request->input('resolution')
            );

            return response()->json([
                'message' => 'Complaint finished successfully',
                'data' => $this->formatComplaintResponse($updatedComplaint),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to finish complaint',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Decline complaint
     */
    public function decline(DeclineComplaintRequest $request, string $trackingNumber): JsonResponse
    {
        try {
            $employee = auth()->user();
            $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

            if (!$complaint) {
                return response()->json(['message' => 'Complaint not found'], 404);
            }

            $updatedComplaint = $this->complaintService->declineComplaint(
                $complaint,
                $employee,
                $request->input('reason')
            );

            return response()->json([
                'message' => 'Complaint declined',
                'data' => $this->formatComplaintResponse($updatedComplaint),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to decline complaint',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Request more information from citizen
     */
    public function requestInfo(RequestInfoRequest $request, string $trackingNumber): JsonResponse
    {
        try {
            $employee = auth()->user();
            $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

            if (!$complaint) {
                return response()->json(['message' => 'Complaint not found'], 404);
            }

            $updatedComplaint = $this->complaintService->requestMoreInfo(
                $complaint,
                $employee,
                $request->input('message')
            );

            return response()->json([
                'message' => 'Information requested successfully',
                'data' => $this->formatComplaintResponse($updatedComplaint),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to request information',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Unlock complaint manually (if needed)
     */
    public function unlock(string $trackingNumber): JsonResponse
    {
        try {
            $employee = auth()->user();
            $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

            if (!$complaint) {
                return response()->json(['message' => 'Complaint not found'], 404);
            }

            // Only assigned employee or admin can unlock
            if ($complaint->assigned_to !== $employee->id && $employee->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $complaint->unlock();

            return response()->json([
                'message' => 'Complaint unlocked successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to unlock complaint',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    private function formatComplaintResponse($complaint): array
    {
        return [
            'id' => $complaint->id,
            'tracking_number' => $complaint->tracking_number,
            'complaint_kind' => $complaint->complaint_kind,
            'description' => $complaint->description,
            'location' => $complaint->location,
            'latitude' => $complaint->latitude,
            'longitude' => $complaint->longitude,
            'status' => $complaint->status,
            'priority' => $complaint->priority,
            'info_requested' => $complaint->info_requested,
            'info_request_message' => $complaint->info_request_message,
            'is_locked' => $complaint->isLocked(),
            'locked_at' => $complaint->locked_at,
            'lock_expires_at' => $complaint->lock_expires_at,
            'citizen' => [
                'id' => $complaint->user->id,
                'name' => $complaint->user->full_name,
                'email' => $complaint->user->email,
            ],
            'entity' => [
                'id' => $complaint->entity->id,
                'name' => $complaint->entity->name,
                'name_ar' => $complaint->entity->name_ar,
            ],
            'attachments' => [
                'images' => $complaint->images->map(fn($img) => [
                    'id' => $img->id,
                    'file_name' => $img->file_name,
                    'url' => $img->url,
                    'size' => $img->human_readable_size,
                ]),
                'pdfs' => $complaint->pdfs->map(fn($pdf) => [
                    'id' => $pdf->id,
                    'file_name' => $pdf->file_name,
                    'url' => $pdf->url,
                    'size' => $pdf->human_readable_size,
                ]),
            ],
            'assigned_employee' => $complaint->assignedEmployee ? [
                'id' => $complaint->assignedEmployee->id,
                'name' => $complaint->assignedEmployee->full_name,
            ] : null,
            'admin_notes' => $complaint->admin_notes,
            'resolution' => $complaint->resolution,
            'created_at' => $complaint->created_at,
            'updated_at' => $complaint->updated_at,
            'reviewed_at' => $complaint->reviewed_at,
            'resolved_at' => $complaint->resolved_at,
        ];
    }

    private function formatComplaintListItem($complaint): array
    {
        return [
            'id' => $complaint->id,
            'tracking_number' => $complaint->tracking_number,
            'complaint_kind' => $complaint->complaint_kind,
            'status' => $complaint->status,
            'priority' => $complaint->priority,
            'info_requested' => $complaint->info_requested,
            'is_locked' => $complaint->isLocked(),
            'citizen_name' => $complaint->user->full_name,
            'assigned_to_me' => $complaint->assigned_to === auth()->id(),
            'attachments_count' => $complaint->attachments->count(),
            'created_at' => $complaint->created_at,
        ];
    }
}
