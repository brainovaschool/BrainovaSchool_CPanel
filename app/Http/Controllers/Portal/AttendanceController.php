<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Portal\AttendanceRepository;

class AttendanceController extends Controller
{
    private $repo;

    public function __construct(AttendanceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $date = $request->filled('date') ? $request->date : now()->format('Y-m-d');

        $data['date']   = $date;
        $data['rows']   = $this->repo->forDate($date);
        $data['title']  = 'Team Portal — Attendance';
        return view('portal.attendance.index', compact('data'));
    }
}
