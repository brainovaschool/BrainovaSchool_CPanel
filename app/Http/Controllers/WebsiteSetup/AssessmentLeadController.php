<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Mail\AssessmentInviteMail;
use App\Models\WebsiteSetup\AssessmentLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        return view('website-setup.assessment-lead.index', compact('data'));
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
