<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $adminName;
    public $churchName;
    public $expiryDate;
    public $amount;

    /**
     * Create a new message instance.
     */
    public function __construct($adminName, $churchName, $expiryDate, $amount)
    {
        $this->adminName = $adminName;
        $this->churchName = $churchName;
        $this->expiryDate = $expiryDate;
        $this->amount = $amount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'FaithCore Subscription Auto-Renewal Notice - ' . $this->churchName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
