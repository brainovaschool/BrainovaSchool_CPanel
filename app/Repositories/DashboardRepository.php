<?php

namespace App\Repositories;

use App\Enums\AttendanceType;
use App\Interfaces\DashboardInterface;
use App\Models\Academic\Classes;
use App\Models\Academic\ClassSetup;
use App\Models\Accounts\Expense;
use App\Models\Accounts\Income;
use App\Models\Attendance\Attendance;
use App\Models\Event;
use App\Models\Fees\FeesCollect;
use App\Models\Role;
use App\Models\Session;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Auth;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectAssignChildren;

class DashboardRepository implements DashboardInterface
{
    public function index()
    {
        $sessionId = setting('session');
        $data['teacher_assigned_subjects'] = collect();
        // distinct() — a student with a secondary (short-course) enrollment
        // has 2+ rows here; this stat should still count the person once.
        $data['student'] = SessionClassStudent::where('session_id', $sessionId)->distinct('student_id')->count('student_id');
        $data['parent']  = ParentGuardian::count();
        $data['teacher'] = Staff::where('role_id',5)->count();
        $data['session'] = Session::count();

        $data['events']  = Event::where('session_id', $sessionId)->active()->where('date', '>=', date('Y-m-d'))->orderBy('date')->take(5)->get();

        $data['income']  = Income::where('session_id', $sessionId)->sum('amount');
        $data['expense'] = Expense::where('session_id', $sessionId)->sum('amount');
        $data['balance'] = $data['income'] - $data['expense'];

        $data['teacher_classes_count'] = null;
        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            $staffId    = (int) Auth::user()->staff->id;
            $childTable = (new SubjectAssignChildren())->getTable();
            $data['teacher_classes_count'] = (int) SubjectAssignChildren::query()
                ->where($childTable . '.staff_id', $staffId)
                ->join('subject_assigns', 'subject_assigns.id', '=', $childTable . '.subject_assign_id')
                ->where('subject_assigns.session_id', $sessionId)
                ->selectRaw('COUNT(DISTINCT subject_assigns.classes_id) as cnt')
                ->value('cnt');

            $data['student'] = $this->countSessionStudentsForTeacherAssignments($staffId, $sessionId);
            $data['teacher_assigned_subjects'] = $this->teacherAssignedSubjectsForStaff($staffId, $sessionId);
        }

