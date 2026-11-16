<?php

namespace App\Http\Controllers\CoreAcademics;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Repositories\Homework\HomeworkRepository;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     *  under Academics Boost (see teachesInCategory()), or a Coordinator/
     *  "Head" approver reviewing that teacher's submissions, so this gate
     *  has to allow all three, or they hit the tab and then a 403 the
     *  moment they click into it. Folded into scopedProgramIds() itself
     *  (same shape as BeyondAcademics\OverviewController) rather than a
     *  separate guard(), so every caller gets the check for free. */
    private function scopedProgramIds(): ?array
    {
        $user = Auth::user();
        $isAdmin       = $this->isAdmin();
        $isTeacher     = !$isAdmin && teachesInCategory('Academics Boost');
        $isCoordinator = !$isAdmin && hasPermission('class_content_coordinator_review');
        // A "Head" standing in for Admin on final approval - same
        // permission that already gates /class-content-admin - should
        // see every program to approve, same as Admin, not just their
        // own coordinatees' scope.
        $isApprover    = !$isAdmin && hasPermission('class_content_approve');

        if (!$isAdmin && !$isTeacher && !$isCoordinator && !$isApprover) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }

        if ($isAdmin || $isApprover) {
            return null;
        }

        /** Otherwise the union of: programs this teacher personally
         *  teaches (Program edit form's "Teachers" field), and - if
         *  they're also a Coordinator - programs taught by whichever
         *  teachers they oversee (Staff::coordinatees()). Same scoping
         *  Beyond Academics already applies, so an assigned
         *  teacher/coordinator here only ever sees their own program(s)
         *  or their coordinatees', not every other teacher's students. */
        $idSet = [];
        if ($isTeacher) {
            $staff = $user->staff;
            $idSet = array_merge($idSet, $staff ? $staff->programs()->pluck('programs.id')->all() : []);
        }
        if ($isCoordinator) {
            $coordinateeIds = $user->staff?->coordinatees->pluck('id')->all() ?? [];
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
