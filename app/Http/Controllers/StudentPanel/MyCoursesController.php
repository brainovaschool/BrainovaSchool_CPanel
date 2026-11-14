<?php

namespace App\Http\Controllers\StudentPanel;

use App\Http\Controllers\Controller;
use App\Repositories\StudentPanel\MyCoursesRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Phase 2 of Progress & Report: the student-facing counterpart to the
 *  admin/teacher/coordinator Course Details + Progress & Report pages -
 *  a student sees their own enrolled Programs, the same lesson content,
 *  and only their own marks, never a classmate's. */
class MyCoursesController extends Controller
{
    private $repo;

    public function __construct(MyCoursesRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $student   = Auth::user()->student;
        $programId = (int) $request->query('program') ?: null;

        $dashboard = $student
            ? $this->repo->dashboardForStudent($student, $programId)
            : ['programs' => collect(), 'selectedProgram' => null, 'progress' => null];

        $data['programs']        = $dashboard['programs'];
        $data['selectedProgram'] = $dashboard['selectedProgram'];
        $data['progress']        = $dashboard['progress'];
        $data['title']           = 'My Courses';

        return view('student-panel.my-courses.index', compact('data'));
    }
}
