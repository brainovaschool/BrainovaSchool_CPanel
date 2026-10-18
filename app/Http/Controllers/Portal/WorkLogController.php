<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Portal\PortalSetting;
use App\Models\Portal\PortalTask;
use App\Models\Portal\PortalWorkLogEntry;
use App\Models\Staff\Staff;
use App\Repositories\Portal\WorkLogRepository;

class WorkLogController extends Controller
{
    private $repo;

    public function __construct(WorkLogRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    public function myLog(Request $request)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return redirect()->route('portal-employees.index')->with('danger', 'No staff record is linked to your account, so there\'s no work log here.');
        }

        $date = $request->filled('date') ? $request->date : now()->format('Y-m-d');

        $data['date']     = $date;
        $data['settings'] = PortalSetting::current();
        $data['slots']    = $data['settings']->daySlots();
        $data['entries']    = $this->repo->entriesForDay($staffId, $date);
        $data['isSubmitted'] = $this->repo->isSubmitted($staffId, $date);
        $data['isLocked']   = $this->repo->isLocked($staffId, $date);
        $data['myTasks']  = PortalTask::where('assigned_to', $staffId)->whereNotIn('status', [PortalTask::COMPLETED])->get();
        $data['history']  = $this->repo->history($staffId);
        $data['title']    = 'My Work Log';
        return view('portal.work-log.my', compact('data'));
    }

    public function storeEntry(Request $request)
    {
        $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required',
            'end_time'   => 'required',
            'task_id'    => 'nullable',
            'activity'   => 'nullable|string|max:150',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return back()->with('danger', ___('alert.not_found'));
        }

        $result = $this->repo->saveEntry($request, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function deleteEntry($id)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return back()->with('danger', ___('alert.not_found'));
        }

        $result = $this->repo->deleteEntry((int) $id, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function submitDay(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return back()->with('danger', ___('alert.not_found'));
        }

        $result = $this->repo->submitDay($staffId, $request->date);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** Coordinator leaves an observation on one entry -- gated by
     *  portal_manage at the route level, plus the same coordinator-scoping
     *  as managerShow() here, so a coordinator can't leave feedback on a
     *  staff member they don't actually oversee. */
    public function saveFeedback(Request $request, $entryId)
    {
        $request->validate(['feedback' => 'required|string|max:1000']);

        $entry = PortalWorkLogEntry::find($entryId);
        if (!$entry) {
            return back()->with('danger', ___('alert.not_found'));
        }
        if (!$this->repo->canView($this->actingStaffId(), (int) $entry->staff_id, $this->seesEveryone())) {
            abort(403, "You're not set as a coordinator for this staff member.");
        }

        $result = $this->repo->saveFeedback((int) $entryId, $request->feedback);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** Super Admin and Admin see every staff member's work regardless of
     *  coordinator assignment; everyone else with portal_manage only sees
     *  the staff they're set as a coordinator for. */
    private function seesEveryone(): bool
    {
        return in_array((int) Auth::user()->role_id, [1, 2], true);
    }

    public function managerIndex(Request $request)
    {
        [$from, $to] = $this->rangeFor($request->string('range', 'week')->toString());

        $data['range']       = $request->string('range', 'week')->toString();
        $data['from']        = $from;
        $data['to']          = $to;
        $data['rows']        = $this->repo->managerSummary($from, $to, $this->actingStaffId(), $this->seesEveryone());
        $data['isSuperAdmin'] = (int) Auth::user()->role_id === 1;
        $data['title']       = 'Team Portal — Work Logs';
        return view('portal.work-log.manager-index', compact('data'));
    }

    /** Super Admin only -- permanently deletes every work log entry (and
     *  their day-submission records) older than $days days. Logs are kept
     *  indefinitely otherwise; this is a manual, deliberate cleanup action,
     *  not an automatic expiry. */
    public function bulkDelete(Request $request)
    {
        if ((int) Auth::user()->role_id !== 1) {
            abort(403, 'Only the main administrator can bulk-delete work log entries.');
        }

        $request->validate(['days' => 'required|integer|min:1']);
        $beforeDate = now()->subDays((int) $request->days)->format('Y-m-d');
        $deleted = $this->repo->bulkDeleteOlderThan($beforeDate);

        return back()->with('success', "Deleted {$deleted} work log entr" . ($deleted === 1 ? 'y' : 'ies') . " logged before {$beforeDate}.");
    }

    public function managerShow(Request $request, $staffId)
    {
        [$from, $to] = $this->rangeFor($request->string('range', 'week')->toString());

        $staff = Staff::find($staffId);
        if (!$staff) {
            return redirect()->route('portal-work-logs.index')->with('danger', ___('alert.not_found'));
        }

        if (!$this->repo->canView($this->actingStaffId(), (int) $staffId, $this->seesEveryone())) {
            abort(403, "You're not set as a coordinator for this staff member.");
        }

        $data['range'] = $request->string('range', 'week')->toString();
        $data['from']  = $from;
        $data['to']    = $to;
        $data['staff'] = $staff;
        $data['days']  = $this->repo->employeeRange((int) $staffId, $from, $to);
        $data['title'] = 'Work Log — ' . trim($staff->first_name . ' ' . $staff->last_name);
        return view('portal.work-log.manager-show', compact('data'));
    }

    private function rangeFor(string $range): array
    {
        return match ($range) {
            'today' => [now()->format('Y-m-d'), now()->format('Y-m-d')],
            'month' => [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')],
            default => [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')],
        };
    }
}
