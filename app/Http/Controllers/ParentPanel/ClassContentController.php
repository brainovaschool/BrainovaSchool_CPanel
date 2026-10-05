<?php

namespace App\Http\Controllers\ParentPanel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\StudentInfo\ParentGuardian;
use App\Repositories\ClassContent\ClassContentModuleRepository;

/** Same "pick a child, see their stuff" pattern as ParentPanel's Homework
 *  page — the selected child is remembered in session. Only ever shows
 *  Approved content for a class that child is actually enrolled in. */
class ClassContentController extends Controller
{
    private $repo;

    public function __construct(ClassContentModuleRepository $repo)
    {
        $this->repo = $repo;
    }

    private function children()
    {
        $parent = ParentGuardian::where('user_id', Auth::user()->id)->first();
        return $parent ? Student::where('parent_guardian_id', $parent->id)->get() : collect();
    }

    public function index()
    {
        $data['students'] = $this->children();
        $data['student']  = null;
        $data['modules']  = collect();

        $studentId = Session::get('student_id');
        if ($studentId) {
            $data['student'] = $data['students']->firstWhere('id', (int) $studentId);
            if ($data['student']) {
                $data['modules'] = $this->repo->forStudent($data['student']);
            }
        }

        $data['title'] = 'Class Content';
        return view('parent-panel.class-content.index', compact('data'));
    }

    public function search(Request $request)
    {
        $data['students'] = $this->children();

        $studentId = (int) $request->student;
        Session::put('student_id', $studentId);

        $data['student'] = $data['students']->firstWhere('id', $studentId);
        $data['modules'] = $data['student'] ? $this->repo->forStudent($data['student']) : collect();
        $data['title']   = 'Class Content';

        return view('parent-panel.class-content.index', compact('data'));
    }

    public function show($id)
    {
        $studentId = (int) Session::get('student_id');
        $student   = $studentId ? $this->children()->firstWhere('id', $studentId) : null;

        $data['item'] = $student ? $this->repo->showApprovedForStudent((int) $id, $student) : null;
        if (!$data['item']) {
            return redirect()->route('parent-panel-class-content.index')->with('danger', ___('alert.not_found'));
        }

        $data['title'] = $data['item']->title;
        return view('parent-panel.class-content.show', compact('data'));
    }
}
