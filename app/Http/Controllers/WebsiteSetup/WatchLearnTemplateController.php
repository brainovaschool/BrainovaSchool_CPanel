<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\WatchLearnTemplate;
use App\Repositories\WebsiteSetup\WatchLearnTemplateRepository;

class WatchLearnTemplateController extends Controller
{
    private $repo;

    public function __construct(WatchLearnTemplateRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['templates'] = $this->repo->getAll();
        $data['title']     = 'Watch & Learn — Tile Templates';
        return view('website-setup.watch-learn-template.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = 'Add a Tile Template';
        return view('website-setup.watch-learn-template.create', compact('data'));
    }

    public function store(Request $request)
    {
        $this->validateRequest($request, null);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('watch-learn-template.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('watch-learn-template.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Tile Template';
        return view('website-setup.watch-learn-template.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $this->validateRequest($request, $this->repo->show($id));

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('watch-learn-template.index')->with('success', $result['message']);
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

    private function validateRequest(Request $request, ?WatchLearnTemplate $existing): void
    {
        $request->validate([
            'name'        => 'nullable|string|max:100',
            'orientation' => 'required|in:' . implode(',', array_keys(WatchLearnTemplate::ORIENTATIONS)),
            'image'       => ($existing && $existing->upload_id ? 'nullable' : 'required') . '|file|image|max:5120',
            'sort_order'  => 'nullable|integer',
            'status'      => 'required',
        ]);
    }
}
