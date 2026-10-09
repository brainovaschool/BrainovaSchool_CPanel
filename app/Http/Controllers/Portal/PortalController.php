<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;

/** The /portal shell root. Phase 1 only ships the Employees directory, so
 *  this just lands there — becomes a real overview (tasks, attendance,
 *  notifications) as the later phases add those. */
class PortalController extends Controller
{
    public function index()
    {
        return redirect()->route('portal-employees.index');
    }
}
