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
 * portal:task-deadlines) sending two independent email reminders for each
 * booked assessment-follow-up meeting: one about a day before, one about
 * an hour before - each tracked by its own "sent" column so it only ever
 * fires once, however many times this command runs. Both carry the
 * meeting link and a one-tap "I'll be there" confirmation link.
 *
 * No automatic WhatsApp send here - there's no working Twilio WhatsApp
 * sender configured (and no plan to pay for one), so WhatsApp is a free
 * manual "click to open WhatsApp with this message ready" link instead
 * (see AssessmentLeadController::whatsappReminderLink()), sent from
 * whichever WhatsApp account is logged into the admin's own browser/
 * phone - not something a cron job can do on its own.
 */
class AssessmentMeetingReminderCron extends Command
{
    protected $signature = 'assessment:meeting-reminders';
    protected $description = 'Send the day-before and hour-before email reminders for booked assessment meetings';

    public function handle()
    {
        $leads = AssessmentLead::query()
            ->where('status', AssessmentLead::STATUS_BOOKED_MEETING)
            ->where(function ($q) {
                $q->whereNull('meeting_day_before_sent_at')->orWhereNull('meeting_reminder_sent_at');
            })
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

            if (! $lead->meeting_day_before_sent_at) {
                // A narrow 4-hour band (20h-24h before the meeting), not
                // "any time until the hour-before reminder takes over" -
                // a meeting booked with LESS than ~20 hours' notice (very
                // common for a next-day or same-day slot) would otherwise
                // still match "any time before start" and go out calling
                // a same-day meeting "tomorrow". Missing this band (e.g.
                // short notice, or a cron outage) just means this one
                // reminder is silently skipped, never sent with wrong
                // wording - the hour-before reminder still covers it.
                $this->maybeRemind($lead, $start, 'meeting_day_before_sent_at', $start->copy()->subHours(24), $start->copy()->subHours(20), 'tomorrow');
            }

            if (! $lead->meeting_reminder_sent_at) {
                // From an hour out until 30 minutes after start - covers a
                // short cron outage without sending a confusingly-late
                // "coming up" notice for a meeting long over.
                $this->maybeRemind($lead, $start, 'meeting_reminder_sent_at', $start->copy()->subHour(), $start->copy()->addMinutes(30), 'soon');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * $sentColumn is stamped the moment this lead enters the send window,
     * whether or not the email itself actually succeeds, and also when
     * the window was missed entirely (cron outage) - either way this
     * reminder is "handled" and must never be attempted again.
     */
    private function maybeRemind(AssessmentLead $lead, Carbon $start, string $sentColumn, Carbon $windowOpensAt, Carbon $windowClosesAt, string $timeframe): void
    {
        if (now()->lt($windowOpensAt)) {
            return; // not yet
        }

        $lead->{$sentColumn} = now();
        $lead->save();

        if (now()->gt($windowClosesAt)) {
            return; // window missed - stamped above so it's never retried, just not sent this late
        }

        $this->send($lead, $start, $timeframe);
    }

    private function send(AssessmentLead $lead, Carbon $start, string $timeframe): void
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
                'timeframe' => $timeframe, // 'tomorrow' or 'soon' - see the mail view
            ]));
        } catch (\Throwable $e) {
            Log::warning('Assessment meeting reminder email failed: '.$e->getMessage());
        }
    }
}
