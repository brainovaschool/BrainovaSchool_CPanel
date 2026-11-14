<?php

namespace App\Http\Controllers\StudentPanel;

use App\Http\Controllers\Controller;
use App\Repositories\StudentPanel\CourseCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** One controller for all 4 category pages (Homeschooling, Academics
 *  Boost, Beyond Academics, Beyond Screens) rather than 4 near-identical
 *  ones - the admin side duplicates per category because each of those
 *  pages existed before this feature and evolved independently; these
 *  are brand new, so there's nothing to avoid consolidating. */
class CourseCategoryController extends Controller
{
    private $repo;

    public function __construct(CourseCategoryRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, string $category)
    {
        if (!array_key_exists($category, CourseCategoryRepository::CATEGORIES)) {
            abort(404);
        }

        $student   = Auth::user()->student;
        $programId = (int) $request->query('program') ?: null;

        $dashboard = $student
            ? $this->repo->dashboardForStudent($student, $category, $programId)
            : ['category' => null, 'programs' => collect(), 'selectedProgram' => null, 'progress' => null];

        $data['categorySlug']    = $category;
        $data['categoryTitle']   = CourseCategoryRepository::CATEGORIES[$category];
        $data['category']        = $dashboard['category'];
        $data['courseOptions']   = $dashboard['programs'];
        $data['selectedProgram'] = $dashboard['selectedProgram'];
        $data['progress']        = $dashboard['progress'];
        $data['title']           = $data['categoryTitle'];

        return view('student-panel.course-category.index', compact('data'));
    }

    public function submit(Request $request)
    {
        $student = Auth::user()->student;
        if (!$student) {
            return back()->with('danger', "This isn't available to you.");
        }

        $request->validate([
            'program_class_id' => 'required|integer',
            'assessment_type'  => 'required|string|in:quiz,project,worksheet,assignment',
            'link_url'         => 'nullable|string|max:500',
            'file'             => 'nullable|file|max:51200',
            'category'         => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(CourseCategoryRepository::CATEGORIES))],
            'program_id'       => 'required|integer',
        ]);

        $result = $this->repo->submit($student, $request);

        $redirectUrl = route('student-panel-course.index', $request->input('category')) . '?program=' . $request->input('program_id') . '#course-details';

        return redirect($redirectUrl)->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
