<?php

namespace App\Http\Controllers\StudentPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\LearningEngine\StudentAvatarRepository;

class AvatarController extends Controller
{
    private $repo;

    public function __construct(StudentAvatarRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $student = Auth::user()->student;
        $data              = $this->repo->forStudent($student->id);
        $data['title']     = 'My Avatar';

        return view('student-panel.avatar.index', compact('data'));
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

    public function selectAccessory(Request $request)
    {
        $request->validate(['item_id' => 'nullable|integer']);
        $student = Auth::user()->student;

        $itemId = $request->filled('item_id') ? (int) $request->input('item_id') : null;
        $result = $this->repo->selectAccessory($student->id, $itemId);

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
}
