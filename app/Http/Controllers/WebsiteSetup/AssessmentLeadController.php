<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Enums\RoleEnum;
use App\Http\Controllers\AssessmentSystemController;
use App\Http\Controllers\Controller;
use App\Mail\AssessmentInviteMail;
use App\Models\Setting;
use App\Models\WebsiteSetup\AssessmentLead;
use App\Models\WebsiteSetup\AssessmentSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

// Read-only triage list for assessment leads - a lead is never
// hand-edited here, only created by the public booking form. Same
// simple admin/superadmin role gate as AssessmentSlotController, for
// the same reason (part of Assessment System administration).
class AssessmentLeadController extends Controller
{
    private function guard(Request $request)
    {
        if (! in_array((int) $request->user()?->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN], true)) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->guard($request);

        $query = AssessmentLead::query()->with('slot')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $data['leads'] = $query->paginate(25)->withQueryString();
        $data['statuses'] = AssessmentLead::STATUS_LABELS;
        $data['title'] = 'Assessment Leads';

        // A direct "view this child's report" link for admin/teacher,
        // reusing the exact same signed-token scheme a logged-in staff
        // member gets from AssessmentSystemController::launch() - just
        // with an extra 'vs' (view student) param the assessment app's
        // identity.js reads once at boot to land straight on that
        // student's own history instead of the dashboard. Only shown
        // once a student row actually exists over there (i.e. the
        // invite link has gone out at least once).
        foreach ($data['leads'] as $row) {
            $row->report_link = $row->assessment_system_student_id
                ? $this->reportLink($request, $row->assessment_system_student_id)
                : null;
            $row->whatsapp_reminder_link = $this->whatsappReminderLink($row);
        }

        $data['calendarConnected'] = (new \App\Services\GoogleCalendarClient())->isConfigured();
        $data['calendarClientId'] = setting('assessment_calendar_client_id') ?: setting('ai_helper_drive_client_id');
        $data['calendarClientSecret'] = setting('assessment_calendar_client_secret') ?: setting('ai_helper_drive_client_secret');

