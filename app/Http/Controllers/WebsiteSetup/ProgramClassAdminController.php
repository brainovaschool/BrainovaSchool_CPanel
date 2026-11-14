<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClass;
use App\Repositories\WebsiteSetup\ProgramClassRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Admin's final decision on a lesson a Coordinator sent on (or, since
 *  Admin sees/edits everything like the rest of this app, on one that's
 *  merely Submitted too) - decided inline from Course Details, the same
 *  page the teacher and coordinator already use. */
class ProgramClassAdminController extends Controller
{
    private $repo;

    public function __construct(ProgramClassRepository $repo)
    {
        $this->repo = $repo;
    }

    public function decide(Request $request, $id)
    {
        if ((int) Auth::user()?->role_id !== 1) {
            abort(403, 'Admin only.');
        }

        $request->validate([
            'decision' => 'required|in:approve,request_changes',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $class = ProgramClass::find($id);
        if (!$class) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }

        $adminStaffId = Auth::user()->staff?->id;
        $result = $this->repo->adminDecision($request, (int) $id, $adminStaffId);

        $message = $request->decision === 'request_changes'
            ? 'Sent back to the teacher with your feedback.'
            : 'Approved — now live on Course Details.';

        return redirect($this->repo->courseDetailsUrl($class->program))
            ->with($result['status'] ? 'success' : 'danger', $result['status'] ? $message : $result['message']);
    }
}
