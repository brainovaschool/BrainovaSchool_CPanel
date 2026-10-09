<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalAttendance;
use App\Models\Staff\Staff;

class AttendanceRepository
{
    /** Every staff member for that date, each paired with their attendance
     *  row if they signed in that day (or null if they haven't yet). */
    public function forDate(string $date)
    {
        $attendanceByStaff = PortalAttendance::where('date', $date)->get()->keyBy('staff_id');

        return Staff::with('role')->orderBy('first_name')->get()->map(function (Staff $staff) use ($attendanceByStaff) {
            return [
                'staff'      => $staff,
                'attendance' => $attendanceByStaff->get($staff->id),
            ];
        });
    }
}
