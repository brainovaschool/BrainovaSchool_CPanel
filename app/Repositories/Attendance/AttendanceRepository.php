<?php

namespace App\Repositories\Attendance;

use App\Enums\AttendanceType;
use App\Jobs\NotificationSendJob;
use App\Models\Academic\Subject;
use App\Models\SubjectAttendance;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use App\Models\StudentInfo\Student;
use App\Models\Attendance\Attendance;
use App\Traits\SendNotificationTrait;
use Illuminate\Support\Facades\Notification;
use App\Jobs\StudentAttendanceNotificationJOb;
use App\Models\StudentInfo\SessionClassStudent;
use App\Interfaces\Attendance\AttendanceInterface;
use App\Notifications\StudentAttendanceNotification;

class AttendanceRepository implements AttendanceInterface
{
    use ReturnFormatTrait;
    use SendNotificationTrait;

    private $model;
    private $student;

    public function __construct(Attendance $model ,  Student $student)
    {
        $this->model  = $model;
        $this->student  = $student;
    }

    public function attendance(){
        $totalStudent = SessionClassStudent::where('session_id', setting('session'))->count();
        $data['present_student'] = $this->model->where('session_id', setting('session'))
                                    ->whereDay('date', date('d'))
                                    ->whereIn('attendance', [AttendanceType::PRESENT, AttendanceType::LATE, AttendanceType::HALFDAY])
                                    ->count();
        $data['absent_student'] = $totalStudent - $data['present_student'];
        return $data;
    }

