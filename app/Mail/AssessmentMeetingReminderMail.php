<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The "your meeting is in about an hour" reminder - sent once per lead by
 * the assessment:meeting-reminders scheduled command (see
 * App\Console\Commands\AssessmentMeetingReminderCron). Carries the meeting
 * link (Google Meet or the Jitsi fallback - see MeetingBookingController)
 * and a one-tap, no-login "I'll be there" confirmation link so the admin
 * list can show whether a parent actually confirmed, not just that a
 * reminder went out.
 */
class AssessmentMeetingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Reminder: your meeting with the teacher is in about an hour')
            ->view('emails.assessment-meeting-reminder')
            ->with('data', $this->data);
    }
}
