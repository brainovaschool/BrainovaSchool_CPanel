<?php

namespace App\Http\Controllers\StudentPanel;

use Carbon\Carbon;
use App\Enums\Status;
use App\Models\Gmeet;
use App\Models\Search;
use App\Models\NoticeBoard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentInfo\SessionClassStudent;
use App\Repositories\StudentPanel\DashboardRepository;
use App\Repositories\LearningEngine\LearningHomeRepository;
use App\Repositories\LearningEngine\DailyGoalRepository;
use App\Repositories\LearningEngine\ReflectionJournalRepository;

class DashboardController extends Controller
{
    private $repo;
    private $learningHome;
    private $dailyGoals;
    private $reflectionJournal;

    function __construct(DashboardRepository $repo, LearningHomeRepository $learningHome, DailyGoalRepository $dailyGoals, ReflectionJournalRepository $reflectionJournal)
    {
        $this->repo              = $repo;
        $this->learningHome      = $learningHome;
        $this->dailyGoals        = $dailyGoals;
        $this->reflectionJournal = $reflectionJournal;
    }

    public function index()
    {
        $data = $this->repo->index();

        if ($data && !empty($data['student'])) {
            try {
                $data['learning_home']      = $this->learningHome->forStudent($data['student']);
                $data['weekly_wins']        = $this->learningHome->weeklyWins($data['student']);
                $data['daily_goals']        = $this->dailyGoals->forStudent($data['student']->id);
                $data['reflection_today']   = $this->reflectionJournal->today($data['student']->id);
            } catch (\Throwable $th) {
                report($th);
            }
        }

        return view('student-panel.dashboard', compact('data'));
    }

    public function saveDailyGoals(Request $request)
    {
        $student = optional(Auth::user())->student;
        if (!$student) {
            return redirect()->route('student-panel-dashboard.index');
        }

        $this->dailyGoals->setGoals($student->id, (array) $request->input('goals', []));

        return redirect()->route('student-panel-dashboard.index')->with('success', ___('alert.updated_successfully'));
    }

    public function saveReflection(Request $request)
    {
        $student = optional(Auth::user())->student;
        if (!$student) {
            return redirect()->route('student-panel-dashboard.index');
        }

        $request->validate([
            'what_was_hard' => 'nullable|string|max:1000|required_without:what_worked',
            'what_worked'   => 'nullable|string|max:1000|required_without:what_was_hard',
        ]);

        $this->reflectionJournal->save($student->id, $request->input('what_was_hard'), $request->input('what_worked'));

        return redirect()->route('student-panel-dashboard.index')->with('success', ___('alert.updated_successfully'));
    }

    public function searchStudentMenuData(Request $request)
    {
        try {
            $search = Search::query()
                ->when(request()->filled('search'), fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
                ->where('user_type', 'Student')
                ->take(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'title' => $item->title,
                        'route_name' => route($item->route_name)
                    ];
                });


            return response()->json($search);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    public function gmeet()
    {
        $session_class_student = SessionClassStudent::where('session_id', setting('session'))->where('student_id', Auth::user()->student->id)->first();
        $data['gmeets'] = Gmeet::where('session_id', setting('session'))
            ->where('classes_id', $session_class_student->classes_id)
            ->where('section_id', $session_class_student->section_id)
            ->orderByDesc('id')
            ->paginate(10);
        $data['title']        = ___('common.gmeet');
        return view('student-panel.gmeet', compact('data'));
    }

    public function notices()
    {
        $currentDateTime = Carbon::now(); // Get the current datetime
        $role_id = Auth::user()->role_id;
        $session_class_student = Auth::user()->student->session_class_student;
        $user = Auth::user();

        $sectionId = @$session_class_student->section_id;
        $classId = @$session_class_student->classes_id;
        $studentId = @$session_class_student->student_id;
        $departmentId = @$user->student->department_id;

        $data['notice-boards'] =  NoticeBoard::where('status', 1) // Match active notices
            ->whereJsonContains('visible_to', "$role_id")
            ->where(function ($query) use ($classId) {
                return $query->where('class_id', $classId)
                    ->orWhereNull('class_id');
            })
            ->where(function ($query) use ($sectionId) {
                return $query->where('section_id', $sectionId)
                    ->orWhereNull('section_id');
            })
            ->where(function ($query) use ($studentId) {
                return $query->where('student_id', $studentId)
                    ->orWhereNull('student_id');
            })
            ->where(function ($query) use ($departmentId) {
                return $query->where('department_id', $departmentId)
                    ->orWhereNull('department_id');
            })
            ->orderByDesc('publish_date') // Order by most recent publish date
            ->paginate(10);

        $data['title']        = ___('common.notice boards');
        return view('student-panel.notices', compact('data'));
    }
}
