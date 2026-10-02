<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\KnowledgeHubPageRepository;

class KnowledgeHubPageController extends Controller
{
    private $repo;

    public function __construct(KnowledgeHubPageRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['pages'] = $this->repo->getAll();
        $data['title'] = 'Knowledge Hub — Pages';
        return view('website-setup.knowledge-hub-page.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = 'Add a Page';
        return view('website-setup.knowledge-hub-page.create', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:150',
            'sort_order' => 'nullable|integer',
            'status'     => 'required',
        ]);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('knowledge-hub-page.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('knowledge-hub-page.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Page';
        return view('website-setup.knowledge-hub-page.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'      => 'required|string|max:150',
            'sort_order' => 'nullable|integer',
            'status'     => 'required',
        ]);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('knowledge-hub-page.index')->with('success', $result['message']);
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
