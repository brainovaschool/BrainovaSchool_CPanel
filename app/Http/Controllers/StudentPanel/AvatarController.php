<?php

namespace App\Http\Controllers\StudentPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LearningEngine\Mission;
use App\Repositories\LearningEngine\StudentAvatarRepository;
use App\Repositories\LearningEngine\StudentMissionRepository;

class AvatarController extends Controller
{
    private $repo;
    private $missions;

    public function __construct(StudentAvatarRepository $repo, StudentMissionRepository $missions)
    {
        $this->repo     = $repo;
        $this->missions = $missions;
    }

    public function index()
    {
        $student = Auth::user()->student;
        $profile = $this->repo->getOrCreateProfile($student->id);

        // Checked on every visit rather than only when work is submitted,
        // since an Examination approval (the other way a term can finish)
        // happens on the school's own schedule, not the student's.
        $justAdvanced = $this->missions->maybeAdvanceTerm($student->id, $profile);

        $data                = $this->repo->forStudent($student->id);
        $data['title']       = 'My Learning Island';
        $data['justAdvancedTerm'] = $justAdvanced;

        return view('student-panel.avatar.index', compact('data'));
    }

    public function chooseTheme(Request $request)
    {
        $request->validate(['theme' => 'required|in:' . implode(',', array_keys(Mission::THEMES))]);
        $student = Auth::user()->student;

        $result = $this->repo->chooseTheme($student->id, $request->input('theme'));

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** Room content for one Building, loaded into a modal when a student
     *  taps it on the island — an HTML fragment, not JSON, the same way
     *  the homework quiz questions modal already works elsewhere. */
    public function room($buildingId)
    {
        $student = Auth::user()->student;
        $profile = $this->repo->getOrCreateProfile($student->id);

        $data['building'] = \App\Models\LearningEngine\AvatarItem::active()->category('building')->find($buildingId);
        $data['room']     = $data['building'] ? $this->missions->roomData($student->id, (int) $buildingId, $profile->theme) : null;

        return view('student-panel.avatar._room-modal', compact('data'));
    }

    public function purchase(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);
        $student = Auth::user()->student;

        $result = $this->repo->purchase($student->id, (int) $request->input('item_id'));

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function selectAvatar(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);
        $student = Auth::user()->student;

        $result = $this->repo->selectAvatar($student->id, (int) $request->input('item_id'));

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function selectOutfit(Request $request)
    {
        $request->validate(['item_id' => 'nullable|integer']);
        $student = Auth::user()->student;

        $itemId = $request->filled('item_id') ? (int) $request->input('item_id') : null;
        $result = $this->repo->selectOutfit($student->id, $itemId);

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function selectHat(Request $request)
    {
        $request->validate(['item_id' => 'nullable|integer']);
        $student = Auth::user()->student;

        $itemId = $request->filled('item_id') ? (int) $request->input('item_id') : null;
        $result = $this->repo->selectHat($student->id, $itemId);

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function toggleAccessory(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);
        $student = Auth::user()->student;

        $result = $this->repo->toggleAccessory($student->id, (int) $request->input('item_id'));

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function saveProfile(Request $request)
    {
        $request->validate([
            'avatar_name'  => 'nullable|string|max:40',
            'voice_preset' => 'required|string',
        ]);
        $student = Auth::user()->student;

        $result = $this->repo->saveProfile($student->id, $request->input('avatar_name'), $request->input('voice_preset'));

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function placeItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'pos_x'   => 'required|numeric|between:0,100',
            'pos_y'   => 'required|numeric|between:0,100',
        ]);
        $student = Auth::user()->student;

        $result = $this->repo->placeItem($student->id, (int) $request->input('item_id'), (float) $request->input('pos_x'), (float) $request->input('pos_y'));

        return response()->json(['ok' => $result['status'], 'message' => $result['message'], 'data' => $result['data'] ?? []]);
    }

    public function placeAvatar(Request $request)
    {
        $request->validate([
            'pos_x' => 'required|numeric|between:0,100',
            'pos_y' => 'required|numeric|between:0,100',
        ]);
        $student = Auth::user()->student;

        $result = $this->repo->placeAvatar($student->id, (float) $request->input('pos_x'), (float) $request->input('pos_y'));

        return response()->json(['ok' => $result['status'], 'message' => $result['message'], 'data' => $result['data'] ?? []]);
    }
}
