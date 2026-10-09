<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Portal\EmployeeRepository;

class EmployeeController extends Controller
{
    private $repo;

    public function __construct(EmployeeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $data['employees']    = $this->repo->index($request->integer('role_id') ?: null, $request->filled('search') ? $request->string('search')->toString() : null);
        $data['roles']        = $this->repo->roleOptions();
        $data['selectedRole'] = $request->integer('role_id') ?: null;
        $data['search']       = $request->string('search')->toString();
        $data['title']        = 'Team Portal — Employees';
        return view('portal.employees.index', compact('data'));
    }

    public function show($id)
    {
        $data['employee'] = $this->repo->show((int) $id);
        if (!$data['employee']) {
            return redirect()->route('portal-employees.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Employee Profile';
        return view('portal.employees.show', compact('data'));
    }
}
