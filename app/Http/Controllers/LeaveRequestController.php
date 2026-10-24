<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\LeaveRequestStoreRequest;
use App\Models\LeaveType;
use App\Repositories\leave\LeaveRequestRepository;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    private $leaveRequestRepo;

    public function __construct(LeaveRequestRepository $leaveRequestRepo){
        $this->leaveRequestRepo = $leaveRequestRepo;
    }

    // A Teacher sees two tabs: her own leave, and her class students' leave.
    // Everyone else (including a manager, who already sees the full list)
    // just gets the single list.
    private function teacherStaffId(): ?int
    {
        $user = auth()->user();
        if ($user && (int) $user->role_id === 5 && $user->staff) {
            return (int) $user->staff->id;
        }
        return null;
    }

    public function index(Request $request){
        $isManager = hasPermission('leave_request_update');
        $teacherStaffId = $this->teacherStaffId();
        $view = $request->query('view', 'mine');
        $filters = [
            'month' => $request->query('month'),
            'leave_type' => $request->query('leave_type'),
            'status' => $request->query('status'),
        ];

        if ($isManager) {
            $data['leave_requests'] = $this->leaveRequestRepo->all($filters);
        } elseif ($teacherStaffId !== null && $view === 'class') {
            $data['leave_requests'] = $this->leaveRequestRepo->forClassStudents($teacherStaffId, $filters);
        } else {
            $data['leave_requests'] = $this->leaveRequestRepo->mine(auth()->id(), $filters);
            $view = 'mine';
        }

        $data['showClassTab'] = !$isManager && $teacherStaffId !== null;
        $data['activeView'] = $view;
        $data['filters'] = $filters;
        $data['types'] = LeaveType::where('active_status', true)->get();
        $data['title'] = ___('leave.Leave Log');
        return view('backend.leave.request', compact('data'));
    }

    // Same shape as the student self-service page (one page: a short form
    // to raise leave, plus your own leave list beside it) - just with
    // Substitute Day added for staff.
    public function create()
    {
        $data['title'] = ___('leave.Leave Request');
        $data['types'] = LeaveType::where('active_status', true)->get();
        $data['selfIsStaff'] = (bool) auth()->user()->staff;
        $data['leave_requests'] = $this->leaveRequestRepo->mine(auth()->id());

        return view('backend.leave.request-form', compact('data'));
    }

    public function store(LeaveRequestStoreRequest $request)
    {

        if ($request->editLeaveId){
            $result = $this->leaveRequestRepo->update($request);
        }else{
            $result = $this->leaveRequestRepo->store($request);
        }

        if ($result) {
            return redirect()->route('leave-request.index')->with('success', ___('alert.updated_successfully'));
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

        $data['title'] = ___('leave.Leave Request');
        $data['types'] = LeaveType::where('active_status', true)->get();
        // Whose leave this is never changes on edit - just whether
        // Substitute Day applies, fixed by what the record already is.
        $data['selfIsStaff'] = (int) $editLeave->role_id !== RoleEnum::STUDENT;
        $data['leave_requests'] = $this->leaveRequestRepo->mine($editLeave->user_id);

        return view('backend.leave.request-form', compact('data', 'editLeave'));
    }

    public function approval()
    {
        $data['leave_requests'] = $this->leaveRequestRepo->pending();
        $data['title'] = ___('leave.Leave Approval');
        return view('backend.leave.approval', compact('data'));
    }

    public function approve($id)
    {
        $result = $this->leaveRequestRepo->setStatus($id, 'approved');
        return redirect()->back()->with(
            $result ? 'success' : 'danger',
            $result ? ___('alert.updated_successfully') : ___('alert.something_went_wrong_please_try_again')
        );
    }

    public function reject($id)
    {
        $result = $this->leaveRequestRepo->setStatus($id, 'rejected');
        return redirect()->back()->with(
            $result ? 'success' : 'danger',
            $result ? ___('alert.updated_successfully') : ___('alert.something_went_wrong_please_try_again')
        );
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
