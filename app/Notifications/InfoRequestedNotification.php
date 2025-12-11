<?php

namespace App\Notifications;

use App\Models\Complaint;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InfoRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Complaint $complaint;

    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Additional Information Requested')
            ->greeting('Hello ' . $notifiable->full_name . '!')
            ->line('The employee handling your complaint has requested additional information.')
            ->line('Tracking Number: ' . $this->complaint->tracking_number)
            ->line('Message: ' . $this->complaint->info_request_message)
            ->action('Update Complaint', url('/complaints/' . $this->complaint->tracking_number . '/update'))
            ->line('Please provide the requested information to proceed with your complaint.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'tracking_number' => $this->complaint->tracking_number,
            'title' => 'Information Requested',
            'message' => $this->complaint->info_request_message,
            'action_url' => '/complaints/' . $this->complaint->tracking_number . '/update',
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
                        'title' => 'Additional Information Needed',
                        'body' => 'Please update your complaint with the requested information.',
                    ],
                    [
                        'complaint_id' => (string) $this->complaint->id,
                        'tracking_number' => $this->complaint->tracking_number,
                        'type' => 'info_requested',
                    ]
                );
            }
        }
    }
}
