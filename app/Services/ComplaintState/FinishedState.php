<?php

namespace App\Services\ComplaintState;

use App\Models\Complaint;

class FinishedState implements ComplaintStateInterface
{
    public function canBeUpdatedByCitizen(Complaint $complaint): bool
    {
        return false; // Cannot update finished complaints
    }

    public function canBeAcceptedByEmployee(Complaint $complaint): bool
    {
        return false; // Cannot accept finished complaints
    }

    public function canChangeStatus(Complaint $complaint, string $newStatus, $user): bool
    {
        // Only admins can reopen finished complaints
        if ($user->role === 'admin') {
            return in_array($newStatus, $this->getAllowedTransitions());
        }

        return false;
    }

    public function getAllowedTransitions(): array
    {
        return ['in_progress']; // Only admin can reopen
    }

    public function getStateName(): string
    {
        return 'finished';
    }
}
