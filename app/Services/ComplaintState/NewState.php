<?php

namespace App\Services\ComplaintState;

use App\Models\Complaint;

class NewState implements ComplaintStateInterface
{
    public function canBeUpdatedByCitizen(Complaint $complaint): bool
    {
        return true; // Citizens can update new complaints
    }

    public function canBeAcceptedByEmployee(Complaint $complaint): bool
    {
        return true; // Employees can accept new complaints
    }

    public function canChangeStatus(Complaint $complaint, string $newStatus, $user): bool
    {
        // From 'new' can go to: in_progress, declined
        $allowedTransitions = $this->getAllowedTransitions();

        if (!in_array($newStatus, $allowedTransitions)) {
            return false;
        }

        // Only employees from the complaint's entity can change status
        if ($user->role === 'employee' && $user->entity_id === $complaint->entity_id) {
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
        return ['in_progress', 'declined'];
    }

    public function getStateName(): string
    {
        return 'new';
    }
}
