<?php

namespace App\Repositories;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ComplaintRepository
{
    public function create(array $data): Complaint
    {
        return Complaint::create($data);
    }

    public function findById(int $id): ?Complaint
    {
        return Complaint::with(['user', 'entity', 'attachments', 'assignedEmployee'])
            ->find($id);
    }

    public function findByTrackingNumber(string $trackingNumber): ?Complaint
    {
        return Complaint::with(['user', 'entity', 'attachments', 'assignedEmployee'])
            ->where('tracking_number', $trackingNumber)
            ->first();
    }

    public function getUserComplaints(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Complaint::with(['entity', 'attachments'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getEntityComplaints(int $entityId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Complaint::with(['user', 'attachments', 'assignedEmployee'])
            ->where('entity_id', $entityId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getEmployeeAssignedComplaints(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return Complaint::with(['user', 'entity', 'attachments'])
            ->where('assigned_to', $employeeId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Complaint::with(['user', 'entity', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Complaint::with(['user', 'entity', 'attachments'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function update(Complaint $complaint, array $data): bool
    {
        return $complaint->update($data);
    }

    public function delete(Complaint $complaint): bool
    {
        return $complaint->delete();
    }
}
