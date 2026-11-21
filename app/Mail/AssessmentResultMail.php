<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent automatically the instant a lead's assessment is submitted (see
 * AssessmentBookingController::completed(), triggered by a same-origin
 * ping from the Assessment System's own JS right after submitRun()) -
 * a deliberately LIMITED summary (how many questions were wrong,
 * nothing diagnostic) plus the link to book a meeting with a teacher
 * for the full picture. No teacher review before this sends - a bare
 * wrong-answer count carries no judgment call to get wrong, unlike the
 * full Pathway recommendation, which still goes through the existing
 * draft/finalise/share workflow.
 */
class AssessmentResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Your child\'s assessment result — '.($this->data['student_name'] ?? ''))
            ->view('emails.assessment-result')
            ->with('data', $this->data);
    }
}
