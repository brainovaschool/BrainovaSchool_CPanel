<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The "your meeting with the teacher" email - sent three times per lead:
 * immediately at booking (MeetingBookingController::store()), again
 * about a day before, and again about an hour before (the latter two by
 * the assessment:meeting-reminders scheduled command - see
 * App\Console\Commands\AssessmentMeetingReminderCron). $data['timeframe']
 * is 'justBooked', 'tomorrow' or 'soon' and picks the wording below.
 * Carries the meeting link (Google Meet or the Jitsi fallback - see
 * MeetingBookingController) and a one-tap, no-login "I'll be there"
 * confirmation link so the admin list can show whether a parent actually
 * confirmed, not just that a reminder went out - every version repeats
 * the same 24-hour confirm-or-release policy, since whichever one a
 * parent actually reads should tell them the same thing.
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
        $subject = match ($this->data['timeframe'] ?? 'soon') {
            'justBooked' => 'Your meeting with the teacher is booked — please confirm',
            'tomorrow' => 'Reminder: your meeting with the teacher is tomorrow',
            default => 'Reminder: your meeting with the teacher is in about an hour',
        };

        return $this->subject($subject)
            ->view('emails.assessment-meeting-reminder')
            ->with('data', $this->data);
    }
}
