<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\StudentInfo\Student;
use App\Models\WebsiteSetup\Program;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** A single student's progress within one Program - what clicking a
 *  student's name on Progress & Report's "Student scores" table opens.
 *  Read-only; reuses the same per-student breakdown the student's own
 *  Progress & Report is built on (ProgramClassMarkRepository), just
 *  across every lesson with gradable items rather than Approved-only. */
class ProgramStudentProgressController extends Controller
{
    private ProgramClassMarkRepository $repo;

    public function __construct(ProgramClassMarkRepository $repo)
    {
        $this->repo = $repo;
    }

    public function show($programId, $studentId)
    {
        $program = Program::with('students')->find($programId);
        $student = Student::find($studentId);

        if (!$program || !$student || !$program->students->contains('id', $student->id)) {
            abort(404);
        }

        $this->authorizeProgram($program);

        $data['program']  = $program;
        $data['student']  = $student;
        $data['progress'] = $this->repo->studentBreakdownForTeacher($program, $student);
        $data['title']    = $student->full_name . ' — ' . $program->title;

        return view('program-class.student-progress', compact('data'));
    }

    private function authorizeProgram(Program $program): void
    {
        $user = Auth::user();
        if ((int) ($user->role_id ?? 0) === 1) {
            return;
        }

        $staffId = $user->staff->id ?? null;
        if ($staffId && $program->teachers()->where('staff.id', $staffId)->exists()) {
            return;
        }

        if ($staffId && hasPermission('class_content_coordinator_review')) {
            $coordinateeIds = $user->staff->coordinatees->pluck('id')->all();
            if ($coordinateeIds && DB::table('staff_program_enrollments')->where('program_id', $program->id)->whereIn('staff_id', $coordinateeIds)->exists()) {
                return;
            }
        }

        abort(403, ___('alert.You are not authorized to access this page'));
    }
}
