<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a parent/guardian right after they book an assessment slot -
 * carries the signed, anonymous link straight into the standalone
 * Assessment System (no LMS login involved). See
 * AssessmentBookingController::store() for where this is dispatched;
 * same synchronous, best-effort-in-its-own-try/catch pattern as
 * ContactFormReceived - a mail failure never blocks the visitor's
 * booking confirmation, only gets logged.
 */
class AssessmentInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Your assessment booking is confirmed — '.($this->data['student_name'] ?? ''))
            ->view('emails.assessment-invite')
            ->with('data', $this->data);
    }
}
