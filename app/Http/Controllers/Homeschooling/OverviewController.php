<?php

namespace App\Http\Controllers\Homeschooling;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Repositories\Homework\HomeworkRepository;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Split into 3 pages (Overview / Course Details / Progress & Report)
 *  rather than one page with 3 anchor sections. */
class OverviewController extends Controller
{
    private $homeworkRepo;
    private $markRepo;

    public function __construct(HomeworkRepository $homeworkRepo, ProgramClassMarkRepository $markRepo)
    {
        $this->homeworkRepo = $homeworkRepo;
        $this->markRepo     = $markRepo;
    }

    private function guard(): void
    {
        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }
    }

    /** Same category record the public Our Programs page reads for its
     *  own banner - one place (Website Setup > Course Catalogue > Program
     *  Category > Edit) to set tagline/title/subtitle/image for this
     *  category everywhere it's shown. */
    private function category(): ?ProgramCategory
    {
        return ProgramCategory::where('name', 'Homeschooling')->with('upload')->first();
    }

    private function courseOptions(?ProgramCategory $category)
    {
        return Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->get(['id', 'title']);
    }

    public function overview()
    {
        $this->guard();
        $category = $this->category();
        $data['category'] = $category;

        /** Scoped to the "Homeschooling" category - the full online
         *  school programme, not the Beyond Academics skills catalogue. */
        $data['featured'] = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        $data['title'] = 'Homeschooling — Overview';

        return view('homeschooling.overview', compact('data'));
    }

    public function courseDetails(Request $request)
    {
        $this->guard();
        $category = $this->category();

        $data['courseOptions'] = $this->courseOptions($category);

        $selectedId     = (int) $request->query('program');
        $selectedOption = $data['courseOptions']->firstWhere('id', $selectedId) ?? $data['courseOptions']->first();

        $data['selectedProgram'] = $selectedOption
            ? Program::with(['teachers', 'students', 'programClasses.materials', 'programClasses.activities', 'programClasses.outcomes', 'programClasses.marks'])->find($selectedOption->id)
            : null;

        $data['contentModules'] = $data['selectedProgram'] ? $data['selectedProgram']->contentModules() : collect();

        $allHomeworks = $data['contentModules']->flatMap(fn ($m) => $m->lessons)->flatMap(fn ($l) => $l->homeworks);
        $data['homeworkStats'] = $allHomeworks->isNotEmpty()
            ? $this->homeworkRepo->getSubmissionStatsForHomeworks($allHomeworks)
            : [];

        $data['title'] = 'Homeschooling — Course Details';

        return view('homeschooling.course-details', compact('data'));
    }

    public function progressReport(Request $request)
    {
        $this->guard();
        $category = $this->category();

        $data['courseOptions'] = $this->courseOptions($category);

        $selectedId     = (int) $request->query('program');
        $selectedOption = $data['courseOptions']->firstWhere('id', $selectedId) ?? $data['courseOptions']->first();

        $data['selectedProgram'] = $selectedOption
            ? Program::with(['students', 'programClasses.marks'])->find($selectedOption->id)
            : null;

        $data['progress'] = $data['selectedProgram'] ? $this->markRepo->progressForProgram($data['selectedProgram']) : null;

        $data['title'] = 'Homeschooling — Progress & Report';

        return view('homeschooling.progress-report', compact('data'));
    }
}
