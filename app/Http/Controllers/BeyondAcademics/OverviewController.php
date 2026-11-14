<?php

namespace App\Http\Controllers\BeyondAcademics;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\PageSections;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    /** The skill categories themselves - just the buttons for now, each
     *  one's real course content comes later. Kept here rather than in
     *  Website Setup since this is the curriculum taxonomy, not marketing
     *  copy; "Other Skills & Workshops" is deliberately left out. */
    public const CATEGORIES = [
        ['label' => 'Graphic Design & Canva', 'icon' => 'la-palette', 'color' => '#fde8d7'],
        ['label' => 'Coding & Programming', 'icon' => 'la-code', 'color' => '#dcecfb'],
        ['label' => 'Robotics', 'icon' => 'la-robot', 'color' => '#d9f5ef'],
        ['label' => 'Artificial Intelligence', 'icon' => 'la-microchip', 'color' => '#dde6fc'],
        ['label' => 'Art & Creativity', 'icon' => 'la-paint-brush', 'color' => '#fde8d7'],
        ['label' => 'Science & Experiments', 'icon' => 'la-flask', 'color' => '#d9f5ef'],
        ['label' => 'Entrepreneurship & Business', 'icon' => 'la-handshake', 'color' => '#e7defc'],
        ['label' => 'Communication & Public Speaking', 'icon' => 'la-bullhorn', 'color' => '#e7defc'],
    ];

    public function index()
    {
        if (!Auth::check() || (int) Auth::user()->role_id !== 1) {
            abort(403, ___('alert.You are not authorized to access this page'));
        }

        $data['hero'] = PageSections::where('key', 'beyond_academics_overview_hero')->first();
        $data['categories'] = self::CATEGORIES;
        $data['title'] = 'Beyond Academics — Overview';

        return view('beyond-academics.overview', compact('data'));
    }
}
