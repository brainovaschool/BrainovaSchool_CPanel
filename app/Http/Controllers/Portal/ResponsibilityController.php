<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Staff\Staff;
use App\Repositories\Portal\ResponsibilityRepository;

class ResponsibilityController extends Controller
{
    private $repo;

    public function __construct(ResponsibilityRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    public function index()
    {
        $data['duties']  = $this->repo->all();
        $data['staffList'] = Staff::orderBy('first_name')->get();
        $data['title']   = 'Team Portal — Responsibilities';
        return view('portal.responsibilities.index', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'freq'  => 'required|in:daily,weekly',
        ]);

        $result = $this->repo->store($request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'freq'  => 'required|in:daily,weekly',
        ]);

        $result = $this->repo->update((int) $id, $request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function destroy($id)
    {
        $result = $this->repo->destroy((int) $id);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function myDuties()
    {
        $staffId = $this->actingStaffId();
        if ($staffId === null) {
            return redirect()->route('portal-employees.index')->with('danger', 'No staff record is linked to your account.');
        }

        $data['duties'] = $this->repo->forStaff($staffId);
        $data['title']  = 'My Responsibilities';
        return view('portal.responsibilities.my', compact('data'));
    }

    public function tick($id)
    {
        $isManager = hasPermission('portal_manage');
        $staffId   = $this->actingStaffId();

        if (!$isManager && $staffId === null) {
            return back()->with('danger', "This isn't your responsibility.");
        }

        $result = $this->repo->tick((int) $id, $isManager ? null : $staffId, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
