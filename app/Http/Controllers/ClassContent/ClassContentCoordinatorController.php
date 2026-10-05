<?php

namespace App\Http\Controllers\ClassContent;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ClassContent\ClassContentModuleRepository;

/** Coordinator's review queue — checks a Teacher's submitted module in
 *  detail, then either sends it on to Admin or kicks it back with
 *  feedback. Reuses the AdminPanel backend shell like everything else in
 *  Class Content (see ClassContentModuleController for why). */
class ClassContentCoordinatorController extends Controller
{
    private $repo;

    public function __construct(ClassContentModuleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $data['modules'] = $this->repo->forCoordinator($request->get('status'));
        $data['status']  = $request->get('status');
        $data['title']   = 'Coordinator Review';
        return view('class-content.coordinator.index', compact('data'));
    }

    public function show($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('class-content-coordinator.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Review — ' . $data['item']->title;
        return view('class-content.coordinator.show', compact('data'));
    }

    public function decide(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:send_to_admin,request_changes',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $coordinatorStaffId = Auth::user()->staff?->id;
        $result = $this->repo->coordinatorDecision($request, (int) $id, (int) $coordinatorStaffId);

        $message = $request->decision === 'request_changes'
            ? 'Sent back to the teacher with your feedback.'
            : 'Sent on to admin for approval.';

        return redirect()->route('class-content-coordinator.index')
            ->with($result['status'] ? 'success' : 'danger', $result['status'] ? $message : $result['message']);
    }
}
