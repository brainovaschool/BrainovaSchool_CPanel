<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Portal\PortalResponsibility;
use App\Models\Portal\PortalSetting;
use App\Models\Staff\Staff;
use App\Repositories\Portal\SocialRepository;

/** Social board — handoff spec phase 8. "Content" rights (reels, month
 *  plan) and "Audience" rights (metrics) follow whoever currently holds
 *  that Responsibility (phase 5) — not a role — plus portal_manage
 *  always has both, matching the spec's access matrix exactly. */
class SocialController extends Controller
{
    private $repo;

    public function __construct(SocialRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    private function canManageContent(): bool
    {
        if (hasPermission('portal_manage')) {
            return true;
        }
        $staffId = $this->actingStaffId();
        return $staffId !== null && in_array($staffId, PortalResponsibility::ownerStaffIdsForKey(PortalResponsibility::KEY_CONTENT), true);
    }

    private function canManageAudience(): bool
    {
        if (hasPermission('portal_manage')) {
            return true;
        }
        $staffId = $this->actingStaffId();
        return $staffId !== null && in_array($staffId, PortalResponsibility::ownerStaffIdsForKey(PortalResponsibility::KEY_AUDIENCE), true);
    }

    private function requireContentAccess(): void
    {
        if (!$this->canManageContent()) {
            abort(403, 'Only admin or whoever holds the Content responsibility can do this.');
        }
    }

    // ---- Reels -------------------------------------------------------

    public function reels(Request $request)
    {
        $data['canManage'] = $this->canManageContent();
        $data['reels']     = $this->repo->reels($request->only(['status', 'category', 'format']));
        $data['settings']  = PortalSetting::current();
        $data['filters']   = $request->only(['status', 'category', 'format']);
        $data['title']     = 'Social Board — Reels';
        return view('portal.social.reels.index', compact('data'));
    }

    public function createReel()
    {
        $this->requireContentAccess();
        $data['settings'] = PortalSetting::current();
        $data['title']    = 'Add a Reel/Topic';
        return view('portal.social.reels.create', compact('data'));
    }

    public function storeReel(Request $request)
    {
        $this->requireContentAccess();
        $request->validate([
            'title'  => 'required|string|max:150',
            'format' => 'required|in:Reel,Carousel,Post',
        ]);

        $result = $this->repo->storeReel($request, Auth::id());
        if ($result['status']) {
            return redirect()->route('portal-social-reels.show', $result['data']['id'])->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function showReel($id)
    {
        $reel = $this->repo->showReel((int) $id);
        if (!$reel) {
            return redirect()->route('portal-social-reels.index')->with('danger', ___('alert.not_found'));
        }

        $data['reel']      = $reel;
        $data['canManage'] = $this->canManageContent();
        $data['title']     = $reel->title;
        return view('portal.social.reels.show', compact('data'));
    }

    public function editReel($id)
    {
        $this->requireContentAccess();
        $reel = $this->repo->showReel((int) $id);
        if (!$reel) {
            return redirect()->route('portal-social-reels.index')->with('danger', ___('alert.not_found'));
        }

        $data['reel']     = $reel;
        $data['settings'] = PortalSetting::current();
        $data['title']    = 'Edit Reel/Topic';
        return view('portal.social.reels.edit', compact('data'));
    }

    public function updateReel(Request $request, $id)
    {
        $this->requireContentAccess();
        $request->validate([
            'title'  => 'required|string|max:150',
            'format' => 'required|in:Reel,Carousel,Post',
        ]);

        $result = $this->repo->updateReel($request, (int) $id);
        return redirect()->route('portal-social-reels.show', $id)->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function reviewReel(Request $request, $id)
    {
        $this->requireContentAccess();
        $request->validate([
            'status' => 'required|in:accepted,production,ready,published,rejected',
            'note'   => 'nullable|string|max:1000',
        ]);

        $result = $this->repo->reviewReel((int) $id, $request->status, $request->note, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function destroyReel($id)
    {
        $this->requireContentAccess();
        $result = $this->repo->destroyReel((int) $id);
        return redirect()->route('portal-social-reels.index')->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    // ---- Month plan ----------------------------------------------------

    public function monthPlan()
    {
        $data['canManage'] = $this->canManageContent();
        $data['plan']      = $this->repo->monthPlan();
        $data['title']     = 'Social Board — Month Plan';
        return view('portal.social.month-plan', compact('data'));
    }

    public function updateCategoryTargets(Request $request)
    {
        if (!hasPermission('portal_manage')) {
            abort(403);
        }
        $result = $this->repo->updateCategoryTargets($request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    // ---- Audience numbers ----------------------------------------------

    public function audience(Request $request)
    {
        if (!$this->canManageAudience()) {
            abort(403, 'Only admin or whoever holds the Audience responsibility can see this.');
        }

        $month = $request->filled('month') ? $request->month : now()->format('Y-m');

        $data['canManage'] = $this->canManageAudience();
        $data['month']     = $month;
        $data['metrics']   = $this->repo->metricsForMonth($month);
        $data['settings']  = PortalSetting::current();
        $data['title']     = 'Social Board — Audience Numbers';
        return view('portal.social.audience', compact('data'));
    }

    public function storeMetric(Request $request)
    {
        if (!$this->canManageAudience()) {
            abort(403);
        }
        $request->validate([
            'date'     => 'required|date',
            'platform' => 'required|string|max:60',
        ]);

        $result = $this->repo->saveMetric($request, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    // ---- Page fixes ------------------------------------------------------

    public function pageFixes()
    {
        $data['canManage'] = hasPermission('portal_manage');
        $data['fixes']     = $this->repo->pageFixes();
        $data['staffList'] = Staff::orderBy('first_name')->get();
        $data['myStaffId'] = $this->actingStaffId();
        $data['title']     = 'Social Board — Page Fixes';
        return view('portal.social.page-fixes', compact('data'));
    }

    public function storePageFix(Request $request)
    {
        if (!hasPermission('portal_manage')) {
            abort(403);
        }
        $request->validate(['title' => 'required|string|max:150']);

        $result = $this->repo->storePageFix($request, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function updatePageFixStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:open,in_progress,fixed']);

        $isManager = hasPermission('portal_manage');
        $staffId   = $this->actingStaffId();
        if (!$isManager && $staffId === null) {
            abort(403);
        }

        $result = $this->repo->updatePageFixStatus((int) $id, $request->status, $isManager ? null : $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    // ---- Settings ----------------------------------------------------

    public function settings()
    {
        if (!hasPermission('portal_manage')) {
            abort(403);
        }
        $data['settings'] = PortalSetting::current();
        $data['title']    = 'Social Board Settings';
        return view('portal.social.settings', compact('data'));
    }

    public function updateSettings(Request $request)
    {
        if (!hasPermission('portal_manage')) {
            abort(403);
        }
        $result = $this->repo->updateSocialSettings($request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    // ---- Daily plan / weekly goals -------------------------------------

    public function dailyPlan(Request $request)
    {
        if (!$this->canManageContent()) {
            abort(403, 'Only admin or whoever holds the Content responsibility can see this.');
        }

        $weekStart = $request->filled('week') ? $request->week : now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');
        $staffId   = $this->actingStaffId();

        $data['weekStart']   = $weekStart;
        $data['contentTeam'] = $this->repo->contentTeam();
        $data['plansByStaff'] = $this->repo->dailyPlansForWeek($weekStart);
        $data['goals']       = $this->repo->weeklyGoalsForWeek($weekStart)->keyBy('staff_id');
        $data['myStaffId']   = $staffId;
        $data['isOnTeam']    = $staffId !== null && $data['contentTeam']->contains('id', $staffId);
        $data['title']       = 'Social Board — Daily Plan & Weekly Goals';
        return view('portal.social.daily-plan', compact('data'));
    }

    public function storeDailyPlan(Request $request)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null || !in_array($staffId, PortalResponsibility::ownerStaffIdsForKey(PortalResponsibility::KEY_CONTENT), true)) {
            abort(403, "You're not on the content team.");
        }
        $request->validate(['date' => 'required|date']);

        $result = $this->repo->saveDailyPlan($request, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function storeWeeklyGoal(Request $request)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null || !in_array($staffId, PortalResponsibility::ownerStaffIdsForKey(PortalResponsibility::KEY_CONTENT), true)) {
            abort(403, "You're not on the content team.");
        }
        $request->validate(['week_start' => 'required|date']);

        $result = $this->repo->saveWeeklyGoal($request, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function toggleGoal($id)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            abort(403);
        }
        $result = $this->repo->toggleGoalAchieved((int) $id, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
