<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Portal\PortalSetting;
use App\Repositories\Portal\TaskRepository;

/** Task board — handoff spec phase 2. "Manager" actions (create, assign,
 *  reassign, review, approve) require portal_manage; any portal_access
 *  holder can work their own assigned tasks (accept, submit, comment).
 *  Every action re-checks ownership here, not just the sidebar/route gate —
 *  per the spec's own rule, hiding a button is never enough. */
class TaskController extends Controller
{
    private $repo;

    public function __construct(TaskRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    public function index(Request $request)
    {
        $data['tasks']     = $this->repo->forManager($request->only(['status', 'category', 'assigned_to', 'search']));
        $data['employeesList'] = \App\Models\Staff\Staff::orderBy('first_name')->get();
        $data['settings']  = PortalSetting::current();
        $data['filters']   = $request->only(['status', 'category', 'assigned_to', 'search']);
        $data['title']     = 'Team Portal — Tasks';
        return view('portal.tasks.index', compact('data'));
    }

    public function myTasks()
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return redirect()->route('portal-employees.index')->with('danger', 'No staff record is linked to your account, so there are no tasks to show here.');
        }

        $data['tasks'] = $this->repo->forEmployee($staffId);
        $data['title'] = 'My Tasks';
        return view('portal.tasks.my', compact('data'));
    }

    public function create()
    {
        $data['employeesList'] = \App\Models\Staff\Staff::with('role')->orderBy('first_name')->get();
        $data['settings']      = PortalSetting::current();
        $data['title']         = 'Assign a Task';
        return view('portal.tasks.create', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:150',
            'assigned_to' => 'required|exists:staff,id',
            'due_date'    => 'required|date',
            'priority'    => 'nullable|in:Low,Medium,High',
        ]);

        $result = $this->repo->store($request, Auth::id());

        if ($result['status']) {
            return redirect()->route('portal-tasks.show', $result['data']['id'])->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function show($id)
    {
        $task = $this->repo->show((int) $id);
        if (!$task) {
            return redirect()->route('portal-tasks.index')->with('danger', ___('alert.not_found'));
        }

        $isManager = hasPermission('portal_manage');
        $staffId   = $this->actingStaffId();
        if (!$isManager && (int) $task->assigned_to !== $staffId) {
            abort(403, "This isn't your task.");
        }

        $data['task']      = $task;
        $data['isManager']  = $isManager;
        $data['isAssignee'] = $staffId !== null && (int) $task->assigned_to === $staffId;
        $data['title']      = $task->title;
        return view('portal.tasks.show', compact('data'));
    }

    public function reassign(Request $request, $id)
    {
        $request->validate(['assigned_to' => 'required|exists:staff,id']);
        $result = $this->repo->reassign((int) $id, (int) $request->assigned_to);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function accept($id)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return back()->with('danger', "This isn't your task.");
        }
        $result = $this->repo->accept((int) $id, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function submit(Request $request, $id)
    {
        $request->validate([
            'link'    => 'nullable|string|max:500',
            'comment' => 'nullable|string|max:3000',
            'file'    => 'nullable|file|max:10240',
        ]);

        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return back()->with('danger', "This isn't your task.");
        }
        $result = $this->repo->submitWork((int) $id, $staffId, $request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function startReview($id)
    {
        $result = $this->repo->startReview((int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function requestRevision(Request $request, $id)
    {
        $request->validate(['comment' => 'required|string|max:3000']);
        $result = $this->repo->requestRevision((int) $id, Auth::id(), $request->comment);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'quality_score' => 'required|integer|min:0|max:5',
            'comment'       => 'nullable|string|max:3000',
        ]);
        $result = $this->repo->approve((int) $id, Auth::id(), (int) $request->quality_score, $request->comment);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function comment(Request $request, $id)
    {
        $request->validate(['body' => 'required|string|max:2000']);

        $task = $this->repo->show((int) $id);
        if (!$task) {
            return back()->with('danger', ___('alert.not_found'));
        }

        $isManager = hasPermission('portal_manage');
        $staffId   = $this->actingStaffId();
        if (!$isManager && (int) $task->assigned_to !== $staffId) {
            abort(403, "This isn't your task.");
        }

        $result = $this->repo->addComment((int) $id, Auth::id(), $request->body);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
