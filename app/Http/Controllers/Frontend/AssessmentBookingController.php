<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\AssessmentSystemController;
use App\Http\Controllers\Controller;
use App\Mail\AssessmentInviteMail;
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
        // H10: honeypot — a real visitor never sees or fills this field, only a bot would.
        // Pretend success so the bot doesn't learn to try elsewhere, but never save it.
        if (trim((string) $request->input('website')) !== '') {
            return redirect()->route('frontend.book-assessment')
                ->with('message', 'Thanks! We\'ve received your booking — check your email for the assessment link.');
        }

        $request->validate([
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
            'assessment_slot_id'  => 'required|integer|exists:assessment_slots,id',
            'consent'             => 'required|accepted',
        ]);

        $slot = AssessmentSlot::find($request->assessment_slot_id);
        if (! $slot || $slot->remaining <= 0 || $slot->status != 1) {
            return back()->withInput()->with('error', 'Sorry — that time just filled up. Please pick another.');
        }
        $slot->increment('booked_count');

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
        $lead->assessment_slot_id = $slot->id;
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
                    'slot_label' => $slot->slot_date->format('D, d M Y').' · '.$slot->time_label,
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

        return redirect()->route('frontend.book-assessment')
            ->with('message', 'Thanks! We\'ve received your booking — check your email for the assessment link.');
    }
}
