<?php

namespace App\Repositories\StudentPanel;

use Illuminate\Http\Request;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance\Attendance;
use App\Models\StudentInfo\SessionClassStudent;
use App\Interfaces\StudentPanel\AttendanceInterface;

class AttendanceRepository implements AttendanceInterface
{
    public function search($request)
    {
        try {
            $student        = Student::where('user_id', Auth::user()->id)->first();

            // student_id alone is enough — a student enrolled in more than
            // one class (a short course alongside their main class) gets
            // attendance marked separately per class, and should see all
            // of it, not just whichever one class this used to filter to.
            $result = Attendance::query();
            $result = $result->where('session_id', setting('session'))
            ->where('student_id', $student->id);
            if($request->month != "") {
                $result = $result->where('date', 'LIKE', $request->month.'%');
            }
            if($request->date != "") {
                $result = $result->where('date', $request->date);
            }

            $year = 0;
            $month = 0;
            if ($request->month != "") {
                $abc = explode('-', $request->month);
                $year = $abc[0];
                $month = $abc[1];
            }


            if ($request->date != "") {
                $abc   = explode('-', $request->date);
                $year  = $abc[0];
                $month = $abc[1];
            }

            $data = [];
            $data['days'] = getAllDaysInMonth($year, $month);


            if($request->view == 0) {
                $data['results'] = $result->get();
            }
            else{
                $data['results'] = $result->paginate(10);
            }

            return $data;

        } catch (\Throwable $th) {
            return false;
        }
    }
}
