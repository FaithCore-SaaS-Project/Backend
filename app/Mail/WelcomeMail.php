<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $adminName;
    public $churchName;
    public $activationCode;
    public $planName;

    /**
     * Create a new message instance.
     */
    public function __construct($adminName, $churchName, $activationCode, $planName)
    {
        $this->adminName = $adminName;
        $this->churchName = $churchName;
        $this->activationCode = $activationCode;
        $this->planName = $planName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to FaithCore - ' . $this->churchName . ' Activated!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
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
