<?php

namespace App\Services\ComplaintState;

use App\Models\Complaint;

class InProgressState implements ComplaintStateInterface
{
    public function canBeUpdatedByCitizen(Complaint $complaint): bool
    {
        // Citizens can only update if info is requested
        return $complaint->info_requested;
    }

    public function canBeAcceptedByEmployee(Complaint $complaint): bool
    {
        return false; // Already in progress, cannot be re-accepted
    }

    public function canChangeStatus(Complaint $complaint, string $newStatus, $user): bool
    {
        $allowedTransitions = $this->getAllowedTransitions();

        if (!in_array($newStatus, $allowedTransitions)) {
            return false;
        }

        // Only the assigned employee can change status
        if ($user->role === 'employee' && $user->id === $complaint->assigned_to) {
            return true;
        }

        // Admins can always change status
        if ($user->role === 'admin') {
            return true;
        }

        return false;
    }

    public function getAllowedTransitions(): array
    {
        return ['finished', 'declined'];
    }

    public function getStateName(): string
    {
        return 'in_progress';
    }
}
