<?php

namespace App\Notifications;

use App\Models\Complaint;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Complaint $complaint;
    private string $oldStatus;
    private string $newStatus;

    public function __construct(Complaint $complaint, string $oldStatus, string $newStatus)
    {
        $this->complaint = $complaint;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Complaint Status Updated')
            ->greeting('Hello ' . $notifiable->full_name . '!')
            ->line('Your complaint status has been updated.')
            ->line('Tracking Number: ' . $this->complaint->tracking_number)
            ->line('Previous Status: ' . ucfirst($this->oldStatus))
            ->line('New Status: ' . ucfirst($this->newStatus))
            ->action('View Complaint', url('/complaints/' . $this->complaint->tracking_number))
            ->line('Thank you for using our service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'tracking_number' => $this->complaint->tracking_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => 'Status Updated',
            'message' => "Your complaint #{$this->complaint->tracking_number} status changed to " . ucfirst($this->newStatus),
            'action_url' => '/complaints/' . $this->complaint->tracking_number,
        ];
    }

    public function afterSend(object $notifiable)
    {
        if ($notifiable->fcm_token) {
            $fcmService = app(FcmService::class);

            if ($fcmService->isConfigured()) {
                $fcmService->sendToDevice(
                    $notifiable->fcm_token,
                    [
                        'title' => 'Complaint Status Updated',
                        'body' => "Status changed to: " . ucfirst($this->newStatus),
                    ],
                    [
                        'complaint_id' => (string) $this->complaint->id,
                        'tracking_number' => $this->complaint->tracking_number,
                        'type' => 'status_changed',
                        'new_status' => $this->newStatus,
                    ]
                );
            }
        }
    }
}
