<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Interfaces\UserInterface;
use App\Http\Controllers\Controller;
use App\Interfaces\SessionInterface;
use App\Traits\ApiReturnFormatTrait;
use App\Interfaces\Academic\ShiftInterface;
use App\Interfaces\Academic\ClassesInterface;
use App\Interfaces\Academic\SectionInterface;
use App\Interfaces\Academic\SubjectInterface;
use App\Models\Academic\SubjectAssignChildren;
use App\Interfaces\Academic\SubjectAssignInterface;
use App\Repositories\Academic\ClassSetupRepository;
use App\Http\Requests\Academic\SubjectAssign\SubjectAssignStoreRequest;
use App\Http\Requests\Academic\SubjectAssign\SubjectAssignUpdateRequest;

class SubjectAssignController extends Controller
{
    use ApiReturnFormatTrait;

    private $repo;
    private $sessionRepo;
    private $classesRepo;
    private $sectionRepo;
    private $shiftRepo;
    private $subjectRepo;
    private $staffRepo;
    private $classSetupRepo;

    function __construct(
        SubjectAssignInterface $repo,
        SessionInterface       $sessionRepo,
        ClassesInterface       $classesRepo,
        SectionInterface       $sectionRepo,
        ShiftInterface         $shiftRepo,
        SubjectInterface       $subjectRepo,
        UserInterface          $staffRepo,
        ClassSetupRepository   $classSetupRepo,
        )
    {
        $this->repo              = $repo; 
        $this->sessionRepo       = $sessionRepo; 
        $this->classesRepo       = $classesRepo; 
        $this->sectionRepo       = $sectionRepo; 
        $this->shiftRepo         = $shiftRepo; 
        $this->subjectRepo       = $subjectRepo; 
        $this->staffRepo         = $staffRepo; 
        $this->classSetupRepo    = $classSetupRepo; 
    }
    
    public function index()
    {
        $data['title']              = ___('academic.subject_assign');
        $data['subject_assigns']    = $this->repo->getPaginateAll();

        return view('backend.academic.assign-subject.index', compact('data'));
        
    }

    public function create()
    {
        $data['title']              = ___('academic.subject_assign');
        $data['classes']            = $this->classesRepo->assignedAll();
        $data['sections']           = [];
        $data['shifts']             = $this->shiftRepo->all();
        // $data['subjects']           = $this->subjectRepo->all();
        return view('backend.academic.assign-subject.create', compact('data'));
        
    }
    
    public function addSubjectTeacher(Request $request)
    {
        $counter          = $request->counter;
        $data['subjects'] = $this->subjectRepo->all();
        $data['teachers'] = $this->staffRepo->all();
        return view('backend.academic.assign-subject.add-subject-teacher', compact('counter','data'))->render();
    }

    public function store(SubjectAssignStoreRequest $request)
    {
        $result = $this->repo->store($request);
        if($result['status']){
            return redirect()->route('assign-subject.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function show(Request $request)
    {
        $subjectAssignId = $request->query('id', $request->input('id'));
        if ($subjectAssignId === null || $subjectAssignId === '') {
            abort(404);
        }

        $data['subject_assign_children'] = SubjectAssignChildren::query()
            ->with(['subject', 'teacher'])
            ->where('subject_assign_id', $subjectAssignId)
            ->orderBy('subject_id')
            ->get();

        return view('backend.academic.assign-subject.view', compact('data'))->render();
    }

    public function getSubjects(Request $request){
        $result = $this->repo->getSubjects($request);
        return response()->json($result, 200);
    }

    public function edit($id)
    {
        $data                       = $this->repo->show($id);
        $data['title']              = ___('academic.subject_assign');
        $data['subject_assign']     = $data['row'];
        $data['assignSubjects']     = $data['assignSubjects'];
        $data['disabled']           = $data['disabled'];
        $data['redirect']           = $data['redirect'];
        $data['classes']            = $this->classesRepo->assignedAll();
        $data['sections']           = $this->classSetupRepo->getSections($data['subject_assign']->classes_id);
        $data['shifts']             = $this->shiftRepo->all();
        $data['subjects']           = $this->subjectRepo->all();
        $data['teachers']           = $this->staffRepo->all();
        $data['all_subject_assign'] = $data['subject_assign']->subjectTeacher->pluck('subject_id')->toArray();
        
        // dd($data['redirect']);
        if($data['redirect'])
            return redirect()->route('assign-subject.index')->with('danger', ___('academic.you_cannot_edit_this'));

        return view('backend.academic.assign-subject.edit', compact('data'));
    }

    public function update(SubjectAssignUpdateRequest $request, $id)
    {
        // dd($request->all());
        $result = $this->repo->update($request, $id);
        if($result['status']){
            return redirect()->route('assign-subject.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        
        $result = $this->repo->destroy($id);
        if($result['status']):
            $success[0] = $result['message'];
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
            return response()->json($success);
        else:
            $success[0] = $result['message'];
            $success[1] = 'error';
            $success[2] = ___('alert.oops');
            return response()->json($success);
        endif;
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $deleted = $this->repo->bulkDestroy($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    public function checkSection(Request $request)
    {
        $result = $this->repo->checkSection($request);
        return response()->json($result, 200);
    }

    public function checkExamAssign($id)
    {
        $result = $this->repo->checkExamAssign($id);
        return response()->json($result, 200);
    }

}
