<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\StudentInfo\Student;
use App\Models\WebsiteSetup\Program;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Teacher/Admin-only marks entry - every lesson in a Program, filtered
 *  down to one student at a time (a dropdown, not a separate page),
 *  since that's what actually scales once a course has more than a
 *  couple of students: a wide "everyone x every item" grid stops being
 *  readable fast, one student's own row at a time doesn't. Coordinators
 *  see results in Progress & Report but don't grade here, same split as
 *  everywhere else in this feature. */
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

    private function guardAccess(Program $program): void
    {
        $staffId = $this->staffId();
        if ($staffId === null) {
            return;
        }
        if (!$program->teachers()->where('staff.id', $staffId)->exists()) {
            abort(403, "You're not assigned to teach this program.");
        }
    }

    public function edit(Request $request, $programId)
    {
        $program = Program::with('students')->find($programId);
        if (!$program) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->guardAccess($program);

        $students = $program->students()->orderBy('first_name')->get();

        $selectedStudentId = (int) $request->query('student') ?: null;
        $selectedStudent = $selectedStudentId ? $students->firstWhere('id', $selectedStudentId) : null;
        $selectedStudent = $selectedStudent ?? $students->first();

        $data['program']         = $program;
        $data['students']        = $students;
        $data['selectedStudent'] = $selectedStudent;
        $data['breakdown']       = $selectedStudent ? $this->repo->studentBreakdownForTeacher($program, $selectedStudent) : null;
        $data['title']           = 'Grade — ' . $program->title;

        return view('program-class.grade', compact('data'));
    }

    public function update(Request $request, $programId)
    {
        $program = Program::find($programId);
        if (!$program) {
            return redirect()->route('dashboard')->with('danger', ___('alert.not_found'));
        }
        $this->guardAccess($program);

        $request->validate([
            'student_id' => 'required|integer',
            'marks.*.*'  => 'nullable|numeric|min:0',
        ]);

        $student = Student::find($request->input('student_id'));
        if (!$student) {
            return back()->with('danger', ___('alert.not_found'));
        }

        $result = $this->repo->saveForStudent($program, $student, $request, $this->staffId());

        return redirect(route('program-class.grade.edit', $program->id) . '?student=' . $student->id)
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
