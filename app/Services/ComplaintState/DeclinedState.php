<?php

namespace App\Services\ComplaintState;

use App\Models\Complaint;

class DeclinedState implements ComplaintStateInterface
{
    public function canBeUpdatedByCitizen(Complaint $complaint): bool
    {
        return true; // Citizens can update and resubmit declined complaints
    }

    public function canBeAcceptedByEmployee(Complaint $complaint): bool
    {
        return false; // Cannot accept declined complaints directly
    }

    public function canChangeStatus(Complaint $complaint, string $newStatus, $user): bool
    {
        $allowedTransitions = $this->getAllowedTransitions();

        if (!in_array($newStatus, $allowedTransitions)) {
            return false;
        }

        // Citizens can resubmit (change to 'new')
        if ($user->role === 'citizen' && $user->id === $complaint->user_id && $newStatus === 'new') {
            return true;
        }

        // Employees and admins can change status
        if ($user->role === 'employee' && $user->entity_id === $complaint->entity_id) {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return false;
    }

    public function getAllowedTransitions(): array
    {
        return ['new', 'in_progress']; // Can be resubmitted or reopened
    }

    public function getStateName(): string
    {
        return 'declined';
    }
}
