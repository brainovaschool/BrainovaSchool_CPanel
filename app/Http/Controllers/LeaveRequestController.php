<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\LeaveRequestStoreRequest;
use App\Models\Academic\Section;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\Student;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\leave\LeaveRequestRepository;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    private $leaveRequestRepo;
    private $classRepo;

    public function __construct(LeaveRequestRepository $leaveRequestRepo, ClassesRepository $classRepo){
        $this->leaveRequestRepo = $leaveRequestRepo;
        $this->classRepo = $classRepo;
    }

    public function index(){
        $isManager = hasPermission('leave_request_update');
        $data['leave_requests'] = $isManager
            ? $this->leaveRequestRepo->all()
            : $this->leaveRequestRepo->mine(auth()->id());
        $data['isManager'] = $isManager;
        $data['title'] = ___('leave.Leave Request');
        $data['types'] = LeaveType::where('active_status', true)->get();

        $data['classes'] = $this->classRepo->assignedAll();
        $data['sections'] = [];

        $data['students'] = [];
        $data['staffs'] = Staff::with('role')->active()->orderBy('first_name')->get();
        return view('backend.leave.request', compact('data'));
    }

    public function store(LeaveRequestStoreRequest $request)
    {

        if ($request->editLeaveId){
            $result = $this->leaveRequestRepo->update($request);
        }else{
            $result = $this->leaveRequestRepo->store($request);
        }

        if ($result) {
            return redirect()->back()->with('success', ___('alert.updated_successfully'));
        }
        return redirect()->back()->with('danger', ___('alert.something_went_wrong_please_try_again'));
    }


    public function edit($id)
    {
        $editLeave = $this->leaveRequestRepo->show($id);
        $isManager = hasPermission('leave_request_update');
        if (!$isManager && (!$editLeave || $editLeave->request_by != auth()->id())) {
            abort(403);
        }

        $data['leave_requests'] = $isManager
            ? $this->leaveRequestRepo->all()
            : $this->leaveRequestRepo->mine(auth()->id());
        $data['isManager'] = $isManager;
        $data['title'] = ___('leave.Leave Request');
        $data['types'] = LeaveType::where('active_status', true)->get();

        $data['classes'] = $this->classRepo->assignedAll();
        $data['sections'] = [];

        $data['students'] = [];
        $data['staffs'] = Staff::with('role')->active()->orderBy('first_name')->get();

        if ((int) $editLeave->role_id === RoleEnum::STUDENT) {
            $student = Student::with('session_class_student')
                ->where('user_id', $editLeave->user_id)->first();

            $data['class_id'] = $student->session_class_student->classes_id ?? null;
            $data['section'] = $student->session_class_student->section ?? null;
            $data['student_id'] = $student->id ?? null;
        } else {
            $data['staff_id'] = optional(Staff::where('user_id', $editLeave->user_id)->first())->id;
        }

        return view('backend.leave.request', compact('data', 'editLeave'));
    }

    public function delete($id)
    {
        $leaveRequest = $this->leaveRequestRepo->show($id);
        if (!hasPermission('leave_request_update') && (!$leaveRequest || $leaveRequest->request_by != auth()->id())) {
            abort(403);
        }

        $result = $this->leaveRequestRepo->delete($id);
        if ($result) {
            $success[0] = 'Deleted Successfully';
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
            return response()->json($success);
        }else{
            $response = [
                'message' => 'Something went wrong!',
                'status' => 'error',
                'title' => ___('alert.oops'),
                'button' => ___('alert.OK'),
            ];

            return response()->json($response);
        }
    }

}
