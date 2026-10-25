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

    /** portal_manage alone isn't enough to review a task you're also the
     *  assignee of - a coordinator who holds portal_manage to review her
     *  content creators must not be able to approve her own submitted
     *  work, even though she technically has the permission. */
    private function isReviewingOwnTask(int $id): bool
    {
        $task = $this->repo->show($id);
        $staffId = $this->actingStaffId();
        return $task && $staffId !== null && (int) $task->assigned_to === $staffId;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'category', 'assigned_to', 'search', 'show']);
        $data['tasks']     = $this->repo->forManager($filters);
        $data['employeesList'] = \App\Models\Staff\Staff::orderBy('first_name')->get();
        $data['settings']  = PortalSetting::current();
        $data['filters']   = $filters;
        $data['title']     = 'Team Portal — Tasks';
        return view('portal.tasks.index', compact('data'));
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }

        $result = $this->repo->bulkDelete($ids);
        if ($result['deleted'] === 0 && $result['blocked'] === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }

        $message = $result['deleted'] . ' task(s) deleted.';
        if ($result['blocked'] > 0) {
            $message .= ' ' . $result['blocked'] . ' paid task(s) with a payment record were skipped.';
        }
        return response()->json([$message, 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }

        $result = $this->repo->bulkArchive($ids, (bool) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkReassign(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $request->validate(['assigned_to' => 'required|exists:staff,id']);

        $result = $this->repo->bulkReassign($ids, (int) $request->assigned_to);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
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
        // A normal task always needs an assignee; a paid task may be left
        // unassigned on purpose, to sit Open for anyone to claim.
        $assignedToRule = $request->boolean('paid') ? 'nullable' : 'required';

        $request->validate([
            'title'       => 'required|string|max:150',
            'assigned_to' => $assignedToRule . '|exists:staff,id',
            'amount'      => 'required_if:paid,1|nullable|numeric|min:0',
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
        // An Open paid task has no assignee yet — anyone needs to be able
        // to view it in order to claim it, so that case alone bypasses
        // the "is this your task" check below.
        $isOpenForClaim = $task->paid && $task->status === \App\Models\Portal\PortalTask::OPEN;
        if (!$isManager && !$isOpenForClaim && (int) $task->assigned_to !== $staffId) {
            abort(403, "This isn't your task.");
        }

        $data['task']      = $task;
        $data['isManager']  = $isManager;
        $data['isAssignee'] = $staffId !== null && (int) $task->assigned_to === $staffId;
        // Reassigning is narrower than general manage access — only the
        // person who actually created/assigned this task (or the main
        // admin) can hand it to someone else, not any manager.
        $data['isCreator']  = $isManager && ((int) $task->assigned_by === (int) Auth::id() || (int) Auth::user()->role_id === 1);
        $data['canClaim']   = $task->paid && $task->status === \App\Models\Portal\PortalTask::OPEN && $staffId !== null;
        $data['claimBlockers'] = $data['canClaim'] ? $this->repo->claimBlockers($staffId) : collect();
        $data['canMarkPaid'] = $isManager && $task->paid && $task->status === \App\Models\Portal\PortalTask::COMPLETED && $task->pay_status === 'due';
        $data['title']      = $task->title;
        return view('portal.tasks.show', compact('data'));
    }

    public function claim($id)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return back()->with('danger', 'No staff record is linked to your account.');
        }

        $result = $this->repo->claim((int) $id, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function markPaid($id)
    {
        $result = $this->repo->markPaid((int) $id, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function paidTasks()
    {
        $isManager = hasPermission('portal_manage');
        $staffId   = $this->actingStaffId();

        $data['isManager'] = $isManager;
        $data['openTasks'] = \App\Models\Portal\PortalTask::where('paid', true)->where('status', \App\Models\Portal\PortalTask::OPEN)->get();

        if ($isManager) {
            $data['claimedTasks']   = \App\Models\Portal\PortalTask::where('paid', true)
                ->whereNotIn('status', [\App\Models\Portal\PortalTask::OPEN, \App\Models\Portal\PortalTask::COMPLETED])
                ->with('assignee')->get();
            $data['completedTasks'] = \App\Models\Portal\PortalTask::where('paid', true)->where('status', \App\Models\Portal\PortalTask::COMPLETED)
                ->with(['assignee', 'payout'])->get();
        } else {
            $data['claimBlockers'] = $staffId ? $this->repo->claimBlockers($staffId) : collect();
            $data['myPaidTasks']   = $staffId ? \App\Models\Portal\PortalTask::where('paid', true)->where('assigned_to', $staffId)->get() : collect();
        }

        $data['title'] = 'Paid Tasks';
        return view('portal.tasks.paid', compact('data'));
    }

    public function reassign(Request $request, $id)
    {
        $request->validate(['assigned_to' => 'required|exists:staff,id']);

        $task = $this->repo->show((int) $id);
        if (!$task) {
            return back()->with('danger', ___('alert.not_found'));
        }

        // Mirrors show()'s $isCreator — reassigning is only for whoever
        // created/assigned this specific task (or the main admin), not
        // every portal_manage holder. The UI already hides this for anyone
        // else; this is the check that actually matters.
        $isCreator = (int) $task->assigned_by === (int) Auth::id() || (int) Auth::user()->role_id === 1;
        if (!$isCreator) {
            abort(403, 'Only the person who created this task can reassign it.');
        }

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
        if ($this->isReviewingOwnTask((int) $id)) {
            abort(403, "You can't review your own task.");
        }
        $result = $this->repo->startReview((int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function requestRevision(Request $request, $id)
    {
        if ($this->isReviewingOwnTask((int) $id)) {
            abort(403, "You can't review your own task.");
        }
        $request->validate(['comment' => 'required|string|max:3000']);
        $result = $this->repo->requestRevision((int) $id, Auth::id(), $request->comment);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function approve(Request $request, $id)
    {
        if ($this->isReviewingOwnTask((int) $id)) {
            abort(403, "You can't review your own task.");
        }
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
