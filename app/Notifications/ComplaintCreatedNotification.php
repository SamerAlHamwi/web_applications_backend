<?php

namespace App\Notifications;

use App\Models\Complaint;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Complaint $complaint;

    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }

    /**
     * Get the notification's delivery channels
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail']; // Only built-in channels
    }

    /**
     * Get the mail representation
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Complaint Submitted Successfully')
            ->greeting('Hello ' . $notifiable->full_name . '!')
            ->line('Your complaint has been submitted successfully.')
            ->line('Tracking Number: ' . $this->complaint->tracking_number)
            ->line('Entity: ' . $this->complaint->entity->name)
            ->line('Status: ' . ucfirst($this->complaint->status))
            ->action('Track Complaint', url('/complaints/' . $this->complaint->tracking_number))
            ->line('You will receive updates about your complaint.');
    }

    /**
     * Get the array representation (database notification)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'tracking_number' => $this->complaint->tracking_number,
            'entity_name' => $this->complaint->entity->name,
            'status' => $this->complaint->status,
            'title' => 'Complaint Submitted',
            'message' => 'Your complaint #' . $this->complaint->tracking_number . ' has been received.',
            'action_url' => '/complaints/' . $this->complaint->tracking_number,
        ];
    }

    /**
     * Handle FCM push notification after other notifications are sent
     */
    public function afterSend(object $notifiable)
    {
        // Only send FCM if user has a device token
        if ($notifiable->fcm_token) {
            $fcmService = app(FcmService::class);

            if ($fcmService->isConfigured()) {
                $fcmService->sendToDevice(
                    $notifiable->fcm_token,
                    [
                        'title' => 'Complaint Submitted Successfully',
                        'body' => 'Your complaint #' . $this->complaint->tracking_number . ' has been received.',
                    ],
                    [
                        'complaint_id' => (string) $this->complaint->id,
                        'tracking_number' => $this->complaint->tracking_number,
                        'type' => 'complaint_created',
                        'action' => 'open_complaint',
                    ]
                );
            }
        }
    }
}
