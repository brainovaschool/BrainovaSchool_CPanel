<?php

namespace App\Repositories\leave;

use App\Enums\RoleEnum;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Staff\Staff;
use App\Models\Upload;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\StudentInfo\StudentRepository;
use App\Traits\CommonHelperTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestRepository
{
    use CommonHelperTrait;

    private $classRepo;
    private $studentRepo;

    public function __construct(ClassesRepository $classRepo, StudentRepository $studentRepo){
        $this->classRepo = $classRepo;
        $this->studentRepo = $studentRepo;
    }


    // ID column: roll number for student rows, staff id for staff rows -
    // eager loaded everywhere a list of leave requests is built.
    private const WITH = [
        'leaveType:id,name', 'user:id,name', 'approvedBy:id,name', 'requestedBy:id,name',
        'session:id,name', 'role:id,name', 'attachment',
        'student.session_class_student', 'staff',
    ];

    public function show($id)
    {
        return LeaveRequest::with(self::WITH)->find($id);
    }

    public function all()
    {
        return LeaveRequest::with(self::WITH)->latest()->paginate(10);
    }

    // A role with only leave_request_create (no leave_request_update, the
    // approver capability) should only ever see the leave it raised itself,
    // not the whole school's requests.
    public function mine($userId)
    {
        return LeaveRequest::with(self::WITH)
            ->where('request_by', $userId)
            ->latest()->paginate(10);
    }

    // The Approval page: pending requests only, for whoever can approve.
    public function pending()
    {
        return LeaveRequest::with(self::WITH)
            ->where('approval_status', 'pending')
            ->latest()->paginate(10);
    }

    public function setStatus($id, string $status): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        try {
            $leaveRequest = LeaveRequest::find($id);
            if (!$leaveRequest) {
                return false;
            }
            $leaveRequest->approval_status = $status;
            if ($status === 'approved') {
                $leaveRequest->approved_by = auth()->id();
            }
            $leaveRequest->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function store(Request $request): bool
    {
        try {
            if ((string) $request->role_id !== (string) RoleEnum::STUDENT) {
                return $this->createStaffLeaveRequest($request);
            }

            $isSingleStudent = $request->class !== null && $request->section !== null && $request->student !== null;
            $isEntireClass = $request->class !== null && $request->section === null && $request->student === null;
            $isEntireSection = $request->class !== null && $request->section !== null && $request->student === null;

            if ($isSingleStudent) {
                return $this->createSingleStudentLeaveRequest($request);
            }

            if ($isEntireClass || $isEntireSection) {
                return $this->createBulkLeaveRequests($request);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function createStaffLeaveRequest(Request $request): bool
    {
        $staff = Staff::find($request->staff);
        if (!$staff || !$staff->user_id) {
            return false;
        }

        if ($request->hasFile('attachment')) {
            $upload_id = $this->UploadImageCreate($request->file('attachment'), 'backend/uploads/students');
        }

        $leaveRequest = new LeaveRequest();
        $leaveRequest->fill([
            'leave_type_id' => $request->leave_type,
            'user_id' => $staff->user_id,
            // The real role (Teacher, Staff, a custom combined role, etc.)
            // comes from the staff member picked, not the Student/Staff
            // toggle in the form - that toggle only decides which picker
            // to show.
            'role_id' => $staff->role_id,
            'request_by' => auth()->id(),
            'session_id' => setting('session'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'leave_days' => $this->calculateLeaveDays($request),
            'approval_status' => hasPermission('leave_request_update') ? $request->approval_status : 'pending',
            'attachment_id' => $upload_id ?? null,
            'is_half_day' => $request->boolean('is_half_day'),
            // Substitute/make-up day - staff leave only, never set for students.
            'replacement_day' => $request->replacement_day ?: null,
        ]);

        return $leaveRequest->save();
    }

    private function createSingleStudentLeaveRequest(Request $request): bool
    {
        $leaveRequest = new LeaveRequest();
        $student = $this->studentRepo->show($request->student);

        if ($request->hasFile('attachment')){
            $upload_id         = $this->UploadImageCreate($request->file('attachment'), 'backend/uploads/students');
        }
        $leaveRequest->fill([
            'leave_type_id' => $request->leave_type,
            'user_id' => $student->user_id,
            'role_id' => $request->role_id,
            'request_by' => auth()->id(),
            'session_id' => setting('session'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'leave_days' => $this->calculateLeaveDays($request),
            // Only an approver (leave_request_update) may decide the status
            // at submission time — anyone just raising a leave always lands
            // as pending, regardless of what the hidden/posted field says.
            'approval_status' => hasPermission('leave_request_update') ? $request->approval_status : 'pending',
            'attachment_id' => $upload_id ?? null,
            'is_half_day' => $request->boolean('is_half_day'),
        ]);

        return $leaveRequest->save();
    }

    private function createBulkLeaveRequests(Request $request): bool
    {
        $students = $request->section
            ? $this->studentRepo->getStudents($request)
            : $this->studentRepo->getStudentsByClass($request->class);

        $leaveDays = $this->calculateLeaveDays($request);
        $leaveRequests = [];

        if ($request->hasFile('attachment')){
            $upload_id         = $this->UploadImageCreate($request->image, 'backend/uploads/students');
        }

        $approvalStatus = hasPermission('leave_request_update') ? $request->approval_status : 'pending';
        $isHalfDay = $request->boolean('is_half_day');
        foreach ($students as $student) {
            $leaveRequests[] = [
                'leave_type_id' => $request->leave_type,
                'user_id' => $student->student->user_id,
                'role_id' => $request->role_id,
                'request_by' => auth()->id(),
                'session_id' => setting('session'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => $request->description,
                'approval_status' => $approvalStatus,
                'leave_days' => $leaveDays,
                'attachment_id' => $upload_id ?? null,
                'is_half_day' => $isHalfDay,
            ];
        }

        return DB::transaction(fn() => LeaveRequest::insert($leaveRequests));
    }

    public function calculateLeaveDays($request)
    {
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        return $start->diffInDays($end) + 1;
    }


    public function update(Request $request): bool
    {
        $isStaff = (string) $request->role_id !== (string) RoleEnum::STUDENT;

        if (!$isStaff) {
            $request->validate([
                'section' => 'required|exists:sections,id',
                'class' => 'required|exists:classes,id',
            ]);
        }

        try {
            $leaveRequest = $this->show($request->editLeaveId);
            $isManager = hasPermission('leave_request_update');

            // Someone who can only raise leave (no approver permission) may
            // only ever touch their own request, and can never set the
            // approval status themselves — only an approver can.
            if (!$isManager && (!$leaveRequest || $leaveRequest->request_by != auth()->id())) {
                return false;
            }

            if ($request->hasFile('attachment')){
                $uploads = Upload::find($leaveRequest->attachment_id);
                if ($uploads) {
                    $uploads->delete();
                    $upload_id         = $this->UploadImageCreate($request->file('attachment'), 'backend/uploads/students');
                    $leaveRequest->attachment_id = $upload_id;
                }
            }
            if ($isStaff) {
                $staff = Staff::find($request->staff);
                if (!$staff || !$staff->user_id) {
                    return false;
                }
                $leaveRequest->user_id = $staff->user_id;
                $leaveRequest->role_id = $staff->role_id;
                $leaveRequest->replacement_day = $request->replacement_day ?: null;
            } else {
                $student = $this->studentRepo->show($request->student);
                $leaveRequest->user_id = $student->user_id;
                $leaveRequest->replacement_day = null;
            }
            $leaveRequest->leave_type_id = $request->leave_type;
            $leaveRequest->is_half_day = $request->boolean('is_half_day');
            $leaveRequest->approval_status = $isManager ? $request->approval_status : 'pending';
            if ($leaveRequest->approval_status == 'approved'){
                $leaveRequest->approved_by = auth()->id();
            }
            $leaveRequest->start_date = $request->start_date;
            $leaveRequest->end_date     = $request->end_date;
            $leaveRequest->leave_days = $this->calculateLeaveDays($request);
            $leaveRequest->description = $request->description;
            $leaveRequest->save();
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $leaveRequest = $this->show($id);
            $leaveRequest->delete();
            return true;
        }catch (\Exception $e){
            return false;
        }

    }
}
