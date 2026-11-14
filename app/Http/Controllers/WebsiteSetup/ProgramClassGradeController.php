<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClass;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Teacher/Admin-only marks entry for a lesson's gradable items - the
 *  "develop the checking by teachers, marks addition" piece Progress &
 *  Report is built on. Coordinators see the results in Progress & Report
 *  but don't grade here, same split as everywhere else in this feature. */
class ProgramClassGradeController extends Controller
{
    private ProgramClassMarkRepository $repo;

    public function __construct(ProgramClassMarkRepository $repo)
    {
        $this->repo = $repo;
    }

    private function staffId(): ?int
    {
        $user = Auth::user();
        if ($user && (int) $user->role_id !== 1 && $user->staff) {
            return (int) $user->staff->id;
        }
        return null;
    }

    private function authorize(ProgramClass $class): void
    {
        $staffId = $this->staffId();
        if ($staffId === null) {
            return;
        }
        if (!$class->program->teachers()->where('staff.id', $staffId)->exists()) {
            abort(403, "You're not assigned to teach this program.");
        }
    }

    public function edit($id)
    {
        $class = ProgramClass::with('program')->find($id);
        if (!$class) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->authorize($class);

        if (empty($class->gradableItems())) {
            return redirect($this->courseDetailsUrl($class))->with('danger', 'This lesson has no quiz, project, worksheet, or assignment to grade.');
        }

        $data['class']   = $class;
        $data['program'] = $class->program;
        $data['grid']    = $this->repo->gridFor($class);
        $data['title']   = 'Grade — ' . $class->title;
        return view('program-class.grade', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $class = ProgramClass::with('program')->find($id);
        if (!$class) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->authorize($class);

        $request->validate([
            'marks.*.*' => 'nullable|numeric|min:0',
        ]);

        $result = $this->repo->save($class, $request, $this->staffId());

        return redirect()->route('program-class.grade.edit', $class->id)
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    private function courseDetailsUrl(ProgramClass $class): string
    {
        return app(\App\Repositories\WebsiteSetup\ProgramClassRepository::class)->courseDetailsUrl($class->program);
    }
}