        return $data;
    }

    /**
     * Distinct subjects this staff member teaches in the current session (subject assign children).
     */
    private function teacherAssignedSubjectsForStaff(int $staffId, $sessionId)
    {
        $childTable = (new SubjectAssignChildren())->getTable();
        $subjectIds = SubjectAssignChildren::query()
            ->where($childTable . '.staff_id', $staffId)
            ->join('subject_assigns', 'subject_assigns.id', '=', $childTable . '.subject_assign_id')
            ->where('subject_assigns.session_id', $sessionId)
            ->distinct()
            ->pluck($childTable . '.subject_id');

        if ($subjectIds->isEmpty()) {
            return collect();
        }

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Students in the current session whose class/section matches a subject assignment for this staff member.
     */
    private function countSessionStudentsForTeacherAssignments(int $staffId, $sessionId): int
    {
        $pairs = $this->teacherAssignedClassSectionPairs($staffId, $sessionId);
        if ($pairs->isEmpty()) {
            return 0;
        }

        return SessionClassStudent::query()
            ->where('session_id', $sessionId)
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $p) {
                    $q->orWhere(function ($qq) use ($p) {
                        $qq->where('classes_id', $p->classes_id)->where('section_id', $p->section_id);
                    });
                }
            })
            ->count();
    }

    private function teacherAssignedClassSectionPairs(int $staffId, $sessionId)
    {
        $childTable = (new SubjectAssignChildren())->getTable();

        return SubjectAssignChildren::query()
            ->where($childTable . '.staff_id', $staffId)
            ->join('subject_assigns', 'subject_assigns.id', '=', $childTable . '.subject_assign_id')
            ->where('subject_assigns.session_id', $sessionId)
            ->selectRaw('DISTINCT subject_assigns.classes_id, subject_assigns.section_id')
            ->get();
    }

    /**
     * Distinct class IDs this staff member teaches in the session (subject assignments).
     */
    private function teacherAssignedClassIds(int $staffId, $sessionId)
    {
        $childTable = (new SubjectAssignChildren())->getTable();

        return SubjectAssignChildren::query()
            ->where($childTable . '.staff_id', $staffId)
            ->join('subject_assigns', 'subject_assigns.id', '=', $childTable . '.subject_assign_id')
            ->where('subject_assigns.session_id', $sessionId)
            ->distinct()
            ->pluck('subject_assigns.classes_id')
            ->values();
    }

    public function feesCollectionYearly() {
        $data = [];
        for($i = 1; $i <= 12; $i++) {
            $data[] = FeesCollect::where('session_id', setting('session'))->whereMonth('date', $i)->sum('amount');
        }
        return $data;
    }

    public function revenueYearly() {
        $data['income']  = [];
        $data['expense'] = [];
        $data['revenue'] = [];

        $n = 0;
        for($i = 1; $i <= date('m'); $i++) {
            $data['income'][]  = Income::where('session_id', setting('session'))->whereMonth('date', $i)->sum('amount');
            $data['expense'][] = Expense::where('session_id', setting('session'))->whereMonth('date', $i)->sum('amount');
            $data['revenue'][] = $data['income'][$n] - $data['expense'][$n];
            $n++;
        }
        return $data;
    }

    public function feesCollection() {
        for ($i = 1; $i <=  date('t'); $i++) {
            $data['collection'][] = FeesCollect::where('session_id', setting('session'))->whereMonth('date', date('m'))->whereDay('date', $i)->sum('amount');
            $data['dates'][]      = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
        return response()->json($data, 200);
    }

    public function incomeExpense() {
        for ($i = 1; $i <=  date('t'); $i++) {
            $data['incomes'][]  = Income::where('session_id', setting('session'))->whereMonth('date', date('m'))->whereDay('date', $i)->sum('amount');
            $data['expenses'][] = Expense::where('session_id', setting('session'))->whereMonth('date', date('m'))->whereDay('date', $i)->sum('amount');
            $data['dates'][]    = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
        return response()->json($data, 200);
    }

    public function attendance() {
        $sessionId = setting('session');
        $items = ClassSetup::active()->where('session_id', $sessionId);

        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            $classIds = $this->teacherAssignedClassIds((int) Auth::user()->staff->id, $sessionId);
            $items = $classIds->isEmpty()
                ? collect()
                : $items->whereIn('classes_id', $classIds)->get();
        } else {
            $items = $items->get();
        }

        $data['classes'] = [];
        $data['present'] = [];
        $data['absent']  = [];

        $data['classes'] = [];
        foreach ($items as $key => $value) {
            $data['classes'][] = $value->class->name;
            $data['present'][] = Attendance::where('session_id', $sessionId)
                                ->where('classes_id', $value->classes_id)
                                ->whereDay('date', date('d'))
                                ->whereIn('attendance', [AttendanceType::PRESENT, AttendanceType::LATE, AttendanceType::HALFDAY])
                                ->count();
            $data['absent'][]  = Attendance::where('session_id', $sessionId)
                                ->where('classes_id', $value->classes_id)
                                ->whereDay('date', date('d'))
                                ->where('attendance', AttendanceType::ABSENT)
                                ->count();
        }
        return $data;
    }

    public function eventsCurrentMonth() {
        $events = Event::where('session_id', setting('session'))->active()->whereMonth('date', date('m'))->orderBy('date')->get();
        $data = [];
        foreach ($events as $key => $value) {
            $data[] = [
                'title' => $value->title,
                'start' => $value->date.'T'.$value->start_time,
                'end'   => $value->date.'T'.$value->end_time,
            ];
        }
        return response()->json($data, 200);
    }

}
