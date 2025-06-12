<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Email extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;
    public $subjectText;
    public $recipient;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public function __construct($messageContent, $subjectText = '[TaiCOL]', $recipient = '')
    {
        $this->messageContent = $messageContent;
        $this->subjectText = $subjectText;
        $this->recipient = $recipient;
    }

    public function envelope()
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.example',
        );
    }

    public function attachments()
    {
        return [];
    }
}
