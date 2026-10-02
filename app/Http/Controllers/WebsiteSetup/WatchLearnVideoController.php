<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\WatchLearnVideo;
use App\Repositories\WebsiteSetup\WatchLearnVideoRepository;
use App\Repositories\WebsiteSetup\WatchLearnTabRepository;
use App\Repositories\WebsiteSetup\WatchLearnTemplateRepository;

class WatchLearnVideoController extends Controller
{
    private $repo;
    private $tabRepo;
    private $templateRepo;

    public function __construct(WatchLearnVideoRepository $repo, WatchLearnTabRepository $tabRepo, WatchLearnTemplateRepository $templateRepo)
    {
        $this->repo         = $repo;
        $this->tabRepo      = $tabRepo;
        $this->templateRepo = $templateRepo;
    }

    public function index(Request $request)
    {
        $orientation = in_array($request->get('orientation'), ['landscape', 'portrait'], true) ? $request->get('orientation') : null;
        $data['videos']      = $this->repo->getAll($orientation);
        $data['orientation'] = $orientation;
        $data['title']       = 'Watch & Learn — Videos';
        return view('website-setup.watch-learn-video.index', compact('data'));
    }

    public function create(Request $request)
    {
        $orientation = $request->get('orientation') === 'portrait' ? 'portrait' : 'landscape';

        $data['orientation'] = $orientation;
        $data['tabs']        = $this->tabRepo->activeOrdered();
        $data['templates']   = $this->templateRepo->activeByOrientation($orientation);
        $data['title']       = 'Add a ' . ucfirst($orientation) . ' Video';
        return view('website-setup.watch-learn-video.create', compact('data'));
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('watch-learn-video.index', ['orientation' => $request->orientation])->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('watch-learn-video.index')->with('danger', ___('alert.not_found'));
        }
        $data['orientation'] = $data['item']->orientation;
        $data['tabs']        = $this->tabRepo->activeOrdered();
        $data['templates']   = $this->templateRepo->activeByOrientation($data['item']->orientation);
        $data['title']       = 'Edit Video';
        return view('website-setup.watch-learn-video.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $this->validateRequest($request);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('watch-learn-video.index')->with('success', $result['message']);
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
            'title'        => 'nullable|string|max:150',
            'video_url'    => 'required|string|max:500|url',
            'orientation'  => 'required|in:' . implode(',', array_keys(WatchLearnVideo::ORIENTATIONS)),
            'tab_id'       => 'nullable|integer|exists:watch_learn_tabs,id',
            'template_id'  => 'nullable|integer|exists:watch_learn_templates,id',
            'sort_order'   => 'nullable|integer',
            'status'       => 'required',
        ]);
    }
}
