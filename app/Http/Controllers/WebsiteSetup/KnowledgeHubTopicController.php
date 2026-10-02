<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\KnowledgeHubTopicRepository;
use App\Repositories\WebsiteSetup\KnowledgeHubPageRepository;

class KnowledgeHubTopicController extends Controller
{
    private $repo;
    private $pageRepo;

    public function __construct(KnowledgeHubTopicRepository $repo, KnowledgeHubPageRepository $pageRepo)
    {
        $this->repo     = $repo;
        $this->pageRepo = $pageRepo;
    }

    public function index($page)
    {
        $data['page']   = $this->pageRepo->show($page);
        if (!$data['page']) {
            return redirect()->route('knowledge-hub-page.index')->with('danger', ___('alert.not_found'));
        }
        $data['topics'] = $this->repo->getAll((int) $page);
        $data['title']  = 'Topics — ' . $data['page']->title;
        return view('website-setup.knowledge-hub-topic.index', compact('data'));
    }

    public function create($page)
    {
        $data['page'] = $this->pageRepo->show($page);
        if (!$data['page']) {
            return redirect()->route('knowledge-hub-page.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Add a Topic';
        return view('website-setup.knowledge-hub-topic.create', compact('data'));
    }

    public function store(Request $request, $page)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'explanation' => 'required|string',
            'sort_order'  => 'nullable|integer',
            'status'      => 'required',
        ]);

        $result = $this->repo->store($request, (int) $page);
        if ($result['status']) {
            return redirect()->route('knowledge-hub-topic.index', $page)->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($page, $id)
    {
        $data['page'] = $this->pageRepo->show($page);
        $data['item'] = $this->repo->show($id);
        if (!$data['page'] || !$data['item']) {
            return redirect()->route('knowledge-hub-page.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Topic';
        return view('website-setup.knowledge-hub-topic.edit', compact('data'));
    }

    public function update(Request $request, $page, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'explanation' => 'required|string',
            'sort_order'  => 'nullable|integer',
            'status'      => 'required',
        ]);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('knowledge-hub-topic.index', $page)->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($page, $id)
    {
        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkDelete(Request $request, $page)
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

    public function bulkStatus(Request $request, $page)
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
