<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClass;
use App\Repositories\WebsiteSetup\ProgramClassRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Coordinator's review queue for Program Classes - mirrors
 *  ClassContentCoordinatorController, but the decision is final here
 *  (approve or send back), no separate Admin approval tier. */
class ProgramClassCoordinatorController extends Controller
{
    private $repo;

    public function __construct(ProgramClassRepository $repo)
    {
        $this->repo = $repo;
    }

    private function coordinatorStaffId(): ?int
    {
        $user = Auth::user();
        if ($user && (int) $user->role_id !== 1 && $user->staff) {
            return (int) $user->staff->id;
        }
        return null;
    }

    public function index(Request $request)
    {
        $staffId = $this->coordinatorStaffId();
        $data['classes'] = $this->repo->forCoordinator($request->get('status'), $staffId);
        $data['status']  = $request->get('status');
        $data['counts']  = $this->repo->statusCounts(null, $staffId);
        $data['title']   = 'Course Lessons — Coordinator Review';
        return view('program-class.coordinator.index', compact('data'));
    }

    public function show($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('program-class-coordinator.index')->with('danger', ___('alert.not_found'));
        }
        if ($this->repo->outsideCoordinatorScope($data['item'], $this->coordinatorStaffId())) {
            abort(403, "This isn't one of your assigned teachers' submissions.");
        }
        $data['title'] = 'Review — ' . $data['item']->title;
        return view('program-class.coordinator.show', compact('data'));
    }

    public function decide(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:approve,request_changes',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $coordinatorStaffId = $this->coordinatorStaffId();

        $class = ProgramClass::find($id);
        if ($class && $this->repo->outsideCoordinatorScope($class, $coordinatorStaffId)) {
            abort(403, "This isn't one of your assigned teachers' submissions.");
        }

        $result = $this->repo->coordinatorDecision($request, (int) $id, $coordinatorStaffId);

        $message = $request->decision === 'request_changes'
            ? 'Sent back to the teacher with your feedback.'
            : 'Approved — now live on Course Details.';

        return redirect()->route('program-class-coordinator.index')
            ->with($result['status'] ? 'success' : 'danger', $result['status'] ? $message : $result['message']);
    }
}
