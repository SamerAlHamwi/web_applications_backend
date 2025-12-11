<?php

namespace App\Events;

use App\Models\Complaint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Complaint $complaint;
    public string $oldStatus;
    public string $newStatus;

    public function __construct(Complaint $complaint, string $oldStatus, string $newStatus)
    {
        $this->complaint = $complaint;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->complaint->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'tracking_number' => $this->complaint->tracking_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Complaint status changed from {$this->oldStatus} to {$this->newStatus}",
        ];
    }
}
