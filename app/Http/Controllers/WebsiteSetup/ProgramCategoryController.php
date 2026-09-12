<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteSetup\Program\ProgramCategoryRequest;
use App\Repositories\WebsiteSetup\ProgramCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProgramCategoryController extends Controller
{
    private $repo;

    public function __construct(ProgramCategoryRepository $repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')) {
            abort(400);
        }
        $this->repo = $repo;
    }

    public function index()
    {
        $data['categories'] = $this->repo->getAll();
        $data['title']      = ___('settings.program_categories');
        return view('website-setup.program-category.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = ___('settings.add_program_category');
        return view('website-setup.program-category.create', compact('data'));
    }

    public function store(ProgramCategoryRequest $request)
    {
        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('program-category.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['category'] = $this->repo->show($id);
        if (!$data['category']) {
            return redirect()->route('program-category.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = ___('settings.edit_program_category');
        return view('website-setup.program-category.edit', compact('data'));
    }

    public function update(ProgramCategoryRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('program-category.index')->with('success', $result['message']);
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

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkDestroy($ids);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
