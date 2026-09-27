<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LearningEngine\CharacterLine;
use App\Repositories\WebsiteSetup\CharacterLineRepository;

class CharacterLineController extends Controller
{
    private $repo;

    public function __construct(CharacterLineRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['lines'] = $this->repo->getAll();
        $data['title'] = 'Character Lines';
        return view('website-setup.character-line.index', compact('data'));
    }

    public function create()
    {
        $data['contexts'] = $this->knownContexts();
        $data['title']    = 'Add a Line';
        return view('website-setup.character-line.create', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'character'  => 'required|in:' . implode(',', array_keys(CharacterLine::CHARACTERS)),
            'context'    => 'required|string|max:60',
            'line'       => 'required|string|max:500',
            'sort_order' => 'nullable|integer',
            'status'     => 'required',
        ]);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('character-line.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('character-line.index')->with('danger', ___('alert.not_found'));
        }
        $data['contexts'] = $this->knownContexts();
        $data['title']    = 'Edit Line';
        return view('website-setup.character-line.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'character'  => 'required|in:' . implode(',', array_keys(CharacterLine::CHARACTERS)),
            'context'    => 'required|string|max:60',
            'line'       => 'required|string|max:500',
            'sort_order' => 'nullable|integer',
            'status'     => 'required',
        ]);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('character-line.index')->with('success', $result['message']);
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

    /** Every context already in use, so the admin can reuse an existing one
     *  (welcome, encouragement, ...) from a dropdown instead of retyping it
     *  and risking a typo that silently creates a new, orphaned context. */
    private function knownContexts(): array
    {
        return CharacterLine::query()->distinct()->orderBy('context')->pluck('context')->all();
    }
}
