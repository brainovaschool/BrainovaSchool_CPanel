<?php

namespace App\Http\Controllers\StudentPanel;

use App\Http\Controllers\Controller;
use App\Repositories\StudentPanel\CourseCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** One controller for all 4 categories (Homeschooling, Academics Boost,
 *  Beyond Academics, Beyond Screens) rather than 4 near-identical ones -
 *  the admin side duplicates per category because each of those pages
 *  existed before this feature and evolved independently; these are
 *  brand new, so there's nothing to avoid consolidating. Split into 3
 *  actions/pages (Overview / Course Details / Progress & Report), same
 *  split the admin/teacher side uses, rather than 1 page with 3 anchors. */
class CourseCategoryController extends Controller
{
    private $repo;

    public function __construct(CourseCategoryRepository $repo)
    {
        $this->repo = $repo;
    }

    private function assertCategory(string $category): void
    {
        if (!array_key_exists($category, CourseCategoryRepository::CATEGORIES)) {
            abort(404);
        }
    }

    public function overview(string $category)
    {
        $this->assertCategory($category);

        $result = $this->repo->overviewData($category);

        $data['categorySlug']  = $category;
        $data['categoryTitle'] = CourseCategoryRepository::CATEGORIES[$category];
        $data['category']      = $result['category'];
        $data['featured']      = $result['featured'];
        $data['title']         = $data['categoryTitle'];

        return view('student-panel.course-category.overview', compact('data'));
    }

    public function courseDetails(Request $request, string $category)
    {
        $this->assertCategory($category);

        $student   = Auth::user()->student;
        $programId = (int) $request->query('program') ?: null;

        $result = $student
            ? $this->repo->courseDetailsData($student, $category, $programId)
            : ['programs' => collect(), 'selectedProgram' => null, 'progress' => null];

        $data['categorySlug']    = $category;
        $data['categoryTitle']   = CourseCategoryRepository::CATEGORIES[$category];
        $data['courseOptions']   = $result['programs'];
        $data['selectedProgram'] = $result['selectedProgram'];
        $data['progress']        = $result['progress'];
        $data['title']           = $data['categoryTitle'] . ' — Course Details';

        return view('student-panel.course-category.course-details', compact('data'));
    }

    public function progressReport(Request $request, string $category)
    {
        $this->assertCategory($category);

        $student   = Auth::user()->student;
        $programId = (int) $request->query('program') ?: null;

        $result = $student
            ? $this->repo->progressReportData($student, $category, $programId)
            : ['programs' => collect(), 'selectedProgram' => null, 'progress' => null];

        $data['categorySlug']    = $category;
        $data['categoryTitle']   = CourseCategoryRepository::CATEGORIES[$category];
        $data['courseOptions']   = $result['programs'];
        $data['selectedProgram'] = $result['selectedProgram'];
        $data['progress']        = $result['progress'];
        $data['title']           = $data['categoryTitle'] . ' — Progress & Report';

        return view('student-panel.course-category.progress-report', compact('data'));
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

        $redirectUrl = route('student-panel-course.course-details', $request->input('category')) . '?program=' . $request->input('program_id');

        return redirect($redirectUrl)->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
