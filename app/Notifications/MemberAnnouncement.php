<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberAnnouncement extends Notification implements ShouldQueue
{
    use Queueable;

    public $subject;
    public $message;
    public $channels;

    /**
     * Create a new notification instance.
     */
    public function __construct($subject, $message, $channels = ['database', 'mail'])
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->channels = $channels;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject($this->subject)
                    ->greeting('Hello ' . $notifiable->first_name . ',')
                    ->line($this->message)
                    ->action('View Dashboard', url('/'))
                    ->line('Thank you for being part of our church family!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
