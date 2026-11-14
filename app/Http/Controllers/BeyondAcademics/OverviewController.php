<?php

namespace App\Http\Controllers\BeyondAcademics;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Repositories\Homework\HomeworkRepository;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    private $homeworkRepo;
    private $markRepo;

    public function __construct(HomeworkRepository $homeworkRepo, ProgramClassMarkRepository $markRepo)
    {
        $this->homeworkRepo = $homeworkRepo;
        $this->markRepo     = $markRepo;
    }

    public function index()
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

        // null = no restriction (admin sees every active program).
        // Otherwise the union of: programs they personally teach (Program
        // edit form's "Teachers" field) and - if they're also a
        // Coordinator - programs taught by whichever teachers they
        // oversee (Staff::coordinatees()). Students aren't on this page
        // yet, that's a separate follow-up.
        $programIds = null;
        if (!$isAdmin) {
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
                    $idSet = array_merge($idSet, \Illuminate\Support\Facades\DB::table('staff_program_enrollments')
                        ->whereIn('staff_id', $coordinateeIds)
                        ->pluck('program_id')
                        ->all());
                }
            }
            $programIds = array_values(array_unique($idSet));
        }

        /** Same category record the public Our Programs page reads for
         *  its own banner - one place (Website Setup > Course Catalogue >
         *  Program Category > Edit) to set tagline/title/subtitle/image
         *  for this category everywhere it's shown. */
        $category = ProgramCategory::where('name', 'Beyond Acedamics')->with('upload')->first();
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

        // Course Details: same category + role scope as Featured Courses
        // above, but every matching program (not capped to 8) and with the
        // full model + relations the lessons/homework block needs.
        $data['courseOptions'] = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->when($programIds !== null, fn ($q) => $q->whereIn('id', $programIds))
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        $selectedId = (int) request()->query('program');
        $selectedOption = $data['courseOptions']->firstWhere('id', $selectedId) ?? $data['courseOptions']->first();

        $data['selectedProgram'] = $selectedOption
            ? Program::with(['teachers', 'students', 'programClasses.materials', 'programClasses.activities', 'programClasses.outcomes', 'programClasses.creator', 'programClasses.marks'])->find($selectedOption->id)
            : null;

        $data['contentModules'] = $data['selectedProgram'] ? $data['selectedProgram']->contentModules() : collect();

        $allHomeworks = $data['contentModules']->flatMap(fn ($m) => $m->lessons)->flatMap(fn ($l) => $l->homeworks);
        $data['homeworkStats'] = $allHomeworks->isNotEmpty()
            ? $this->homeworkRepo->getSubmissionStatsForHomeworks($allHomeworks)
            : [];

        // Progress & Report is deliberately auto-linked to whichever
        // course is selected above, not a separate dropdown.
        $data['progress'] = $data['selectedProgram'] ? $this->markRepo->progressForProgram($data['selectedProgram']) : null;

        $data['title'] = 'Beyond Academics — Overview';

        return view('beyond-academics.overview', compact('data'));
    }
}
