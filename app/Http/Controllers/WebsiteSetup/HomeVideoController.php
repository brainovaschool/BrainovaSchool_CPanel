<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\HomeVideo;
use App\Repositories\WebsiteSetup\HomeVideoRepository;

class HomeVideoController extends Controller
{
    private $repo;

    public function __construct(HomeVideoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['videos'] = $this->repo->getAll();
        $data['title']  = 'Home Videos';
        return view('website-setup.home-video.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = 'Add a Video';
        return view('website-setup.home-video.create', compact('data'));
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('home-video.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('home-video.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Video';
        return view('website-setup.home-video.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $this->validateRequest($request);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('home-video.index')->with('success', $result['message']);
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

    private function validateRequest(Request $request): void
    {
        $request->validate([
            'title'       => 'nullable|string|max:150',
            'video_url'   => 'required|string|max:500|url',
            'orientation' => 'required|in:' . implode(',', array_keys(HomeVideo::ORIENTATIONS)),
            'autoplay'    => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
            'status'      => 'required',
        ]);
    }
}
