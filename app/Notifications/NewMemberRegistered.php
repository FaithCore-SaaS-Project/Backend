<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMemberRegistered extends Notification
{
    use Queueable;

    public $memberName;

    /**
     * Create a new notification instance.
     */
    public function __construct($memberName)
    {
        $this->memberName = $memberName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => 'New Member Registration',
            'message' => "{$this->memberName} has just registered via the Mobile App."
        ];
    }
}
