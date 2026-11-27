<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, the moment AssessmentMeetingReminderCron::maybeRelease()
 * frees up a slot because nobody confirmed by meeting_confirm_deadline -
 * the one email in this whole flow that was never promised in advance
 * (the others all said "this might happen"), so the parent needs to
 * actually be told it happened, with a fresh link to book another time
 * if they still want one.
 */
class AssessmentMeetingReleasedMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Your meeting slot was released — you can rebook')
            ->view('emails.assessment-meeting-released')
            ->with('data', $this->data);
    }
}
