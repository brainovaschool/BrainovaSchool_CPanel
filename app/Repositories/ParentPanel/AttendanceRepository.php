<?php

namespace App\Repositories\ParentPanel;

use Illuminate\Http\Request;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance\Attendance;
use Illuminate\Support\Facades\Session;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;
use App\Interfaces\ParentPanel\AttendanceInterface;

class AttendanceRepository implements AttendanceInterface
{
    public function index()
    {
        try {
            $parent           = ParentGuardian::where('user_id', Auth::user()->id)->first();
            $data['students'] = Student::where('parent_guardian_id', $parent->id)->get();
            $data['student']  = Student::where('id', Session::get('student_id'))->first();

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function search($request)
    {
        try {
            Session::put('student_id', $request->student);
            $parent   = ParentGuardian::where('user_id', Auth::user()->id)->first();
            $data['students'] = Student::where('parent_guardian_id', $parent->id)->get();
            $data['student']  = Student::where('id', Session::get('student_id'))->first();

            $student        = Student::where('id', Session::get('student_id'))->first();

            // student_id alone is enough — covers attendance from every
            // class the child is enrolled in, not just one.
            $result = Attendance::query();
            $result = $result->where('session_id', setting('session'))
            ->where('student_id', $student->id);
            if($request->month != "") {
                $result = $result->where('date', 'LIKE', $request->month.'%');
            }
            if($request->date != "") {
                $result = $result->where('date', $request->date);
            }
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
