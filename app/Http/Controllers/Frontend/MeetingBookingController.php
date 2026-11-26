<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\AssessmentLead;
use App\Models\WebsiteSetup\AssessmentSlot;
use App\Services\GoogleCalendarClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The "book a meeting with the teacher" step a lead's parent reaches
 * from their result email - identity already known from the signed
 * link itself (see 'signed' route middleware), so this never re-asks
 * guardian/student/grade/syllabus/subject/email/whatsapp/city/consent,
 * only a date/time and an optional note. Updates the existing
 * AssessmentLead row; never creates a new one.
 */
class MeetingBookingController extends Controller
{
    public function show(Request $request, AssessmentLead $lead)
    {
        $slots = AssessmentSlot::query()->active()
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->orderBy('slot_date')->orderBy('start_time')
            ->get()
            ->filter(fn ($s) => $s->remaining > 0);

        $data['slotsByDate'] = $slots->groupBy(fn ($s) => $s->slot_date->format('Y-m-d'))
            ->map(fn ($group) => $group->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->time_label,
                'remaining' => $s->remaining,
            ])->values())
            ->toArray();

        $data['availableDates'] = array_keys($data['slotsByDate']);

        return view('frontend.book-meeting', compact('data', 'lead'));
    }

    public function store(Request $request, AssessmentLead $lead)
    {
        $request->validate([
            'assessment_slot_id' => 'required|integer|exists:assessment_slots,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $slot = AssessmentSlot::find($request->assessment_slot_id);
        if (! $slot || $slot->remaining <= 0 || $slot->status != 1) {
            return back()->withInput()->with('error', 'Sorry — that time just filled up. Please pick another.');
        }
        $slot->increment('booked_count');

        $lead->assessment_slot_id = $slot->id;
        if ($request->filled('message')) {
            $lead->message = trim(($lead->message ? $lead->message."\n\n" : '').$request->input('message'));
        }
        $lead->status = AssessmentLead::STATUS_BOOKED_MEETING;
        $lead->meeting_link = $this->buildMeetingLink($lead, $slot);
        $lead->save();

        // Re-use the current (already-validated) signed URL rather than
        // route('frontend.book-meeting', ...) without a signature, which
        // the 'signed' middleware would reject on the next load.
        return redirect($request->fullUrl())
            ->with('message', 'Thanks! Your meeting is booked — we\'ll be in touch.');
    }

    /**
     * The one-tap "I'll be there" link sent in the 1-hour-before reminder
     * (see App\Console\Commands\AssessmentMeetingReminderCron). No login,
     * identity carried by the signature alone, same as show()/store()
     * above - this only ever records a confirmation, never cancels or
     * changes anything else about the booking.
     */
    public function confirm(Request $request, AssessmentLead $lead)
    {
        if (! $lead->meeting_confirmed_at) {
            $lead->meeting_confirmed_at = now();
            $lead->save();
        }

        return view('frontend.book-meeting-confirmed', compact('lead'));
    }

    /**
     * A real Google Meet link when Calendar is connected (see
     * AssessmentLeadController::calendarConnect()); otherwise a free,
     * no-login Jitsi room keyed off the lead's own id, so a meeting link
     * always exists from the moment a slot is booked, same as it would
     * once Calendar is connected - nothing downstream has to handle "no
     * link yet" as a separate case.
     */
    private function buildMeetingLink(AssessmentLead $lead, AssessmentSlot $slot): string
    {
        try {
            $start = Carbon::createFromFormat('Y-m-d g:i A', $slot->slot_date->format('Y-m-d').' '.$slot->start_time);
            $end = $slot->end_time
                ? Carbon::createFromFormat('Y-m-d g:i A', $slot->slot_date->format('Y-m-d').' '.$slot->end_time)
                : $start->copy()->addMinutes(15);

            $calendar = new GoogleCalendarClient();
            if ($calendar->isConfigured()) {
                $event = $calendar->createMeetEvent(
                    'Brainova — meeting with '.$lead->guardian_name,
                    'Assessment follow-up meeting for '.$lead->student_name.' (Grade '.$lead->grade.').',
                    $start,
                    $end
                );
                if (!empty($event['meetLink'])) {
                    return $event['meetLink'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Google Meet link generation failed: '.$e->getMessage());
        }

        return 'https://meet.jit.si/Brainova-Meeting-'.$lead->id;
    }
}
