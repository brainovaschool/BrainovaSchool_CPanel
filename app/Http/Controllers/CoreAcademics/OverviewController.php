<?php

namespace App\Http\Controllers\CoreAcademics;

use App\Http\Controllers\Controller;
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

        /** Same category record the public Our Programs page reads for
         *  its own banner - one place (Website Setup > Course Catalogue >
         *  Program Category > Edit) to set tagline/title/subtitle/image
         *  for this category everywhere it's shown. */
        $category = ProgramCategory::where('name', 'Core Academics')->with('upload')->first();
        $data['category'] = $category;

        /** Scoped to the "Core Academics" category - the academic subjects
         *  themselves, not the Beyond Academics skills catalogue. */
        $data['featured'] = Program::active()
            ->when($category, fn ($q) => $q->where('program_category_id', $category->id))
            ->orderBy('sort_order')
            ->with('upload')
            ->take(8)
            ->get();

        $data['title'] = 'Core Academics — Overview';

        return view('core-academics.overview', compact('data'));
    }
}
