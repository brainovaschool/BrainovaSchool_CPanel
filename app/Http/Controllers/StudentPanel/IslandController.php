<?php

namespace App\Http\Controllers\StudentPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LearningEngine\AvatarItem;
use App\Models\LearningEngine\Mission;
use App\Repositories\LearningEngine\StudentAvatarRepository;
use App\Repositories\LearningEngine\StudentMissionRepository;

/** My Learning Island — the room/building/mission world and the Yard
 *  Decoration shop. Deliberately separate from AvatarController (My
 *  Avatar) so the two can be switched on for different students
 *  independently — see Website Setup -> Student Feature Access. */
class IslandController extends Controller
{
    private $repo;
    private $missions;

    public function __construct(StudentAvatarRepository $repo, StudentMissionRepository $missions)
    {
        $this->repo     = $repo;
        $this->missions = $missions;
    }

    /** My Learning Island is still being built — hidden from every student
     *  except the ones Website Setup marks as testers (see
     *  AvatarItem::islandVisibleFor()). Checked here, not just in the
     *  sidebar, so a student can't reach any of this by typing the URL
     *  directly. */
    private function ensureAccess($student): void
    {
        if (!$student || !AvatarItem::islandVisibleFor($student->id)) {
            abort(403, "Your school hasn't turned this on for your account yet. If you think this should be available to you, ask your school.");
        }
    }

    public function index()
    {
        $student = Auth::user()->student;
        $this->ensureAccess($student);
        $profile = $this->repo->getOrCreateProfile($student->id);

        // Checked on every visit rather than only when work is submitted,
        // since an Examination approval (the other way a term can finish)
        // happens on the school's own schedule, not the student's.
        $justAdvanced = $this->missions->maybeAdvanceTerm($student->id, $profile);

        $data                     = $this->repo->forIslandPage($student->id);
        $data['title']            = 'My Learning Island';
        $data['justAdvancedTerm'] = $justAdvanced;

        return view('student-panel.island.index', compact('data'));
    }

    public function chooseTheme(Request $request)
    {
        $request->validate(['theme' => 'required|in:' . implode(',', array_keys(Mission::THEMES))]);
        $student = Auth::user()->student;
        $this->ensureAccess($student);

        $result = $this->repo->chooseTheme($student->id, $request->input('theme'));

        return redirect()->route('student-panel-island.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    /** Room content for one Building, loaded into a modal when a student
     *  taps it on the island — an HTML fragment, not JSON, the same way
     *  the homework quiz questions modal already works elsewhere. */
    public function room($buildingId)
    {
        $student = Auth::user()->student;
        $this->ensureAccess($student);
        $profile = $this->repo->getOrCreateProfile($student->id);

        $data['building'] = AvatarItem::active()->category('building')->find($buildingId);
        $data['room']     = $data['building'] ? $this->missions->roomData($student->id, (int) $buildingId, $profile->theme) : null;

        return view('student-panel.island._room-modal', compact('data'));
    }

    public function purchase(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);
        $student = Auth::user()->student;
        $this->ensureAccess($student);

        $item = AvatarItem::find((int) $request->input('item_id'));
        if (!$item || !in_array($item->category, AvatarItem::ISLAND_SHOP_CATEGORIES, true)) {
            return redirect()->route('student-panel-island.index')->with('danger', 'That item could not be found.');
        }

        $result = $this->repo->purchase($student->id, $item->id);

        return redirect()->route('student-panel-island.index')
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
        $this->ensureAccess($student);

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
        $this->ensureAccess($student);

        $result = $this->repo->placeAvatar($student->id, (float) $request->input('pos_x'), (float) $request->input('pos_y'));

        return response()->json(['ok' => $result['status'], 'message' => $result['message'], 'data' => $result['data'] ?? []]);
    }
}
