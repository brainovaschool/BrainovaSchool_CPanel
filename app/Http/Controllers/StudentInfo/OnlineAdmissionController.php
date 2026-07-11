<?php

namespace App\Http\Controllers\StudentInfo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentInfo\OnlineAdmission\OnlineAdmissionRequest;
use App\Repositories\GenderRepository;
use App\Repositories\ReligionRepository;
use App\Repositories\BloodGroupRepository;
use App\Repositories\Academic\ShiftRepository;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\Academic\SectionRepository;
use App\Repositories\Academic\ClassSetupRepository;
use App\Repositories\StudentInfo\OnlineAdmissionRepository;
use App\Repositories\StudentInfo\StudentCategoryRepository;

class OnlineAdmissionController extends Controller
{
    private $repo;
    private $classRepo;
    private $sectionRepo;
    private $classSetupRepo;
    private $shiftRepo;
    private $bloodRepo;
    private $religionRepo;
    private $genderRepo;
    private $categoryRepo;

    function __construct(
        OnlineAdmissionRepository    $repo,
        ClassesRepository            $classRepo,
        SectionRepository            $sectionRepo,
        ClassSetupRepository         $classSetupRepo,
        ShiftRepository              $shiftRepo,
        BloodGroupRepository         $bloodRepo,
        ReligionRepository           $religionRepo,
        GenderRepository             $genderRepo,
        StudentCategoryRepository    $categoryRepo,
        )
    {
        $this->repo         = $repo;
        $this->classRepo    = $classRepo;
        $this->sectionRepo  = $sectionRepo;
        $this->classSetupRepo  = $classSetupRepo;
        $this->shiftRepo    = $shiftRepo;
        $this->bloodRepo    = $bloodRepo;
        $this->religionRepo = $religionRepo;
        $this->genderRepo   = $genderRepo;
        $this->categoryRepo = $categoryRepo;
    }

    public function index()
    {
        $data['title']    = ___('student_info.online_admission');
        $data['students'] = $this->repo->all();
        return view('backend.student-info.online-admission.index', compact('data'));
    }

    public function search(Request $request)
    {
        $data['request']  = $request;
        $data['title']    = ___('student_info.online_admission');
        $data['students'] = $this->repo->searchStudents($request);
        return view('backend.student-info.online-admission.index', compact('data'));
    }

    public function edit($id)
    {
        $data['title']   = ___('student_info.Update Online Admission');
        $data['student'] = $this->repo->show($id);

        return view('backend.student-info.online-admission.edit', compact('data'));
    }

    public function store(OnlineAdmissionRequest $request)
    {
       
        $result = $this->repo->store($request);

        if($result['status']){
            return redirect()->route('online-admissions.index')->with('success', $result['message']);
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
}
