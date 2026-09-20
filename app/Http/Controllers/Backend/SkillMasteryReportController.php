<?php

namespace App\Http\Controllers\Backend;

use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\LearningEngine\LearningHomeRepository;

/** Teacher/admin view of skill mastery per class — growth and mastery per
 *  student, deliberately never sorted or scored as a ranking. */
class SkillMasteryReportController extends Controller
{
    private $learningHome;

    public function __construct(LearningHomeRepository $learningHome)
    {
        $this->learningHome = $learningHome;
    }

    private function guard(): void
    {
        if (!Auth::check() || !in_array((int) Auth::user()->role_id, [1, 5], true)) {
            abort(403);
        }
    }

    /** How to read this report — what the four stages mean, where the data
     *  comes from, and why there's deliberately no ranking. */
    public function guide()
    {
        $this->guard();

        $data['title'] = 'How to read this report';

        return view('backend.skill-mastery-report.guide', compact('data'));
    }

    public function index(Request $request)
    {
        $this->guard();

        $data['title']    = 'Skill Mastery Report';
        $data['classes']  = Classes::active()->orderBy('name')->get();
        $data['sections'] = Section::active()->orderBy('name')->get();

        $data['selected_class']   = $request->input('classes_id');
        $data['selected_section'] = $request->input('section_id');

        $data['snapshot'] = null;
        if ($data['selected_class']) {
            $data['snapshot'] = $this->learningHome->classSnapshot(
                (int) $data['selected_class'],
                $data['selected_section'] ? (int) $data['selected_section'] : null
            );
        }

        return view('backend.skill-mastery-report.index', compact('data'));
    }
}
