<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteSetup\Program\ProgramRequest;
use App\Models\WebsiteSetup\ProgramFocus;
use App\Repositories\WebsiteSetup\ProgramCategoryRepository;
use App\Repositories\WebsiteSetup\ProgramRepository;
use Illuminate\Support\Facades\Schema;

class ProgramController extends Controller
{
    private $repo;
    private $categoryRepo;

    public function __construct(ProgramRepository $repo, ProgramCategoryRepository $categoryRepo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')) {
            abort(400);
        }
        $this->repo         = $repo;
        $this->categoryRepo = $categoryRepo;
    }

    public function index()
    {
        $data['programs'] = $this->repo->getAll();
        $data['title']    = ___('settings.programs');
        return view('website-setup.program.index', compact('data'));
    }

    public function create()
    {
        $data['categories'] = $this->categoryRepo->all();
        $data['focuses']    = $this->focusOptions();
        $data['title']      = ___('settings.add_program');
        return view('website-setup.program.create', compact('data'));
    }

    public function store(ProgramRequest $request)
    {
        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('program.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['program'] = $this->repo->show($id);
        if (!$data['program']) {
            return redirect()->route('program.index')->with('danger', ___('alert.not_found'));
        }
        $data['categories'] = $this->categoryRepo->all();
        $data['focuses']    = $this->focusOptions();
        $data['title']      = ___('settings.edit_program');
        return view('website-setup.program.edit', compact('data'));
    }

    public function update(ProgramRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('program.index')->with('success', $result['message']);
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

    /** Focus areas grouped by category id, for the dependent dropdown. */
    private function focusOptions()
    {
        return ProgramFocus::with('category')
            ->orderBy('program_category_id')->orderBy('sort_order')->orderBy('name')
            ->get();
    }
}
