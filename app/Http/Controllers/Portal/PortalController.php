<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;

/** The /portal shell root has no page of its own - it just lands
 *  somewhere useful. The Employees directory it used to land on is gone
 *  (that listing duplicated Staff Manage), so this goes to the main
 *  dashboard instead. */
class PortalController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }
}
