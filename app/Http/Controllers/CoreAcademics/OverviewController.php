<?php

namespace App\Http\Controllers\CoreAcademics;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Repositories\Homework\HomeworkRepository;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    private $homeworkRepo;

    public function __construct(HomeworkRepository $homeworkRepo)
    {
        $this->homeworkRepo = $homeworkRepo;
    }

    public function index()
    {
        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }

        /** Same category record the public Our Programs page reads for
         *  its own banner - one place (Website Setup > Course Catalogue >
         *  Program Category > Edit) to set tagline/title/subtitle/image
         *  for this category everywhere it's shown. Renamed from "Core
         *  Academics" to "Academics Boost" - the route/controller/view
         *  keep the old internal name, only the category name changed. */
        $category = ProgramCategory::where('name', 'Academics Boost')->with('upload')->first();
        $data['category'] = $category;

        /** Scoped to the "Academics Boost" category - the academic subjects
         *  themselves, not the Beyond Academics skills catalogue. */
        $data['featured'] = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        // Course Details: every program in this category (admin-only page,
        // so no teacher/coordinator scoping needed here), with the full
        // model + relations the lessons/homework block needs.
        $data['courseOptions'] = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        $selectedId = (int) request()->query('program');
        $selectedOption = $data['courseOptions']->firstWhere('id', $selectedId) ?? $data['courseOptions']->first();

        $data['selectedProgram'] = $selectedOption
            ? Program::with('teachers')->find($selectedOption->id)
            : null;

        $data['contentModules'] = $data['selectedProgram'] ? $data['selectedProgram']->contentModules() : collect();

        $allHomeworks = $data['contentModules']->flatMap(fn ($m) => $m->lessons)->flatMap(fn ($l) => $l->homeworks);
        $data['homeworkStats'] = $allHomeworks->isNotEmpty()
            ? $this->homeworkRepo->getSubmissionStatsForHomeworks($allHomeworks)
            : [];

        $data['title'] = 'Academics Boost — Overview';

        return view('core-academics.overview', compact('data'));
    }
}
