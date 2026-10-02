<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\WatchLearnTabRepository;

class WatchLearnTabController extends Controller
{
    private $repo;

    public function __construct(WatchLearnTabRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['tabs']  = $this->repo->getAll();
        $data['title'] = 'Watch & Learn — Tabs';
        return view('website-setup.watch-learn-tab.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = 'Add a Tab';
        return view('website-setup.watch-learn-tab.create', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'status'     => 'required',
        ]);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('watch-learn-tab.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('watch-learn-tab.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Tab';
        return view('website-setup.watch-learn-tab.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'      => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'status'     => 'required',
        ]);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('watch-learn-tab.index')->with('success', $result['message']);
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
