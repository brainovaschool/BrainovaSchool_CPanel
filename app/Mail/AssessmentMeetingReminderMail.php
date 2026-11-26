<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The "your meeting is coming up" reminder - sent twice per lead (a day
 * before, then again about an hour before) by the assessment:meeting-
 * reminders scheduled command (see
 * App\Console\Commands\AssessmentMeetingReminderCron), which passes
 * $data['timeframe'] as 'tomorrow' or 'soon' to pick the wording below.
 * Carries the meeting link (Google Meet or the Jitsi fallback - see
 * MeetingBookingController) and a one-tap, no-login "I'll be there"
 * confirmation link so the admin list can show whether a parent actually
 * confirmed, not just that a reminder went out.
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
        $subject = ($this->data['timeframe'] ?? 'soon') === 'tomorrow'
            ? 'Reminder: your meeting with the teacher is tomorrow'
            : 'Reminder: your meeting with the teacher is in about an hour';

        return $this->subject($subject)
            ->view('emails.assessment-meeting-reminder')
            ->with('data', $this->data);
    }
}
