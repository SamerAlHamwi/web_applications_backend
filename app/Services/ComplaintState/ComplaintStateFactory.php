<?php

namespace App\Services\ComplaintState;

use App\Models\Complaint;

class ComplaintStateFactory
{
    /**
     * Create state instance based on complaint status
     */
    public static function create(string $status): ComplaintStateInterface
    {
        return match($status) {
            'new' => new NewState(),
            'in_progress' => new InProgressState(),
            'finished' => new FinishedState(),
            'declined' => new DeclinedState(),
            default => throw new \InvalidArgumentException("Invalid status: {$status}"),
        };
    }

    /**
     * Get state for complaint
     */
    public static function forComplaint(Complaint $complaint): ComplaintStateInterface
    {
        return self::create($complaint->status);
    }
}
