<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the school by email whenever a visitor submits the /contact form.
 * The submission is always saved to the database regardless of whether this
 * mail sends — see FrontendRepository::contact().
 */
class ContactFormReceived extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('New website enquiry — ' . ($this->data['subject'] ?: 'Contact form'))
            ->view('emails.contact-form')
            ->with('data', $this->data);
    }
}
