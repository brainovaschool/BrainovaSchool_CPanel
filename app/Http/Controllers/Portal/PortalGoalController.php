<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Staff\Staff;
use App\Repositories\Portal\PortalGoalRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Planning Board — Yearly Goals. Replaces the old Daily Plan & Weekly
 *  Goals page outright (see the 2026_11_09 migrations that drop its two
 *  tables); nothing else in the app read that data. Gated entirely on
 *  planning_board_manage - this is strategic planning, not a content-team
 *  tool, so it no longer follows the Content responsibility the old page
 *  did. */
class PortalGoalController extends Controller
{
    private $repo;

    public function __construct(PortalGoalRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $year = (int) ($request->query('year') ?: now()->year);
        $tree = $this->repo->tree();

        $data['year']         = $year;
        $data['view']         = $request->query('view') === 'roadmap' ? 'roadmap' : 'tree';
        $data['tree']         = $tree;
        $data['roadmapGoals'] = $this->repo->roadmapGoals($year, $tree);
        $data['staffList']    = Staff::orderBy('first_name')->get();
        $data['title']        = 'Planning Board — Yearly Goals';
        return view('portal.goals.index', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:150',
            'parent_id'      => 'nullable|exists:portal_goals,id',
            'owner_staff_id' => 'nullable|exists:staff,id',
            'status'         => 'nullable|in:not_started,in_progress,done',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        $result = $this->repo->store($request, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'          => 'required|string|max:150',
            'owner_staff_id' => 'nullable|exists:staff,id',
            'status'         => 'nullable|in:not_started,in_progress,done',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        $result = $this->repo->update($request, (int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function destroy($id)
    {
        $result = $this->repo->destroy((int) $id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function assignTask(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:150',
            'assigned_to' => 'required|exists:staff,id',
            'due_date'    => 'required|date',
        ]);

        $result = $this->repo->assignTask((int) $id, $request, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
