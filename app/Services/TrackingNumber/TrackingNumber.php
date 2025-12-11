<?php

namespace App\Services\TrackingNumber;

use App\Models\Complaint;
use Illuminate\Support\Facades\DB;

class TrackingNumber
{
    /**
     * Generate unique tracking number
     * Format: CMP-2025-000001
     */
    public function generate(): string
    {
        $year = date('Y');
        $prefix = "CMP-{$year}-";

        // Get last tracking number for this year
        $lastComplaint = Complaint::where('tracking_number', 'LIKE', "{$prefix}%")
            ->orderBy('tracking_number', 'desc')
            ->lockForUpdate() // Prevent race condition
            ->first();

        if ($lastComplaint) {
            // Extract number and increment
            $lastNumber = (int) substr($lastComplaint->tracking_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            // First complaint of the year
            $newNumber = 1;
        }

        // Format: CMP-2025-000001
        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Validate tracking number format
     */
    public function validate(string $trackingNumber): bool
    {
        return preg_match('/^CMP-\d{4}-\d{6}$/', $trackingNumber);
    }
}
