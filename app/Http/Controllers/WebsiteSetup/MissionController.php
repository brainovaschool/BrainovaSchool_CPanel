<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LearningEngine\AvatarItem;
use App\Models\LearningEngine\Mission;
use App\Models\Homework;
use App\Models\OnlineExamination\OnlineExam;
use App\Repositories\WebsiteSetup\MissionRepository;

class MissionController extends Controller
{
    private $repo;

    public function __construct(MissionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['missions'] = $this->repo->getAll();
        $data['title']    = 'Missions';
        return view('website-setup.mission.index', compact('data'));
    }

    public function create()
    {
        $data = $this->formData();
        $data['title'] = 'Add a Mission';
        return view('website-setup.mission.create', compact('data'));
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('mission.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data = $this->formData();
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('mission.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Mission';
        return view('website-setup.mission.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $this->validateRequest($request);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('mission.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkDestroy($ids);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    private function validateRequest(Request $request): void
    {
        $request->validate([
            'hub_id'       => 'required|exists:avatar_items,id',
            'building_id'  => 'nullable|exists:avatar_items,id',
            'theme'        => 'required|in:' . implode(',', array_keys(Mission::THEMES)),
            'character'    => 'required|in:brainbot,kea',
            'title'        => 'required|string|max:120',
            'intro_line'   => 'required|string|max:600',
            'clue_lines'   => 'required|string',
            'ending_line'  => 'required|string|max:600',
            'reward_note'  => 'nullable|string|max:150',
            'sort_order'   => 'nullable|integer',
            'status'       => 'required',
            'linkable_type' => 'nullable|in:' . implode(',', array_keys(Mission::LINKABLE_TYPES)),
            'linkable_id'   => 'nullable|integer',
        ]);
    }

    /** Everything both create and edit need to render their pickers —
     *  hubs/buildings from the same Avatar Gallery this session already
     *  built, and the real Homework/Online Exam lists so a mission always
     *  wraps an assignment that actually exists. */
    private function formData(): array
    {
        return [
            'hubs'      => AvatarItem::active()->category('hub')->orderBy('sort_order')->get(),
            'buildings' => AvatarItem::active()->category('building')->with('parent')->orderBy('sort_order')->get(),
            'homeworks' => Homework::with('subject')->where('session_id', setting('session'))->orderByDesc('date')->limit(200)->get(),
            'exams'     => OnlineExam::with('subject')->where('session_id', setting('session'))->orderByDesc('id')->limit(200)->get(),
        ];
    }
}
