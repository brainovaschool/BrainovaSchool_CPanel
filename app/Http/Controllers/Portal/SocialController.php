<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Portal\PortalReel;
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
        if (hasPermission('portal_manage') || hasPermission('portal_social_suggest_reel_read')) {
            return true;
        }
        $staffId = $this->actingStaffId();
        return $staffId !== null && in_array($staffId, PortalResponsibility::ownerStaffIdsForKey(PortalResponsibility::KEY_CONTENT), true);
    }

    private function canAssignReels(): bool
    {
        // portal_social_content_pool_read is the old standalone Content
        // Pool page's permission - that page is now just the Reels page,
        // but the permission still means the same thing (can see/use the
        // assign control), so anyone already granted it keeps it working.
        return hasPermission('portal_manage') || hasPermission('portal_reel_assign') || hasPermission('portal_social_content_pool_read');
    }

    private function canApproveReels(): bool
    {
        return hasPermission('portal_manage') || hasPermission('portal_reel_approve');
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
        $data['canManage']   = $this->canManageContent();
        $data['reels']       = $this->repo->reels($request->only(['status', 'category', 'format', 'series_name']));
        $data['settings']    = PortalSetting::current();
        $data['series']      = $this->repo->reelSeries();
        $data['filters']     = $request->only(['status', 'category', 'format', 'series_name']);
        $data['title']       = 'Social Board — Reels';
        return view('portal.social.reels.index', compact('data'));
    }

    public function createReel()
    {
        $this->requireContentAccess();
        // "Admin or coordinator" (portal_manage) gets the full form; a
        // content-team member raising their own suggestion gets the
        // short one - series, topic, hook, nothing else (everything
        // else comes from the series' defaults).
        $data['isManager']    = hasPermission('portal_manage');
        $data['settings']     = PortalSetting::current();
        $data['customFields'] = $this->repo->reelCustomFields();
        $data['series']       = $this->repo->reelSeries();
        $data['title']        = 'Add a Reel/Topic';
        return view('portal.social.reels.create', compact('data'));
    }

    // ---- Suggest Content (dedicated, always-simplified entry point) --

    public function suggestReelIndex()
    {
        // Page Fixes lived behind its own, separate permission - now that
        // it's a block on this page, anyone who only had that keeps
        // access, same as anyone who could already reach Suggest Content.
        if (!$this->canManageContent() && !hasPermission('portal_social_page_fixes_read')) {
            abort(403, 'Only admin or whoever holds the Content responsibility can do this.');
        }
        $data['canManage'] = hasPermission('portal_manage');
        $data['fixes']     = $this->repo->pageFixes();
        $data['staffList'] = Staff::orderBy('first_name')->get();
        $data['myStaffId'] = $this->actingStaffId();
        $data['title']     = 'Suggest';
        return view('portal.social.suggest-reel.index', compact('data'));
    }

    public function suggestReelCreate()
    {
        $this->requireContentAccess();
        $data['series'] = $this->repo->reelSeries();
        $data['title']  = 'Suggest';
        return view('portal.social.suggest-reel.create', compact('data'));
    }

    /** Content Pool used to be its own page - it's now just the Reels
     *  page (same table, assign control included), so the old URL
     *  redirects there instead of 404ing for anyone with it bookmarked. */
    public function contentPool()
    {
        return redirect()->route('portal-social-reels.index');
    }

    public function storeReel(Request $request)
    {
        $this->requireContentAccess();
        $request->validate([
            'title'  => 'required|string|max:150',
            // Left blank on the simplified Suggest form (format comes
            // from the series' default instead) - the repository falls
            // back to 'Reel' if even that's missing.
            'format' => 'nullable|string|max:20',
        ]);

        $result = $this->repo->storeReel($request, Auth::id());
        if ($result['status']) {
            // The simplified Suggest Content form marks itself so its
            // submitter lands back on that screen, not a reel detail page
            // built for the full admin form.
            if ($request->input('return_to') === 'suggest') {
                return redirect()->route('portal-social-suggest-reel.index')->with('success', $result['message']);
            }
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

        $data['reel']         = $reel;
        $data['canManage']    = $this->canManageContent();
        $data['isManager']    = hasPermission('portal_manage');
        $data['canAssign']    = $this->canAssignReels();
        $data['canApprove']   = $this->canApproveReels();
        $data['contentTeam']  = $this->repo->assignableStaff();
        $data['myStaffId']    = $this->actingStaffId();
        $data['customFields'] = $this->repo->reelCustomFields();
        $data['title']        = $reel->title;
        return view('portal.social.reels.show', compact('data'));
    }

    /** The bridge into Reel Studio: a suggestion has to be approved
     *  before there's anything to set up, so "Open" only lands here past
     *  that point - the first visit creates the linked project, every
     *  later one just goes straight back to it. */
    public function openReelSetup($id)
    {
        $reel = PortalReel::find((int) $id);
        if (!$reel) {
            return redirect()->route('portal-social-reels.index')->with('danger', ___('alert.not_found'));
        }

        $staffId    = $this->actingStaffId();
        $isAssignee = $staffId !== null && (int) $reel->assigned_to === $staffId;
        if (!$this->canManageContent() && !$isAssignee) {
            abort(403);
        }
        if ($reel->status === PortalReel::SUGGESTED) {
            return redirect()->route('portal-social-reels.show', $id)->with('danger', 'Approve this suggestion before setting it up.');
        }

        $project = $this->repo->openOrCreateReelProject($reel, Auth::id());
        return redirect()->route('portal-social-reel-studio.show', $project->id);
    }

    public function editReel($id)
    {
        $this->requireContentAccess();
        $reel = $this->repo->showReel((int) $id);
        if (!$reel) {
            return redirect()->route('portal-social-reels.index')->with('danger', ___('alert.not_found'));
        }

        $data['reel']         = $reel;
        $data['isManager']    = hasPermission('portal_manage');
        $data['settings']     = PortalSetting::current();
        $data['customFields'] = $this->repo->reelCustomFields();
        $data['series']       = $this->repo->reelSeries();
        $data['title']        = 'Edit Reel/Topic';
        return view('portal.social.reels.edit', compact('data'));
    }

    public function updateReel(Request $request, $id)
    {
        $this->requireContentAccess();
        $request->validate([
            'title'  => 'required|string|max:150',
            'format' => 'nullable|string|max:20',
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

    /** "Admin or coordinator" specifically (not just any content-team
     *  member) picks who's doing this reel/topic, or leaves it open as a
     *  paid task for anyone to pick up, plus the work deadline. */
    public function assignReel(Request $request, $id)
    {
        if (!$this->canAssignReels()) {
            abort(403, 'Only admin/coordinator can assign a reel/topic.');
        }
        $openPaid = $request->boolean('open_paid');
        $request->validate([
            'assigned_to' => $openPaid ? 'nullable' : 'required|exists:staff,id',
            'deadline'    => 'nullable|date',
        ]);

        $result = $this->repo->assignReel((int) $id, $openPaid ? null : (int) $request->assigned_to, $openPaid, $request->deadline, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** The assignee shares a prompt link first, then (once that's
     *  approved) a video link - same route, $request->stage picks which. */
    public function submitReelLink(Request $request, $id)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            abort(403);
        }
        $request->validate([
            'stage' => 'required|in:prompt,video',
            'link'  => 'required|string|max:500',
        ]);

        $result = $this->repo->submitStageLink((int) $id, $request->stage, $request->link, $staffId);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function approveReelStage(Request $request, $id)
    {
        if (!$this->canApproveReels()) {
            abort(403, 'Only admin/coordinator can approve.');
        }
        $request->validate([
            'stage'    => 'required|in:prompt,video',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $result = $this->repo->approveStage((int) $id, $request->stage, Auth::id(), $request->feedback);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** The final step, once the video's approved: pick the date it
     *  actually goes live. */
    public function scheduleReel(Request $request, $id)
    {
        if (!$this->canApproveReels()) {
            abort(403, 'Only admin/coordinator can schedule an upload date.');
        }
        $request->validate(['planned_date' => 'required|date']);

        $result = $this->repo->scheduleUpload((int) $id, $request->planned_date);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
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

    /** Page Fixes used to be its own page - it's now a block on the
     *  Suggest page, so the old URL redirects there instead of 404ing
     *  for anyone with it bookmarked. */
    public function pageFixes()
    {
        return redirect()->route('portal-social-suggest-reel.index');
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
        $data['lists']              = $this->repo->settingLists();
        $data['customFields']       = $this->repo->reelCustomFields();
        $data['fieldTypes']         = \App\Models\Portal\PortalReelCustomField::TYPES;
        $data['series']             = $this->repo->reelSeries();
        $data['roles']              = \App\Models\Role::orderBy('name')->get();
        $data['assigneeRoleIds']    = PortalSetting::current()->content_assignee_role_ids ?: [];
        $data['title']              = 'Social Board Settings';
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

    public function updateContentAssigneeRoles(Request $request)
    {
        if (!hasPermission('portal_manage')) {
            abort(403);
        }
        $result = $this->repo->updateContentAssigneeRoles($request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function updateReelFields(Request $request)
    {
        if (!hasPermission('portal_manage')) {
            abort(403);
        }
        $result = $this->repo->updateReelCustomFields($request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function updateReelSeries(Request $request)
    {
        if (!hasPermission('portal_manage')) {
            abort(403);
        }
        $result = $this->repo->updateReelSeries($request);
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
