<?php

namespace App\Http\Controllers\BeyondAcademics;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\PageSections;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramCategory;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && (int) $user->role_id === 1;
        $isTeacher = $user && !$isAdmin && isTeachingStaff();

        if (!$isAdmin && !$isTeacher) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }

        // null = no restriction (admin sees every active program). A
        // teacher only sees the programs they're actually assigned to
        // teach, set from the Program edit form's "Teachers" field -
        // students aren't on this page yet, that's a separate follow-up.
        $programIds = $isAdmin ? null : $user->staff->programs()->pluck('programs.id')->all();

        $data['hero'] = PageSections::where('key', 'beyond_academics_overview_hero')->first();

        /** Pulled straight from the same Programs the public Course
         *  Catalogue already shows - nothing new to maintain here, this
         *  page just reflects whatever's set up in Website Setup. */
        $data['featured'] = Program::active()
            ->when($programIds !== null, fn ($q) => $q->whereIn('id', $programIds))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        $data['categories'] = ProgramCategory::where('status', 1)
            ->orderBy('sort_order')
            ->with(['upload', 'activePrograms' => function ($q) use ($programIds) {
                $q->with('upload');
                if ($programIds !== null) {
                    $q->whereIn('id', $programIds);
                }
            }])
            ->get();

        $data['title'] = 'Beyond Academics — Overview';

        return view('beyond-academics.overview', compact('data'));
    }
}
