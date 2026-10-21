<?php

namespace App\Mail;

use App\Models\Digest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DailyDigest extends Mailable
{
    public function __construct(public Digest $digest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Watchpost Security Digest — '.$this->digest->report_date);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.digest');
    }
}
