<?php

namespace App\Http\Controllers\StudentPanel;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ClassContent\ClassContentModuleRepository;

/** Read-only — a student only ever sees modules that are Approved and
 *  belong to a class they're actually enrolled in this session. */
class ClassContentController extends Controller
{
    private $repo;

    public function __construct(ClassContentModuleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $student = Auth::user()->student;
        $data['modules'] = $student ? $this->repo->forStudent($student) : collect();
        $data['title']   = 'Class Content';
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
}
