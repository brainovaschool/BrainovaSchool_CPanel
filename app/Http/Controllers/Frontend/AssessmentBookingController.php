<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\AssessmentSystemController;
use App\Http\Controllers\Controller;
use App\Mail\AssessmentInviteMail;
use App\Mail\AssessmentResultMail;
use App\Models\WebsiteSetup\AssessmentLead;
use App\Models\WebsiteSetup\AssessmentSlot;
use App\Services\AssessmentSheetClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Public "book an assessment" page: a visitor who is NOT a registered
 * Student/User books a slot, and - with no admin ever touching a
 * keyboard - gets a signed link straight into the standalone Assessment
 * System, already auto-assigned to a matching published assessment by
 * grade. See AssessmentSheetClient for the Sheet read/write side and
 * AssessmentSystemController::buildAssessmentLink() for the signed-link
 * side (same scheme the LMS itself uses for a logged-in user, just with
 * a LEAD-{id} pseudo id instead of a real Laravel user).
 */
class AssessmentBookingController extends Controller
{
    const SUBJECTS = ['Mathematics', 'English', 'Science', 'Computer Science', 'Graphic Design', 'Urdu', 'Artificial Intelligence', 'Robotics'];
    const SYLLABI = ['SNC', 'Cambridge', 'Oxford', 'Pearson Edexcel', 'Other'];

    public function show()
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

