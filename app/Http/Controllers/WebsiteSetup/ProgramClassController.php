<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramClass;
use App\Repositories\WebsiteSetup\ProgramClassRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Teacher authoring for a Program's classes - the newer, simpler
 *  alternative to Class Content for program-based courses: no Class/
 *  Section/Subject link needed, just "am I assigned to teach this
 *  program" (Program::teachers(), the same Teachers checklist Course
 *  Details already scopes by). Admin always passes, same bypass
 *  convention as ClassContentModuleController. */
class ProgramClassController extends Controller
{
    private $repo;

    public function __construct(ProgramClassRepository $repo)
    {
        $this->repo = $repo;
    }

    private function teacherStaffId(): ?int
    {
        $user = Auth::user();
        if ($user && (int) $user->role_id !== 1 && $user->staff) {
            return (int) $user->staff->id;
        }
        return null;
    }

    private function authorizeProgram(Program $program): void
    {
        $staffId = $this->teacherStaffId();
        if ($staffId === null) {
            return;
        }
        if (!$program->teachers()->where('staff.id', $staffId)->exists()) {
            abort(403, "You're not assigned to teach this program.");
        }
    }

    private function authorizeClass(ProgramClass $class, bool $forEdit = false): void
    {
        $staffId = $this->teacherStaffId();
        if ($staffId === null) {
            return;
        }
        if ((int) $class->created_by !== $staffId) {
            abort(403, "This isn't your lesson.");
        }
        if ($forEdit && !$class->isEditableByTeacher()) {
            abort(403, "This has already been submitted for review — you can't edit it until it's sent back to you.");
        }
    }

    /** A Teacher doesn't have program_update (Website Setup is admin-only),
     *  so sending them to program.edit after saving would 403. Everyone who
     *  can reach this controller at all can reach their category's own
     *  Overview -> Course Details, so that's where this sends them back to
     *  - Admin included, since it's just as valid a landing spot. */
    private function redirectToCourseDetails(Program $program)
    {
        $routeName = match ($program->category?->name) {
            'Homeschooling'    => 'homeschooling.overview',
            'Academics Boost'  => 'core-academics.overview',
            'Beyond Acedamics' => 'beyond-academics.overview',
            'Beyond Screens'   => 'beyond-screens.overview',
            default            => 'dashboard',
        };

        return redirect(route($routeName) . '?program=' . $program->id . '#course-details');
    }

    public function create($programId)
    {
        $program = Program::find($programId);
        if (!$program) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeProgram($program);
        $data['program'] = $program;
        $data['title']   = 'Add a Lesson — ' . $program->title;
        return view('program-class.create', compact('data'));
    }

    public function store(Request $request, $programId)
    {
        $program = Program::find($programId);
        if (!$program) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeProgram($program);

        $this->validateRequest($request);

        $staffId = $this->teacherStaffId() ?? Auth::user()->staff?->id;
        $result  = $this->repo->store($request, (int) $programId, $staffId !== null ? (int) $staffId : null);

        if ($result['status']) {
            return $this->redirectToCourseDetails($program)->with('success', 'Lesson added.');
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeClass($data['item'], true);
        $data['program'] = $data['item']->program;
        $data['title']   = 'Edit Lesson';
        return view('program-class.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $class = ProgramClass::find($id);
        if (!$class) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeClass($class, true);

        $this->validateRequest($request);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return $this->redirectToCourseDetails($class->program)->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $class = ProgramClass::find($id);
        if (!$class) {
            return response()->json([___('alert.not_found'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        $this->authorizeClass($class, true);

        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function submit($id)
    {
        $class = ProgramClass::find($id);
        if (!$class) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->authorizeClass($class, true);

        $result = $this->repo->submit((int) $id);

        return $this->redirectToCourseDetails($class->program)
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    private function validateRequest(Request $request): void
    {
        $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:3000',
            'video_url'   => 'nullable|string|max:500',
            'class_date'  => 'nullable|date',
            'sort_order'  => 'nullable|integer',
            'project_title'       => 'nullable|string|max:150',
            'project_description' => 'nullable|string|max:3000',
            'project_link_url'    => 'nullable|string|max:500',
            'quiz_title'          => 'nullable|string|max:150',
            'quiz_description'    => 'nullable|string|max:3000',
            'quiz_link_url'       => 'nullable|string|max:500',
            'materials.file.*'    => 'nullable|file|max:51200',
        ]);
    }
}