        return view('website-setup.assessment-lead.index', compact('data'));
    }

    private function reportLink(Request $request, string $assessmentSystemStudentId): ?string
    {
        $claims = [
            'uid' => $request->user()->id,
            'role' => 'admin',
            'name' => $request->user()->name,
            'exp' => now()->addHours(3)->timestamp,
        ];

        $link = AssessmentSystemController::buildAssessmentLink($claims, $request);

        return $link ? $link.'&vs='.urlencode($assessmentSystemStudentId) : null;
    }

    /**
     * A free, zero-setup stand-in for automatic WhatsApp sending (no
     * working Twilio account - see the 2026-11-26 WhatsApp send failure
     * and the decision not to pay for one): a wa.me link pre-filled with
     * the exact same reminder wording the email gets, so the admin can
     * open it and send it themselves with one click and one tap, same
     * message as if it had gone out automatically. Only offered once a
     * meeting is actually booked and there's a number to message.
     */
    private function whatsappReminderLink(AssessmentLead $lead): ?string
    {
        if ($lead->status !== AssessmentLead::STATUS_BOOKED_MEETING || ! $lead->slot || ! $lead->whatsapp_number) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d g:i A', $lead->slot->slot_date->format('Y-m-d').' '.$lead->slot->start_time);
        } catch (\Throwable $e) {
            return null;
        }

        $slotLabel = $start->format('D, d M Y').' · '.$lead->slot->time_label;
        $meetingLink = $lead->meeting_link ?: 'https://meet.jit.si/Brainova-Meeting-'.$lead->id;
        $confirmLink = URL::signedRoute('frontend.confirm-meeting', ['lead' => $lead->id], now()->addDays(2));

        $body = "Reminder: your meeting with the teacher about {$lead->student_name}'s assessment is at {$slotLabel}.\n".
            "Join: {$meetingLink}\n".
            "Please confirm you'll be there: {$confirmLink}";

        return 'https://wa.me/'.$this->waDigits($lead->whatsapp_number).'?text='.urlencode($body);
    }

    /** Pakistan-specific local-to-international normalisation (every
     *  number on this list so far is a Pakistani number, local or with
     *  country code) - wa.me needs full digits with country code and no
     *  leading zero, unlike how these numbers are typed on the booking
     *  form. */
    private function waDigits(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if (str_starts_with($digits, '0')) {
            return '92'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '92') && strlen($digits) === 10) {
            return '92'.$digits;
        }

        return $digits;
    }

    /** Saves the Google Cloud OAuth client credentials (Calendar API scope) -
     *  the "Connect Google Calendar" button right after this needs them
     *  saved first, same two-step flow as the existing Drive integration
     *  (AiHelperController::driveConnect()/driveCallback()). */
    public function calendarSettings(Request $request)
    {
        $this->guard($request);

        $request->validate([
            'assessment_calendar_client_id' => 'required|string',
            'assessment_calendar_client_secret' => 'required|string',
        ]);

        Setting::updateOrCreate(['name' => 'assessment_calendar_client_id'], ['value' => trim($request->input('assessment_calendar_client_id'))]);
        Setting::updateOrCreate(['name' => 'assessment_calendar_client_secret'], ['value' => trim($request->input('assessment_calendar_client_secret'))]);

        return back()->with('success', 'Saved. Now click "Connect Google Calendar" below to finish.');
    }

    // Sends the admin to Google's own consent screen. access_type=offline +
    // prompt=consent are both required to actually get a refresh_token back.
    public function calendarConnect(Request $request)
    {
        $this->guard($request);

        $clientId = trim((string) setting('assessment_calendar_client_id'));
        if (! $clientId) {
            return back()->with('danger', 'Add the Google Client ID and Client Secret first, then save, before connecting.');
        }

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => route('assessment-lead.calendar-callback'),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'scope' => 'https://www.googleapis.com/auth/calendar.events',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$params);
    }

    public function calendarCallback(Request $request)
    {
        $this->guard($request);

        if ($request->has('error')) {
            return redirect()->route('assessment-lead.index')->with('danger', 'Google Calendar connection was not completed: '.$request->input('error'));
        }

        $code = $request->input('code');
        if (! $code) {
            return redirect()->route('assessment-lead.index')->with('danger', 'Google did not return an authorization code. Please try connecting again.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => trim((string) setting('assessment_calendar_client_id')),
            'client_secret' => trim((string) setting('assessment_calendar_client_secret')),
            'redirect_uri' => route('assessment-lead.calendar-callback'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful() || ! $response->json('refresh_token')) {
            return redirect()->route('assessment-lead.index')->with('danger', 'Google Calendar did not return a long-term access token. Try disconnecting any previous access at myaccount.google.com/permissions and connecting again.');
        }

        Setting::updateOrCreate(['name' => 'assessment_calendar_refresh_token'], ['value' => $response->json('refresh_token')]);

        return redirect()->route('assessment-lead.index')->with('success', 'Google Calendar connected — new meeting bookings will get a real Google Meet link.');
    }

    public function calendarDisconnect(Request $request)
    {
        $this->guard($request);

        Setting::updateOrCreate(['name' => 'assessment_calendar_refresh_token'], ['value' => null]);

        return back()->with('success', 'Google Calendar disconnected. New bookings will fall back to a Jitsi video link.');
    }

    /**
     * A lead is never hand-edited (see the class comment), but it is a
     * valid thing to clean up - test/duplicate bookings, or a parent who
     * asked to be removed. Freeing the seat(s) a deleted lead held is
     * the one side effect that matters here: otherwise a slot's
     * booked_count stays inflated forever and understates how many
     * seats are actually still open, exactly the same bug the "can't
     * delete a booked slot" guard in AssessmentSlotRepository exists to
     * avoid from the other direction.
     */
    public function bulkDelete(Request $request)
    {
        $this->guard($request);

        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }

        $leads = AssessmentLead::whereIn('id', $ids)->get();

        $leads->filter(fn ($lead) => $lead->assessment_slot_id)
            ->groupBy('assessment_slot_id')
            ->each(function ($group, $slotId) {
                AssessmentSlot::where('id', $slotId)
                    ->update(['booked_count' => DB::raw('GREATEST(booked_count - '.(int) $group->count().', 0)')]);
            });

        AssessmentLead::whereIn('id', $ids)->delete();

        return response()->json([count($ids).' lead(s) deleted.', 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    public function resend(Request $request, $id)
    {
        $this->guard($request);

        $lead = AssessmentLead::findOrFail($id);

        if (! $lead->assessment_link) {
            return back()->with('danger', 'This lead has no assessment link yet to resend.');
        }

        try {
            Mail::to($lead->email)->send(new AssessmentInviteMail([
                'guardian_name' => $lead->guardian_name,
                'student_name' => $lead->student_name,
                'slot_label' => optional($lead->slot)->slot_date?->format('D, d M Y').' · '.optional($lead->slot)->time_label,
                'subject' => $lead->subject,
                'grade' => 'Grade '.$lead->grade,
                'assessment_link' => $lead->assessment_link,
            ]));
            $lead->link_sent_at = now();
            if ($lead->status === AssessmentLead::STATUS_NEW) {
                $lead->status = AssessmentLead::STATUS_INVITED;
            }
            $lead->save();

            return back()->with('success', 'Assessment link resent to '.$lead->email.'.');
        } catch (\Throwable $e) {
            Log::warning('Assessment lead resend failed: '.$e->getMessage());
            return back()->with('danger', 'Could not send the email - please try again shortly.');
        }
    }
}
