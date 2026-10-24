<?php

namespace App\Repositories\leave;

use App\Enums\RoleEnum;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Upload;
use App\Repositories\StudentInfo\StudentRepository;
use App\Traits\CommonHelperTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveRequestRepository
{
    use CommonHelperTrait;

    private $studentRepo;

    public function __construct(StudentRepository $studentRepo){
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

    // Leave Log's filter bar - month (Y-m, matched against either boundary
    // of the request's date range), leave type, and approval status. Shared
    // by every list view (all/mine/forClassStudents) so the same filters
    // work no matter whose leave is being looked at.
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['month'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$filters['month']])
                    ->orWhereRaw("DATE_FORMAT(end_date, '%Y-%m') = ?", [$filters['month']]);
            });
        }
        if (!empty($filters['leave_type'])) {
            $query->where('leave_type_id', $filters['leave_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('approval_status', $filters['status']);
        }

        return $query;
    }

    public function all(array $filters = [])
    {
        return $this->applyFilters(LeaveRequest::with(self::WITH)->latest(), $filters)->paginate(10);
    }

    // A role with only leave_request_create (no leave_request_update, the
    // approver capability) sees their own leave history - requests ABOUT
    // them (user_id), not ones they happened to submit for someone else
    // (request_by), e.g. a teacher raising leave on behalf of a student.
    public function mine($userId, array $filters = [])
    {
        $query = LeaveRequest::with(self::WITH)->where('user_id', $userId)->latest();

        return $this->applyFilters($query, $filters)->paginate(10);
    }

    // The Approval page: pending requests only, for whoever can approve.
    public function pending()
    {
        return LeaveRequest::with(self::WITH)
            ->where('approval_status', 'pending')
            ->latest()->paginate(10);
    }

    // A Teacher's "Class Students" tab: leave requests belonging to students
    // primarily enrolled in a class/section this staff member teaches.
    public function forClassStudents(int $staffId, array $filters = [])
    {
        $userIds = $this->studentRepo->getStudentUserIdsForTeacher($staffId);

        if (empty($userIds)) {
            return LeaveRequest::with(self::WITH)->whereRaw('0 = 1')->paginate(10);
        }

        $query = LeaveRequest::with(self::WITH)
            ->where('role_id', RoleEnum::STUDENT)
            ->whereIn('user_id', $userIds)
            ->latest();

        return $this->applyFilters($query, $filters)->paginate(10);
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

    // Raising leave always means raising your own - identity comes from
    // the session, never from posted input, so there is nothing left to
    // pick: no role, no class, no staff/student lookup.
    public function store(Request $request): bool
    {
        try {
            $user = auth()->user();

            if ($request->hasFile('attachment')) {
                $upload_id = $this->UploadImageCreate($request->file('attachment'), 'backend/uploads/students');
            }

            $leaveRequest = new LeaveRequest();
            $leaveRequest->fill([
                'leave_type_id' => $request->leave_type,
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'request_by' => $user->id,
                'session_id' => setting('session'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => $request->description,
                'leave_days' => $this->calculateLeaveDays($request),
                'approval_status' => 'pending',
                'attachment_id' => $upload_id ?? null,
                'is_half_day' => $request->boolean('is_half_day'),
                // Substitute day only applies to staff, never to a student
                // raising their own leave.
                'replacement_day' => $user->staff ? ($request->replacement_day ?: null) : null,
            ]);

            return $leaveRequest->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function calculateLeaveDays($request)
    {
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        return $start->diffInDays($end) + 1;
    }


    // Editing never changes WHOSE leave this is - that was fixed at
    // creation from the session, and stays fixed. A manager may edit
    // anyone's request (details only); anyone else may only edit their own.
    public function update(Request $request): bool
    {
        $isManager = hasPermission('leave_request_update');

        try {
            $leaveRequest = $this->show($request->editLeaveId);

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
            if ((int) $leaveRequest->role_id !== RoleEnum::STUDENT) {
                $leaveRequest->replacement_day = $request->replacement_day ?: null;
            }
            $leaveRequest->leave_type_id = $request->leave_type;
            $leaveRequest->is_half_day = $request->boolean('is_half_day');
            // Approval status is untouched here - it only changes via the
            // dedicated Approval page (setStatus()), never by editing details.
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
