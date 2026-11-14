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
        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }

        $data['hero'] = PageSections::where('key', 'beyond_academics_overview_hero')->first();

        /** Pulled straight from the same Programs the public Course
         *  Catalogue already shows - nothing new to maintain here, this
         *  page just reflects whatever's set up in Website Setup. */
        $data['featured'] = Program::active()
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        $data['categories'] = ProgramCategory::where('status', 1)
            ->orderBy('sort_order')
            ->with(['upload', 'activePrograms.upload'])
            ->get();

        $data['title'] = 'Beyond Academics — Overview';

        return view('beyond-academics.overview', compact('data'));
    }
}
