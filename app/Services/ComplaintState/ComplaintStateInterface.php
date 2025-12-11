<?php

namespace App\Services\ComplaintState;

use App\Models\Complaint;

interface ComplaintStateInterface
{
    /**
     * Can this complaint be updated by citizen?
     */
    public function canBeUpdatedByCitizen(Complaint $complaint): bool;

    /**
     * Can this complaint be accepted by employee?
     */
    public function canBeAcceptedByEmployee(Complaint $complaint): bool;

    /**
     * Can status be changed?
     */
    public function canChangeStatus(Complaint $complaint, string $newStatus, $user): bool;

    /**
     * Get allowed transitions
     */
    public function getAllowedTransitions(): array;

    /**
     * Get state name
     */
    public function getStateName(): string;
}
