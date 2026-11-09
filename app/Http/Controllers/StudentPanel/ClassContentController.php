<?php

namespace App\Http\Controllers\StudentPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ClassContent\ClassContentModuleRepository;
use App\Repositories\ClassContent\ClassContentCompletionRepository;

/** Read-only except complete() — a student only ever sees modules that are
 *  Approved and belong to a class they're actually enrolled in this
 *  session; the data a teacher hasn't filled in or hasn't published never
 *  reaches this controller at all (see ClassContentModuleRepository's
 *  approved()/enrolled scoping), so there's no separate "is this active"
 *  check to make here. */
class ClassContentController extends Controller
{
    private $repo;
    private $completions;

    public function __construct(ClassContentModuleRepository $repo, ClassContentCompletionRepository $completions)
    {
        $this->repo        = $repo;
        $this->completions = $completions;
    }

    public function index(Request $request)
    {
        $student = Auth::user()->student;
        $filters = $request->only(['classes_id', 'section_id', 'subject_id']);
        $data['dashboard'] = $student ? $this->repo->dashboardForStudent($student, $filters) : ['groups' => collect(), 'ungrouped' => collect(), 'unitsCount' => 0, 'modulesCount' => 0, 'lessonsCount' => 0];
        $data['map']   = $student ? $this->completions->mapForStudent($student->id) : [];
        $data['title'] = 'Class Content';
        return view('student-panel.class-content.index', compact('data'));
    }

    public function show($id)
    {
        $student      = Auth::user()->student;
        $data['item'] = $student ? $this->repo->showApprovedForStudent((int) $id, $student) : null;

        if (!$data['item']) {
            return redirect()->route('student-panel-class-content.index')->with('danger', ___('alert.not_found'));
        }

        $data['title'] = $data['item']->title;
        return view('student-panel.class-content.show', compact('data'));
    }

    public function lesson($id)
    {
        $student = Auth::user()->student;
        $lesson  = $student ? $this->repo->lessonForStudent((int) $id, $student) : null;

        if (!$lesson) {
            return redirect()->route('student-panel-class-content.index')->with('danger', ___('alert.not_found'));
        }

        $data['lesson'] = $lesson;
        $data['map']    = $this->completions->mapForStudent($student->id);
        $data['tab']    = request('tab') === 'activities' ? 'activities' : (request('tab') === 'outcome' ? 'outcome' : 'overview');
        $data['title']  = $lesson->title;
        return view('student-panel.class-content.lesson', compact('data'));
    }

    public function complete(Request $request)
    {
        $student = Auth::user()->student;
        if (!$student) {
            return back()->with('danger', "This isn't available to you.");
        }

        $request->validate([
            'type' => 'required|in:lesson,activity,project,quiz',
            'id'   => 'required|integer',
        ]);

        // Re-derive ownership from the lesson a completion belongs to,
        // rather than trusting the posted type/id alone — a student can
        // only mark complete something that's actually theirs to see. This
        // also gives us the right lesson to redirect back to either way:
        // an activity's own id isn't a lesson id, but its ->lesson is.
        $lesson = $request->type === 'activity'
            ? \App\Models\ClassContent\ClassContentActivity::find($request->id)?->lesson
            : \App\Models\ClassContent\ClassContentLesson::find($request->id);

        if (!$lesson || !$this->repo->lessonForStudent($lesson->id, $student)) {
            return back()->with('danger', ___('alert.not_found'));
        }

        $this->completions->markDone($student->id, $request->type, (int) $request->id);

        return redirect()->route('student-panel-class-content.lesson', $lesson->id)->with('success', 'Marked complete.');
    }
}
