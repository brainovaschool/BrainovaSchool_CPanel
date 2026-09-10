<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteSetup\Program\ProgramFocusRequest;
use App\Repositories\WebsiteSetup\ProgramCategoryRepository;
use App\Repositories\WebsiteSetup\ProgramFocusRepository;
use Illuminate\Support\Facades\Schema;

class ProgramFocusController extends Controller
{
    private $repo;
    private $categoryRepo;

    public function __construct(ProgramFocusRepository $repo, ProgramCategoryRepository $categoryRepo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')) {
            abort(400);
        }
        $this->repo         = $repo;
        $this->categoryRepo = $categoryRepo;
    }

    public function index()
    {
        $data['focuses'] = $this->repo->getAll();
        $data['title']   = ___('settings.program_focus_areas');
        return view('website-setup.program-focus.index', compact('data'));
    }

    public function create()
    {
        $data['categories'] = $this->categoryRepo->all();
        $data['title']      = ___('settings.add_program_focus_area');
        return view('website-setup.program-focus.create', compact('data'));
    }

    public function store(ProgramFocusRequest $request)
    {
        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('program-focus.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['focus'] = $this->repo->show($id);
        if (!$data['focus']) {
            return redirect()->route('program-focus.index')->with('danger', ___('alert.not_found'));
        }
        $data['categories'] = $this->categoryRepo->all();
        $data['title']      = ___('settings.edit_program_focus_area');
        return view('website-setup.program-focus.edit', compact('data'));
    }

    public function update(ProgramFocusRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('program-focus.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
