<?php

namespace App\Http\Controllers\ParentPanel;

use Carbon\Carbon;
use App\Enums\Status;
use App\Models\Search;
use App\Models\NoticeBoard;
use Illuminate\Http\Request;
use App\Models\StudentInfo\Student;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\StudentInfo\SessionClassStudent;
use App\Repositories\ParentPanel\DashboardRepository;
use App\Repositories\LearningEngine\LearningHomeRepository;

class DashboardController extends Controller
{
    private $repo;
    private $learningHome;

    function __construct(DashboardRepository $repo, LearningHomeRepository $learningHome)
    {
        $this->repo         = $repo;
        $this->learningHome = $learningHome;
    }

    public function index()
    {
        $data = $this->repo->index();
        $this->attachLearningSnapshot($data);
        return view('parent-panel.dashboard', compact('data'));
    }

    public function search(Request $request)
    {
        $data = $this->repo->search($request);
        $this->attachLearningSnapshot($data);
        return view('parent-panel.dashboard', compact('data'));
    }

    private function attachLearningSnapshot(&$data): void
    {
        if ($data && !empty($data['student'])) {
            try {
                $data['learning_home'] = $this->learningHome->forStudent($data['student']);
                $data['weekly_wins']   = $this->learningHome->weeklyWins($data['student']);
            } catch (\Throwable $th) {
                report($th);
            }
        }
    }

    /** Plain-language explainer for everything the Learning Snapshot panel
     *  shows — Brain Level, Knowledge Tree, mastery stages, badges, etc.
     *  Static content, no student-specific data needed. */
    public function learningGuide()
    {
        $data['title'] = ___('common.understanding_your_childs_dashboard');
        return view('parent-panel.learning-guide', compact('data'));
    }

    public function searchParentMenuData(Request $request){
        try {
            $search = Search::query()
                    ->when(request()->filled('search'), fn ($q) => $q->where('title', 'like', '%' . $request->search . '%'))
                    ->where('user_type', 'Parent')
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

    public function notices()
    {
        $currentDateTime = now();
        $role_id = Auth::user()->role_id;

        // Fetch student's class and section IDs
        $studentIds = optional(Auth::user()->parent)->children->pluck("id") ?? collect();
        $classesIds = SessionClassStudent::whereIn("student_id", $studentIds)->pluck("classes_id")->unique();
        $sectionIds = SessionClassStudent::whereIn("student_id", $studentIds)->pluck("section_id")->unique();

        // Fetch notices with optimized query
        $data['notice-boards'] = NoticeBoard::where([
            ['status', Status::ACTIVE],
            ['publish_date', '<=', $currentDateTime],
        ])
        ->whereJsonContains('visible_to', (string) $role_id)
        ->where(function ($query) use ($studentIds, $classesIds, $sectionIds) {
            $query->whereIn("student_id", $studentIds)
            ->orWhereNull("student_id")
            ->orWhereIn("class_id", $classesIds)
            ->orWhereNull("class_id")
            ->orWhereIn("section_id", $sectionIds)
            ->orWhereNull("section_id");
        })
        ->latest('id')
        ->paginate(10);

        $data['title'] = __('common.notice boards');
        return view('parent-panel.notices', compact('data'));
    }
}
