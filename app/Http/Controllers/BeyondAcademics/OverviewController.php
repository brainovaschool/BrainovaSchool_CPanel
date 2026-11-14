<?php

namespace App\Http\Controllers\BeyondAcademics;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Repositories\Homework\HomeworkRepository;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Split into 3 pages (Overview / Course Details / Progress & Report)
 *  rather than one page with 3 anchor sections - same split the student
 *  side uses, reached from 3 separate sidebar links now instead of 3
 *  anchors into the same page. */
class OverviewController extends Controller
{
    private $homeworkRepo;
    private $markRepo;

    public function __construct(HomeworkRepository $homeworkRepo, ProgramClassMarkRepository $markRepo)
    {
        $this->homeworkRepo = $homeworkRepo;
        $this->markRepo     = $markRepo;
    }

    /** null = no restriction (admin sees every active program). Otherwise
     *  the union of: programs they personally teach (Program edit form's
     *  "Teachers" field) and - if they're also a Coordinator - programs
     *  taught by whichever teachers they oversee (Staff::coordinatees()).
     *  Students aren't on this page yet, that's a separate follow-up. */
    private function scopedProgramIds(): ?array
    {
        $user = Auth::user();
        $isAdmin = $user && (int) $user->role_id === 1;
        $isTeacher = $user && !$isAdmin && isTeachingStaff();
        // Not "!$isTeacher" - a staff member can carry both signals at once
        // (e.g. a Coordinator role that also has class_content_create for
        // unrelated reasons), and they still need their full Coordinator
        // scope, not just their own taught programs.
        $isCoordinator = $user && !$isAdmin && hasPermission('class_content_coordinator_review');

        if (!$isAdmin && !$isTeacher && !$isCoordinator) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }

        if ($isAdmin) {
            return null;
        }

        $idSet = [];
        if ($isTeacher) {
            $idSet = array_merge($idSet, $user->staff->programs()->pluck('programs.id')->all());
        }
        if ($isCoordinator) {
            // Direct pivot-table query rather than whereHas(), to rule
            // out any table-aliasing surprise in the belongsToMany
            // subquery - this is the exact same staff_program_enrollments
            // table Program::teachers() itself reads from.
            $coordinateeIds = $user->staff->coordinatees->pluck('id')->all();
            if ($coordinateeIds) {
                $idSet = array_merge($idSet, DB::table('staff_program_enrollments')
                    ->whereIn('staff_id', $coordinateeIds)
                    ->pluck('program_id')
                    ->all());
            }
        }

        return array_values(array_unique($idSet));
    }

    /** Same category record the public Our Programs page reads for its
     *  own banner - one place (Website Setup > Course Catalogue > Program
     *  Category > Edit) to set tagline/title/subtitle/image for this
     *  category everywhere it's shown. */
    private function category(): ?ProgramCategory
    {
        return ProgramCategory::where('name', 'Beyond Acedamics')->with('upload')->first();
    }

    private function courseOptions(?ProgramCategory $category, ?array $programIds)
    {
        return Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->when($programIds !== null, fn ($q) => $q->whereIn('id', $programIds))
            ->orderBy('sort_order')
            ->get(['id', 'title']);
    }

    public function overview()
    {
        $programIds = $this->scopedProgramIds();
        $category   = $this->category();
        $data['category'] = $category;

        /** Pulled straight from the same Programs the public Course
         *  Catalogue already shows - nothing new to maintain here, this
         *  page just reflects whatever's set up in Website Setup. Scoped
         *  to the "Beyond Acedamics" category specifically - coding,
         *  Canva, AI, robotics etc - not every program in the catalogue. */
        $data['featured'] = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->when($programIds !== null, fn ($q) => $q->whereIn('id', $programIds))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        $data['title'] = 'Beyond Academics — Overview';

        return view('beyond-academics.overview', compact('data'));
    }

    public function courseDetails(Request $request)
    {
        $programIds = $this->scopedProgramIds();
        $category   = $this->category();

        $data['courseOptions'] = $this->courseOptions($category, $programIds);

        $selectedId     = (int) $request->query('program');
        $selectedOption = $data['courseOptions']->firstWhere('id', $selectedId) ?? $data['courseOptions']->first();

        $data['selectedProgram'] = $selectedOption
            ? Program::with(['teachers', 'students', 'programClasses.materials', 'programClasses.activities', 'programClasses.outcomes', 'programClasses.creator', 'programClasses.marks'])->find($selectedOption->id)
            : null;

        $data['contentModules'] = $data['selectedProgram'] ? $data['selectedProgram']->contentModules() : collect();

        $allHomeworks = $data['contentModules']->flatMap(fn ($m) => $m->lessons)->flatMap(fn ($l) => $l->homeworks);
        $data['homeworkStats'] = $allHomeworks->isNotEmpty()
            ? $this->homeworkRepo->getSubmissionStatsForHomeworks($allHomeworks)
            : [];

        $data['title'] = 'Beyond Academics — Course Details';

        return view('beyond-academics.course-details', compact('data'));
    }

    public function progressReport(Request $request)
    {
        $programIds = $this->scopedProgramIds();
        $category   = $this->category();

        $data['courseOptions'] = $this->courseOptions($category, $programIds);

        $selectedId     = (int) $request->query('program');
        $selectedOption = $data['courseOptions']->firstWhere('id', $selectedId) ?? $data['courseOptions']->first();

        $data['selectedProgram'] = $selectedOption
            ? Program::with(['students', 'programClasses.marks'])->find($selectedOption->id)
            : null;

        $data['progress'] = $data['selectedProgram'] ? $this->markRepo->progressForProgram($data['selectedProgram']) : null;

        $data['title'] = 'Beyond Academics — Progress & Report';

        return view('beyond-academics.progress-report', compact('data'));
    }
}
