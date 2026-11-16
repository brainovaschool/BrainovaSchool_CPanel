<?php

namespace App\Http\Controllers\CoreAcademics;

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

    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && (int) $user->role_id === 1;
    }

    /** Was Super Admin only - the sidebar now also opens this section for
     *  a teacher actually assigned (Program::teachers()) to a program
     *  under Academics Boost (see teachesInCategory()), so this gate has
     *  to allow the same thing, or that teacher hits the tab and then a
     *  403 the moment they click into it. */
    private function guard(): void
    {
        if (!Auth::check() || (!$this->isAdmin() && !teachesInCategory('Academics Boost'))) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }
    }

    /** null = no restriction (admin sees every active program in the
     *  category). Otherwise just the ids of programs this teacher
     *  personally teaches (Program edit form's "Teachers" field) - same
     *  scoping Beyond Academics already applies for its own teachers, so
     *  an assigned teacher here only ever sees their own program(s), not
     *  every other teacher's students/progress too. */
    private function scopedProgramIds(): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }
        $staff = Auth::user()->staff;
        return $staff ? $staff->programs()->pluck('programs.id')->all() : [];
    }

    /** Same category record the public Our Programs page reads for its
     *  own banner. Renamed from "Core Academics" to "Academics Boost" -
     *  the route/controller/view keep the old internal name, only the
     *  category name changed. */
    private function category(): ?ProgramCategory
    {
        return ProgramCategory::where('name', 'Academics Boost')->with('upload')->first();
    }

    private function courseOptions(?ProgramCategory $category)
    {
        $scopedIds = $this->scopedProgramIds();
        return Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->when($scopedIds !== null, fn ($q) => $q->whereIn('id', $scopedIds))
            ->orderBy('sort_order')
            ->get(['id', 'title']);
    }

    public function overview()
    {
        $this->guard();
        $category = $this->category();
        $data['category'] = $category;

        /** Scoped to the "Academics Boost" category - the academic subjects
         *  themselves, not the Beyond Academics skills catalogue - and, for
         *  a teacher, to just their own program(s) within it. */
        $scopedIds = $this->scopedProgramIds();
        $data['featured'] = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->when($scopedIds !== null, fn ($q) => $q->whereIn('id', $scopedIds))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        $data['title'] = 'Academics Boost — Overview';

        return view('core-academics.overview', compact('data'));
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

        $data['title'] = 'Academics Boost — Course Details';

        return view('core-academics.course-details', compact('data'));
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

        $data['title'] = 'Academics Boost — Progress & Report';

        return view('core-academics.progress-report', compact('data'));
    }
}