        return view('frontend.book-assessment', compact('data'));
    }

    public function store(Request $request)
    {
        if ($this->isHoneypotTripped($request)) {
            return redirect()->route('frontend.book-assessment')
                ->with('message', 'Thanks! We\'ve received your booking — check your email for the assessment link.');
        }

        $request->validate($this->validationRules() + [
            'assessment_slot_id'  => 'required|integer|exists:assessment_slots,id',
        ]);

        $slot = AssessmentSlot::find($request->assessment_slot_id);
        if (! $slot || $slot->remaining <= 0 || $slot->status != 1) {
            return back()->withInput()->with('error', 'Sorry — that time just filled up. Please pick another.');
        }
        $slot->increment('booked_count');

        $this->createLeadAndSend($request, $slot);

        return redirect()->route('frontend.book-assessment')
            ->with('message', 'Thanks! We\'ve received your booking — check your email for the assessment link.');
    }

    /**
     * Simple registration - no slot/calendar at all. The assessment is
     * taken whenever the parent opens the emailed link, not at a
     * scheduled time - slot-booking belongs to a LATER step (booking a
     * meeting with the teacher once a result exists), not this one.
     */
    public function registerShow()
    {
        return view('frontend.assessment-register');
    }

    public function registerStore(Request $request)
    {
        if ($this->isHoneypotTripped($request)) {
            return redirect()->route('frontend.register-assessment')
                ->with('message', 'Thanks! Check your email for the assessment link.');
        }

        $request->validate($this->validationRules());

        $this->createLeadAndSend($request, null);

        return redirect()->route('frontend.register-assessment')
            ->with('message', 'Thanks! Check your email for the assessment link.');
    }

    /**
     * Same-origin ping the Assessment System's own JS fires the instant
     * ANY attempt is submitted (see views_run.js's submitRun()) - for a
     * real LMS student this is a pure no-op (no matching lead row), so
     * the Assessment System itself never needs to know or care what a
     * "lead" even is. For a lead, sends the limited (wrong-count only)
     * result email with the teacher-meeting booking link - fully
     * automatic, no teacher review, since a bare count carries no
     * judgment call to get wrong. Idempotent: a retried/duplicate ping
     * can never send a second email.
     */
    public function completed(Request $request)
    {
        $uid = (string) $request->query('uid', '');

        if (! str_starts_with($uid, 'LEAD-')) {
            return response()->json(['ok' => true]);
        }

        $lead = AssessmentLead::find((int) substr($uid, 5));
        if (! $lead || $lead->assessment_system_student_id !== $uid) {
            return response()->json(['ok' => true]);
        }

        if (in_array($lead->status, [AssessmentLead::STATUS_REPORTED, AssessmentLead::STATUS_BOOKED_MEETING, AssessmentLead::STATUS_ENROLLED], true)) {
            return response()->json(['ok' => true]); // already reported - never send twice
        }

        $lead->status = AssessmentLead::STATUS_TESTED;
        $lead->save();

        $total = max(0, (int) $request->query('total', 0));
        $correct = max(0, min($total, (int) $request->query('correct', 0)));
        $wrong = $total - $correct;

        try {
            Mail::to($lead->email)->send(new AssessmentResultMail([
                'guardian_name' => $lead->guardian_name,
                'student_name' => $lead->student_name,
                'wrong' => $wrong,
                'total' => $total,
                'booking_link' => route('frontend.book-assessment'),
            ]));
            $lead->status = AssessmentLead::STATUS_REPORTED;
            $lead->save();
        } catch (\Throwable $e) {
            Log::warning('Assessment result email failed: '.$e->getMessage());
            // stays at STATUS_TESTED - visible on the leads list as "needs attention"
        }

        return response()->json(['ok' => true]);
    }

    private function isHoneypotTripped(Request $request): bool
    {
        // H10: honeypot — a real visitor never sees or fills this field, only a bot would.
        // Caller still treats this as a normal success so the bot doesn't learn to try
        // elsewhere, it just never actually saves/sends anything.
        return trim((string) $request->input('website')) !== '';
    }

    private function validationRules(): array
    {
        return [
            'guardian_name'       => 'required|string|max:120',
            'student_name'        => 'required|string|max:120',
            'student_age'         => 'nullable|string|max:40',
            'grade'               => 'required|integer|between:1,10',
            'syllabus'            => ['required', 'string', Rule::in(self::SYLLABI)],
            'subject'             => ['required', 'string', Rule::in(self::SUBJECTS)],
            'email'               => 'required|email|max:150',
            'whatsapp_number'     => 'required|string|max:40',
            'city'                => 'nullable|string|max:100',
            'message'             => 'nullable|string|max:2000',
            'consent'             => 'required|accepted',
        ];
    }

    /**
     * Shared by both entry points: creates the lead row, auto-assigns a
     * published assessment by grade (see AssessmentSheetClient), builds
     * the signed anonymous link, and emails it - a mail/Sheet failure
     * never blocks the visitor's own confirmation, only gets logged.
     */
    private function createLeadAndSend(Request $request, ?AssessmentSlot $slot): AssessmentLead
    {
        $lead = new AssessmentLead();
        $lead->guardian_name = $request->guardian_name;
        $lead->student_name = $request->student_name;
        $lead->student_age = $request->student_age;
        $lead->grade = (int) $request->grade;
        $lead->syllabus = $request->syllabus;
        $lead->subject = $request->subject;
        $lead->email = $request->email;
        $lead->whatsapp_number = $request->whatsapp_number;
        $lead->city = $request->city;
        $lead->message = $request->message;
        $lead->assessment_slot_id = $slot?->id;
        $lead->status = AssessmentLead::STATUS_NEW;
        $lead->save();

        $lead->assessment_system_student_id = 'LEAD-'.$lead->id;

        // Auto-assign to a published assessment matching the grade (grade
        // only, for now - see the Sheet client for why, and the plan for
        // the grade+subject upgrade once more subject-specific assessments
        // exist). None found is never a hard failure: the lead still gets
        // created and emailed, just without an assigned assessment yet -
        // visible to admin as needing attention on the leads list.
        try {
            $sheet = new AssessmentSheetClient();
            $assessments = $sheet->fetchPublishedAssessments();
            $match = collect($assessments)->first(function ($a) use ($lead) {
                $grades = array_map('intval', $a['grades'] ?? []);
                return in_array($lead->grade, $grades, true);
            });

            if ($sheet->assignLead($lead->assessment_system_student_id, $lead->student_name, $lead->grade, $match)) {
                if ($match) {
                    $lead->assigned_assessment_id = $match['id'] ?? null;
                    $lead->assigned_assessment_title = $match['title'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Assessment auto-assignment failed: '.$e->getMessage());
        }

        $claims = [
            'uid' => $lead->assessment_system_student_id,
            'role' => 'student',
            'name' => $lead->student_name,
            'exp' => now()->addDays(7)->timestamp,
        ];
        $link = AssessmentSystemController::buildAssessmentLink($claims, $request);
        $lead->assessment_link = $link;

        if ($link) {
            try {
                Mail::to($lead->email)->send(new AssessmentInviteMail([
                    'guardian_name' => $lead->guardian_name,
                    'student_name' => $lead->student_name,
                    'slot_label' => $slot
                        ? $slot->slot_date->format('D, d M Y').' · '.$slot->time_label
                        : 'Whenever you\'re ready',
                    'subject' => $lead->subject,
                    'grade' => 'Grade '.$lead->grade,
                    'assessment_link' => $link,
                ]));
                $lead->link_sent_at = now();
                $lead->status = AssessmentLead::STATUS_INVITED;
            } catch (\Throwable $e) {
                Log::warning('Assessment invite email failed: '.$e->getMessage());
            }
        }

        $lead->save();

        return $lead;
    }
}
