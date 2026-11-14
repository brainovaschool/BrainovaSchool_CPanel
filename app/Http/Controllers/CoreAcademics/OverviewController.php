<?php

namespace App\Http\Controllers\CoreAcademics;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\PageSections;
use App\Models\WebsiteSetup\Program;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    public function index()
    {
        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }

        $data['hero'] = PageSections::where('key', 'core_academics_overview_hero')->first();

        /** Scoped to the "Core Academics" category - the academic subjects
         *  themselves, not the Beyond Academics skills catalogue. */
        $data['featured'] = Program::active()
            ->whereHas('category', fn ($q) => $q->where('name', 'Core Academics'))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        $data['title'] = 'Core Academics — Overview';

        return view('core-academics.overview', compact('data'));
    }
}