    public function store($request)
    {

        DB::beginTransaction();
        try {
            foreach ($request->students as $key => $item) {

                if($request->status == 1)
                    $row = $this->model::find($request->items[$key]); // already submitted
                else
                $row = new $this->model; // new
                $student_details =  $this->student->with('parent:id,guardian_name,father_name,user_id')->where('id',$item)->first();

                $row->session_id                = setting('session');
                $row->classes_id                = $request->class;
                $row->section_id                = $request->section;
                $row->roll                      = $request->studentsRoll[$key];
                $row->date                      = $request->date;
                $row->student_id                = $item;
                if ($request->holiday == "on")
                    $row->attendance            = 0;
                else
                    $row->attendance            = $request->attendance[$item];
                $row->note                      = $request->note[$key];
                $row->save();

                dispatch(new StudentAttendanceNotificationJOb($student_details, $row));


                $data['student_name'] = @$row->student->first_name.' '.@$row->student->last_name;
                $data['admission_no'] = @$row->student->admission_no;
                $data['roll_no'] = @$row->student->roll_no;
                $data['class'] = @$row->class->name;
                $data['section'] = @$row->section->name;
                $data['guardian_name'] = $this->student->parent ? ($row->student->parent->father_name ?  $row->student->parent->father_name : $row->student->parent->guardian_name) : '';
                $data['attendance_date'] = dateFormat($row->date);
                $data['attendance_type'] = getAttendanceType(@$row->attendance);

                // $this->make_notification('Student_Attendance',[$row->student->user_id], $data , ['Student']);
                // $this->make_notification('Student_Attendance', [$row->student->parent->user_id] , $data , ['Parent']);

               dispatch(new NotificationSendJob('Student_Attendance', [$row->student->user_id], $data , ['Student']));
               dispatch(new NotificationSendJob('Student_Attendance', [$row->student->parent->user_id], $data , ['Parent']));
            }

            DB::commit();

            return $this->responseWithSuccess(___('alert.submitted_successfully'), []);
        } catch (\Throwable $th) {

            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function storeSubjectWiseAttendance($request)
    {

        DB::beginTransaction();
        try {
            foreach ($request->students as $key => $item) {

                if($request->status == 1)
                    $row = SubjectAttendance::find($request->items[$key]); // already submitted
                else
                    $row = new SubjectAttendance(); // new

                $row->session_id                = setting('session');
                $row->classes_id                = $request->class;
                $row->section_id                = $request->section;
                $row->subject_id                = $request->subject;
                $row->roll                      = $request->studentsRoll[$key];
                $row->date                      = $request->date;
                $row->student_id                = $item;
                if ($request->holiday == "on")
                    $row->attendance            = 0;
                else
                    $row->attendance            = $request->attendance[$item];
                $row->note                      = $request->note[$key];
                $row->save();
            }

            DB::commit();

            return $this->responseWithSuccess(___('alert.submitted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error('Subject attendance store failed: ' . $th->getMessage());

            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }


    public function searchStudents($request)
    {
        $students = Attendance::where('session_id', setting('session'))
            ->where('classes_id', $request->class)
            ->where('section_id', $request->section)
            ->where('date', $request->date)
            ->get();

        $data['status'] = 1; // already submitted

        $ids = [];
        foreach ($students as $student) {
            $ids[] = $student->student_id;
        }

        if ($students->count() == 0) {
            $data['status'] = 0; // new
        }

        $students2 = SessionClassStudent::with('student')->where('session_id', setting('session'))
            ->where('classes_id', $request->class)
            ->where('section_id', $request->section)
            ->whereNotIn('student_id', $ids)
            ->get();

        $data['students'] = $students->concat($students2);

        return $data;
    }

    public function searchSubjectAttendance($request)
    {
        $students = SubjectAttendance::where('session_id', setting('session'))
            ->where('classes_id', $request->class)
            ->where('section_id', $request->section)
            ->where('date', $request->date)
            ->where('subject_id', $request->subject)
            ->with('student')
            ->with('subject')
            ->with('session')
            ->with('class')
            ->get();

        $data['status'] = 1; // already submitted

        $ids = [];
        foreach ($students as $student) {
            $ids[] = $student->student_id;
        }

        if ($students->count() == 0) {
            $data['status'] = 0; // new
        }

        $students2 = SessionClassStudent::with(['student', 'class', 'section'])
            ->where('session_id', setting('session'))
            ->where('classes_id', $request->class)
            ->where('section_id', $request->section)
            ->whereNotIn('student_id', $ids)
            ->get();

        $data['students'] = $students->concat($students2);

        $subjectId = $request->input('subject');
        $subject = Subject::find($subjectId);

        $subjectName = $subject ? $subject->name : null;
        $data['subject_name'] = $subjectName;
        return $data;
    }


    public function searchReport($request)
    {
        $students = Attendance::query();

        $students = $students->where('session_id', setting('session'));
        if ($request->class != "") {
            $students = $students->where('classes_id', $request->class);
        }
        if ($request->section != "") {
            $students = $students->where('section_id', $request->section);
        }
        if ($request->month != "") {
            $students = $students->where('date', 'LIKE', $request->month . '%');
        }
        if ($request->date != "") {
            $students = $students->where('date', $request->date);
        }
        if ($request->roll != "") {
            $students = $students->where('roll', $request->roll);
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


        $data['days'] = getAllDaysInMonth($year, $month);

        if ($request->view == 0) {
            $students->select('student_id', 'date', 'attendance');
            $data['attendances'] = $students->get();
            $students->select('student_id', 'roll')->distinct('student_id');
            $data['students']    = $students->paginate(10);
            // dd($data);
            return $data;
        } else {
            $data['students']    = $students->paginate(10);
            $data['attendances'] = [];
            return $data;
        }
    }

    public function searchReportPDF($request)
    {

        $students = Attendance::query();
        $data = [];
        $students = $students->where('session_id', setting('session'));
        if ($request->class != "") {
            $students = $students->where('classes_id', $request->class);

        }
        if ($request->section != "") {
            $students = $students->where('section_id', $request->section);
        }
        if ($request->month != "") {
            $students = $students->where('date', 'LIKE', $request->month . '%');
        }
        if ($request->date != "") {
            $students = $students->where('date', $request->date);
        }
        if ($request->roll != "") {
            $students = $students->where('roll', $request->roll);
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


        $data['days'] = getAllDaysInMonth($year, $month);

        if ($request->view == 0) {
            $students->select('student_id', 'date', 'attendance');
            $data['attendances'] = $students->get();
            $students->select('student_id', 'roll')->distinct('student_id');
            $data['students']    = $students->paginate(10);
            return $data;
        } else {
            $data['students']    = $students->get();
            $data['attendances'] = [];
            return $data;
        }


    }
}
