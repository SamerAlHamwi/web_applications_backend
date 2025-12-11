<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function __construct(
        private ComplaintService $complaintService
    ) {}

    /**
     * Create new complaint (Citizen)
     */
    public function store(CreateComplaintRequest $request): JsonResponse
    {
        try {
            $complaint = $this->complaintService->createComplaint(
                array_merge($request->validated(), [
                    'user_id' => auth()->id()
                ]),
                $request->file('images'),
                $request->file('pdfs')
            );

            return response()->json([
                'message' => 'Complaint submitted successfully',
                'data' => $this->formatComplaintResponse($complaint),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create complaint',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update complaint (Citizen - only for 'new' or 'declined')
     */
    public function update(UpdateComplaintRequest $request, string $trackingNumber): JsonResponse
    {
        try {
            $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

            if (!$complaint) {
                return response()->json(['message' => 'Complaint not found'], 404);
            }

            $updatedComplaint = $this->complaintService->updateComplaint(
                $complaint,
                $request->validated(),
                auth()->user(),
                $request->file('images'),
                $request->file('pdfs')
            );

            return response()->json([
                'message' => 'Complaint updated successfully',
                'data' => $this->formatComplaintResponse($updatedComplaint),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update complaint',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Track complaint by tracking number
     */
    public function track(string $trackingNumber): JsonResponse
    {
        $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

        if (!$complaint) {
            return response()->json(['message' => 'Complaint not found'], 404);
        }

        // Check authorization
        $user = auth()->user();
        if ($user->role === 'citizen' && $complaint->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role === 'employee' && $complaint->entity_id !== $user->entity_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $this->formatComplaintResponse($complaint),
        ], 200);
    }

    /**
     * Get my complaints (Citizen)
     */
    public function myComplaints(): JsonResponse
    {
        $complaints = $this->complaintService->getUserComplaints(auth()->id());

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
     * Delete attachment (Citizen - only for 'new' or 'declined')
     */
    public function deleteAttachment(string $trackingNumber, int $attachmentId): JsonResponse
    {
        try {
            $complaint = $this->complaintService->getByTrackingNumber($trackingNumber);

            if (!$complaint) {
                return response()->json(['message' => 'Complaint not found'], 404);
            }

            // Check ownership
            if ($complaint->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check if can be updated
            $state = $complaint->getState();
            if (!$state->canBeUpdatedByCitizen($complaint)) {
                return response()->json([
                    'message' => 'Cannot delete attachments in current status'
                ], 422);
            }

            $attachment = $complaint->attachments()->find($attachmentId);
            if (!$attachment) {
                return response()->json(['message' => 'Attachment not found'], 404);
            }

            $attachment->delete();

            return response()->json([
                'message' => 'Attachment deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete attachment',
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
            'status' => $complaint->status,
            'info_requested' => $complaint->info_requested,
            'info_request_message' => $complaint->info_request_message,
            'is_locked' => $complaint->isLocked(),
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
            'resolution' => $complaint->resolution,
            'created_at' => $complaint->created_at,
            'updated_at' => $complaint->updated_at,
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
            'info_requested' => $complaint->info_requested,
            'entity' => [
                'name' => $complaint->entity->name,
            ],
            'attachments_count' => $complaint->attachments->count(),
            'created_at' => $complaint->created_at,
        ];
    }
}
