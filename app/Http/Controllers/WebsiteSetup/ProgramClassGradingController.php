<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\Program;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** The "Grading" overview - every lesson across a teacher's (or, for
 *  admin, every) Program that has a quiz/project/worksheet/assignment to
 *  grade, filterable by Program, with the same stat-cards + quest-log
 *  table pattern the Homework page uses. The entry point for grading
 *  instead of having to remember which lesson on which course's Course
 *  Details page has a pending submission. */
class ProgramClassGradingController extends Controller
{
    private ProgramClassMarkRepository $repo;

    public function __construct(ProgramClassMarkRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $user    = Auth::user();
        $isAdmin = (int) ($user->role_id ?? 0) === 1;

        $programsQuery = Program::active()->orderBy('sort_order');
        if (!$isAdmin) {
            $staffId = $user->staff->id ?? null;
            $programsQuery->when($staffId, fn ($q) => $q->whereHas('teachers', fn ($t) => $t->where('staff.id', $staffId)), fn ($q) => $q->whereRaw('1 = 0'));
        }
        $programs = $programsQuery->get(['id', 'title']);

        $selectedProgramId = (int) $request->query('program') ?: null;
        if ($selectedProgramId && !$programs->firstWhere('id', $selectedProgramId)) {
            $selectedProgramId = null;
        }

        $overview = $this->repo->gradingOverview($programs->pluck('id')->all(), $selectedProgramId);

        $data['programs']           = $programs;
        $data['selectedProgramId']  = $selectedProgramId;
        $data['rows']               = $overview['rows'];
        $data['stats']              = $overview['stats'];
        $data['title']              = 'Grading';

        return view('program-class.grading', compact('data'));
    }
}
