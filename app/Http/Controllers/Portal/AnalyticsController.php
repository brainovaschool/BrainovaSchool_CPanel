<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Portal\PortalSetting;
use App\Models\Staff\Staff;
use App\Repositories\Portal\AnalyticsRepository;

class AnalyticsController extends Controller
{
    private $repo;

    public function __construct(AnalyticsRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    private function range(Request $request): array
    {
        $from = $request->filled('from') ? $request->from : now()->startOfMonth()->format('Y-m-d');
        $to   = $request->filled('to') ? $request->to : now()->format('Y-m-d');
        return [$from, $to];
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
        $filters = $request->only(['assigned_to', 'category', 'priority']);

        $data['from']    = $from;
        $data['to']      = $to;
        $data['filters'] = $filters;
        $data['summary'] = $this->repo->orgSummary($from, $to, $filters);
        $data['rows']    = $this->repo->byEmployee($from, $to, $filters);
        $data['trend']   = $this->repo->trend($from, $to, $filters['assigned_to'] ?? null);
        $data['employeesList'] = Staff::orderBy('first_name')->get();
        $data['settings']      = PortalSetting::current();
        $data['title']   = 'Team Portal — Analytics';
        return view('portal.analytics.index', compact('data'));
    }

    public function myPerformance(Request $request)
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return redirect()->route('portal-employees.index')->with('danger', 'No staff record is linked to your account.');
        }

        [$from, $to] = $this->range($request);

        $data['from']    = $from;
        $data['to']      = $to;
        $data['metrics'] = $this->repo->myPerformance($staffId, $from, $to);
        $data['history'] = $this->repo->myTaskHistory($staffId);
        $data['title']   = 'My Performance';
        return view('portal.analytics.my', compact('data'));
    }
}
