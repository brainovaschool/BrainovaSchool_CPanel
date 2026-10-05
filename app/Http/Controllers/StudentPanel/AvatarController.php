<?php

namespace App\Http\Controllers\StudentPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LearningEngine\AvatarItem;
use App\Models\LearningEngine\FeatureAccess;
use App\Repositories\LearningEngine\StudentAvatarRepository;

/** My Avatar — character, outfit, hat and accessory selection and
 *  shopping. Deliberately separate from My Learning Island (see
 *  IslandController) so the two can be switched on for different
 *  students independently, and so a student without Island access never
 *  sees so much as a glimpse of the still-being-built island world —
 *  not even an inert one. */
class AvatarController extends Controller
{
    private $repo;

    public function __construct(StudentAvatarRepository $repo)
    {
        $this->repo = $repo;
    }

    private function ensureAccess($student): void
    {
        if (!$student || !FeatureAccess::isVisibleFor('avatar', $student->id)) {
            abort(403, "Your school hasn't turned this on for your account yet. If you think this should be available to you, ask your school.");
        }
    }

    public function index()
    {
        $student = Auth::user()->student;
        $this->ensureAccess($student);

        $data          = $this->repo->forAvatarPage($student->id);
        $data['title'] = 'My Avatar';

        return view('student-panel.avatar.index', compact('data'));
    }

    public function purchase(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);
        $student = Auth::user()->student;
        $this->ensureAccess($student);

        $item = AvatarItem::find((int) $request->input('item_id'));
        if (!$item || !in_array($item->category, AvatarItem::AVATAR_SHOP_CATEGORIES, true)) {
            return redirect()->route('student-panel-avatar.index')->with('danger', 'That item could not be found.');
        }

        $result = $this->repo->purchase($student->id, $item->id);

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function selectAvatar(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);
        $student = Auth::user()->student;
        $this->ensureAccess($student);

        $result = $this->repo->selectAvatar($student->id, (int) $request->input('item_id'));

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function selectOutfit(Request $request)
    {
        $request->validate(['item_id' => 'nullable|integer']);
        $student = Auth::user()->student;
        $this->ensureAccess($student);

        $itemId = $request->filled('item_id') ? (int) $request->input('item_id') : null;
        $result = $this->repo->selectOutfit($student->id, $itemId);

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function selectHat(Request $request)
    {
        $request->validate(['item_id' => 'nullable|integer']);
        $student = Auth::user()->student;
        $this->ensureAccess($student);

        $itemId = $request->filled('item_id') ? (int) $request->input('item_id') : null;
        $result = $this->repo->selectHat($student->id, $itemId);

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function toggleAccessory(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);
        $student = Auth::user()->student;
        $this->ensureAccess($student);

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
        $this->ensureAccess($student);

        $result = $this->repo->saveProfile($student->id, $request->input('avatar_name'), $request->input('voice_preset'));

        return redirect()->route('student-panel-avatar.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
