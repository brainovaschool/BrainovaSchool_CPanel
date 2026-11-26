<?php

namespace App\Console\Commands;

use App\Mail\AssessmentMeetingReminderMail;
use App\Models\WebsiteSetup\AssessmentLead;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Runs every minute (same pattern as the existing attendance:cron /
 * portal:task-deadlines) looking for a booked assessment-follow-up
 * meeting about an hour away that hasn't been reminded yet. Sends one
 * email (always) and attempts one WhatsApp message (best-effort - see
 * send_whatsapp_twillo in common-helpers.php), each carrying the meeting
 * link and a one-tap "I'll be there" confirmation link. Every lead is
 * only ever reminded once, however many times this fires, because
 * meeting_reminder_sent_at is stamped the moment a lead is handled -
 * whether or not the send itself actually succeeded, since a flaky mail/
 * WhatsApp provider should never turn into a reminder repeated every
 * minute forever.
 */
class AssessmentMeetingReminderCron extends Command
{
    protected $signature = 'assessment:meeting-reminders';
    protected $description = 'Send the 1-hour-before reminder + confirmation link for booked assessment meetings';

    /** How late this is still allowed to send a "coming up" reminder for
     *  a meeting that's already started - covers a cron outage without
     *  sending a confusingly-late notice for a meeting long over. */
    private const GRACE_MINUTES_AFTER_START = 30;

    public function handle()
    {
        $leads = AssessmentLead::query()
            ->where('status', AssessmentLead::STATUS_BOOKED_MEETING)
            ->whereNull('meeting_reminder_sent_at')
            ->with('slot')
            ->get();

        foreach ($leads as $lead) {
            $slot = $lead->slot;
            if (! $slot) {
                continue;
            }

            try {
                $start = Carbon::createFromFormat('Y-m-d g:i A', $slot->slot_date->format('Y-m-d').' '.$slot->start_time);
            } catch (\Throwable $e) {
                continue;
            }

            $remindAt = $start->copy()->subHour();
            if (now()->lt($remindAt)) {
                continue; // more than an hour away still - not yet
            }

            // From here on this lead is considered handled either way,
            // so a flaky mail/WhatsApp send never causes a retry loop.
            $lead->meeting_reminder_sent_at = now();
            $lead->save();

            if (now()->gt($start->copy()->addMinutes(self::GRACE_MINUTES_AFTER_START))) {
                continue; // too late to usefully remind - meeting's already well underway
            }

            $this->send($lead, $start);
        }

        return Command::SUCCESS;
    }

    private function send(AssessmentLead $lead, Carbon $start): void
    {
        $slot = $lead->slot;
        $slotLabel = $start->format('D, d M Y').' · '.$slot->time_label;
        $confirmLink = URL::signedRoute('frontend.confirm-meeting', ['lead' => $lead->id], now()->addDays(2));
        $meetingLink = $lead->meeting_link ?: 'https://meet.jit.si/Brainova-Meeting-'.$lead->id;

        try {
            Mail::to($lead->email)->send(new AssessmentMeetingReminderMail([
                'guardian_name' => $lead->guardian_name,
                'student_name' => $lead->student_name,
                'slot_label' => $slotLabel,
                'meeting_link' => $meetingLink,
                'confirm_link' => $confirmLink,
            ]));
        } catch (\Throwable $e) {
            Log::warning('Assessment meeting reminder email failed: '.$e->getMessage());
        }

        if ($lead->whatsapp_number) {
            $body = "Reminder: your meeting with the teacher about {$lead->student_name}'s assessment is at {$slotLabel}.\n".
                "Join: {$meetingLink}\n".
                "Please confirm you'll be there: {$confirmLink}";

            $result = send_whatsapp_twillo($body, $lead->whatsapp_number);
            if (! $result['ok']) {
                Log::info('Assessment meeting WhatsApp reminder not delivered for lead '.$lead->id.': '.$result['error']);
            }
        }
    }
}
