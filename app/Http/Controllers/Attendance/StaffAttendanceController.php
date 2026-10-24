<?php

namespace App\Http\Controllers\Attendance;

use App\Enums\RoleEnum;
use App\Exports\StaffAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Repositories\Attendance\StaffAttendanceRepository;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StaffAttendanceController extends Controller
{
    private $repo;

    public function __construct(StaffAttendanceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        // Default to the current month on first visit so the page isn't empty.
        if (!$request->filled('month')) {
            $request->merge(['month' => date('Y-m')]);
        }

        $data['title']    = ___('settings.Teacher Attendance');
        $data['roles']    = Role::whereNotIn('id', [RoleEnum::STUDENT, RoleEnum::GUARDIAN])->orderBy('name')->get();
        $data['staffs']   = $this->repo->staffForRole($request->role);
        $data['request']  = $request;

        $results = $this->repo->searchGrid($request);
        $data['staffList']   = $results['staffList'];
        $data['days']        = $results['days'];
        $data['statusGrid']  = $results['statusGrid'];
        $data['summary']     = $results['summary'];

        return view('backend.attendance.teacher', compact('data'));
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->repo->searchForExport($request);

        return Excel::download(new StaffAttendanceExport($rows), 'teacher-attendance-' . date('Y-m-d') . '.xlsx');
    }
}
