<?php

namespace App\Repositories\StudentPanel;

use App\Models\Event;
use App\Models\Homework;
use App\Models\HomeworkStudent;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;
use App\Models\Academic\ClassRoutine;
use App\Models\Academic\SubjectAssign;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\StudentInfo\SessionClassStudent;
use App\Interfaces\StudentPanel\DashboardInterface;

class DashboardRepository implements DashboardInterface
{
    public function index()
    {
        try {
            $student = Student::where('user_id', Auth::user()->id)->first();
            $classSection   = SessionClassStudent::where('session_id', setting('session'))
                            ->where('student_id', @$student->id)
                            ->first();
            
            $subjectTeacher = SubjectAssign::where('session_id', setting('session'))
                            ->where('classes_id', @$classSection->classes_id)
                            ->where('section_id', @$classSection->section_id)
                            ->first();
            $data['totalSubject']   = SubjectAssignChildren::where('subject_assign_id', @$subjectTeacher->id)
                            ->distinct('subject_id')
                            ->count();
            $data['subjects']       = SubjectAssignChildren::where('subject_assign_id', @$subjectTeacher->id)
                            ->with('subject')
                            ->get()
                            ->pluck('subject')
                            ->filter()
                            ->unique('id')
                            ->sortBy('name')
                            ->values();
            $data['totalTeacher']   = SubjectAssignChildren::where('subject_assign_id', @$subjectTeacher->id)
                            ->distinct('staff_id')
                            ->count();
            $data['totalClass']     = ClassRoutine::where('classes_id', @$classSection->classes_id)
                            ->where('section_id', @$classSection->section_id)
                            ->where('session_id', setting('session'))
                            ->count();
            $data['totalEvent']     = Event::where('session_id', setting('session'))
                            ->active()->where('date', '>=', date('Y-m-d'))
                            ->orderBy('date')
                            ->count();
            $data['events']         = Event::where('session_id', setting('session'))
                            ->active()->where('date', '>=', date('Y-m-d'))
                            ->orderBy('date')
                            ->take(5)
                            ->get();
            $data['student'] = $student;

            $data['homework_total_marks'] = null;
            if ($student) {
                $sessionId = setting('session');
                $hwTable = (new Homework)->getTable();
                $hwStats = HomeworkStudent::query()
                    ->join($hwTable, $hwTable . '.id', '=', 'homework_students.homework_id')
                    ->where('homework_students.student_id', $student->id)
                    ->whereNotNull('homework_students.marks')
                    ->where($hwTable . '.session_id', $sessionId)
                    ->selectRaw('COUNT(*) as c, SUM(homework_students.marks) as sum_m')
                    ->first();
                if ($hwStats && (int) $hwStats->c > 0) {
                    $data['homework_total_marks'] = round((float) $hwStats->sum_m, 1);
                }
            }

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
