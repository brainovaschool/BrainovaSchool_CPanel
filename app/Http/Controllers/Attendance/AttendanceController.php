<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceReportRequest;
use App\Http\Requests\Attendance\AttendanceSearchRequest;
use App\Http\Requests\Attendance\AttendanceStoreRequest;
use App\Http\Requests\Report\AttendanceRequest;
use App\Http\Requests\SubjectAttendanceSearchRequest;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\Academic\ClassSetupRepository;
use App\Repositories\Academic\SubjectAssignRepository;
use App\Repositories\Academic\SubjectRepository;
use App\Repositories\Attendance\AttendanceRepository;
use Illuminate\Http\Request;
use PDF;

class AttendanceController extends Controller
{
    private $repo;
    private $classRepo;
    private $classSetupRepo;

    private $subjectRepo;

    private $subjectAssignRepo;

    function __construct(
        AttendanceRepository   $repo,
        ClassesRepository      $classRepo,
        ClassSetupRepository   $classSetupRepo,
        SubjectRepository      $subjectRepo,
        SubjectAssignRepository $subjectAssignRepo,
    )
    {
        $this->repo              = $repo;
        $this->classRepo         = $classRepo;
        $this->classSetupRepo    = $classSetupRepo;
        $this->subjectRepo       = $subjectRepo;
        $this->subjectAssignRepo = $subjectAssignRepo;
    }

    public function index()
    {
        $data['title']              = ___('attendance.Attendance');
        $data['classes']            = $this->classRepo->assignedAll();
        $data['sections']           = [];

        return view('backend.attendance.index', compact('data'));
    }

    public function subjectAttendance()
    {
        $data['title']              = ___('attendance.Subject Attendance');
        $data['classes']            = $this->classRepo->assignedAll();
        $data['sections']           = [];
        $data['subjects']           = collect();

        return view('backend.attendance.subject-attendance', compact('data'));
    }

    public function store(Request $request)
    {

        $result = $this->repo->store($request);
        if($result['status']){
            return redirect(route('attendance.index'))->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function storeSubjectAttendance(Request $request)
    {
        $result = $this->repo->storeSubjectWiseAttendance($request);
        if($result['status']){
            return redirect()->back()->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function searchStudents(AttendanceSearchRequest $request)
    {

        $data = $this->repo->searchStudents($request);
        $data['title']    = ___('attendance.Attendance');
        $data['request']  = $request;
        $data['students'] = $data['students'];
        $data['status']   = $data['status'];
        $data['classes']  = $this->classRepo->assignedAll();
        $data['sections'] = $this->classSetupRepo->getSections($request->class);
        return view('backend.attendance.index', compact('data'));
    }


    public function searchAttendance(SubjectAttendanceSearchRequest $request)
    {
        $data = $this->repo->searchSubjectAttendance($request);
        $data['title']    = ___('attendance.Subject Attendance'). ' ('. $data['subject_name'] .')';
        $data['request']  = $request;
        $data['classes']  = $this->classRepo->assignedAll();
        $data['sections'] = $this->classSetupRepo->getSections($request->class);
        $data['subjects'] = $this->subjectsForClassSection($request->class, $request->section);

        return view('backend.attendance.subject-attendance', compact('data'));
    }

    /**
     * Subjects assigned to a class/section (for filter dropdown).
     */
    private function subjectsForClassSection($classId, $sectionId)
    {
        if (!$classId || !$sectionId) {
            return collect();
        }

        $request = new Request([
            'classes_id' => $classId,
            'section_id' => $sectionId,
        ]);

        return collect($this->subjectAssignRepo->getSubjects($request));
    }

    // report start----------------------------------------------------------------------------------------------

    public function report()
    {
        $data['title']              = ___('attendance.Attendance');
        $data['classes']            = $this->classRepo->assignedAll();
        $data['sections']           = [];
        $data['students']           = [];
        $data['request']            = [];

        return view('backend.attendance.report', compact('data'));
    }


    public function reportSearch(AttendanceRequest $request)
    {
        $data['title']        = ___('attendance.Attendance');
        $data['request']      = $request;
        $data['classes']      = $this->classRepo->assignedAll();
        $data['sections']     = $this->classSetupRepo->getSections($request->class);
        $results              = $this->repo->searchReport($request);
        $data['students']     = $results['students'];
        $data['days']         = $results['days'];
        $data['attendances']  = $results['attendances'];
        return view('backend.attendance.report', compact('data'));
    }

    public function generatePDF(Request $request)
    {
        $results              = $this->repo->searchReportPDF($request);
        $data['students']     = $results['students'];
        $data['days']         = $results['days'];
        $data['attendances']  = $results['attendances'];
        $data['request']      = $request;

        $pdf = PDF::loadView('backend.attendance.reportPDF', compact('data'));

        if($request->view == '0')
            $pdf->setPaper('A4', 'landscape');

        return $pdf->download('attendance'.'_'.date('d_m_Y').'.pdf');
    }

    // report end----------------------------------------------------------------------------------------------
}
