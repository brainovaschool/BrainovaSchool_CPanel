<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\HomeVideo;
use App\Repositories\WebsiteSetup\HomeVideoRepository;
use Illuminate\Validation\ValidationException;

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
        $this->validateRequest($request, $this->repo->show($id));

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

    /** video_url and video_file are both optional on their own — what's
     *  required is at least one of: a link, a freshly uploaded file, or
     *  (on update) a file/link the row already had. 50MB cap on uploads
     *  is a reasonable default, not a verified server limit — a host's
     *  own php.ini upload_max_filesize/post_max_size can still reject a
     *  file before this validation even runs. */
    private function validateRequest(Request $request, ?HomeVideo $existing = null): void
    {
        $request->validate([
            'title'       => 'nullable|string|max:150',
            'video_url'   => 'nullable|string|max:500|url',
            'video_file'  => 'nullable|file|mimes:mp4,mov,webm,ogg,avi,m4v|max:51200',
            'orientation' => 'required|in:' . implode(',', array_keys(HomeVideo::ORIENTATIONS)),
            'autoplay'    => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
            'status'      => 'required',
        ]);

        $hasLink        = $request->filled('video_url');
        $hasFile        = $request->hasFile('video_file');
        $hasExisting    = $existing && ($existing->upload_id || $existing->video_url);

        if (!$hasLink && !$hasFile && !$hasExisting) {
            throw ValidationException::withMessages([
                'video_url' => 'Paste a video link, or upload a video file below.',
            ]);
        }
    }
}
