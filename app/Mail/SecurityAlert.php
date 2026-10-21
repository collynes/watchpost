<?php

namespace App\Mail;

use App\Models\Alert;
use App\Models\Event;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SecurityAlert extends Mailable
{
    public function __construct(public Alert $alert, public Event $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Watchpost SEV '.$this->alert->severity.' — '.$this->event->source_id);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.alert');
    }
}
