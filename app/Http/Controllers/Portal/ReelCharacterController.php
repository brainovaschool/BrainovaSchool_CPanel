<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Portal\ReelCharacterRepository;

class ReelCharacterController extends Controller
{
    private $repo;

    public function __construct(ReelCharacterRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['characters'] = $this->repo->all();
        $data['title']      = 'Reel Characters';
        return view('portal.social.reel-characters.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = 'Add a Reel Character';
        return view('portal.social.reel-characters.create', compact('data'));
    }

    private function rules(): array
    {
        return [
            'name'               => 'required|string|max:100',
            'portrait'           => 'nullable|image|dimensions:ratio=9/16',
            'appearance_text'    => 'nullable|string|max:3000',
            'voice_profile_text' => 'nullable|string|max:3000',
            'rules_text'         => 'nullable|string|max:3000',
            'sort_order'         => 'nullable|integer',
            'status'             => 'nullable|in:0,1',
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('portal-social-reel-characters.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['character'] = $this->repo->show((int) $id);
        if (!$data['character']) {
            return redirect()->route('portal-social-reel-characters.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Edit Reel Character';
        return view('portal.social.reel-characters.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->rules());

        $result = $this->repo->update($request, (int) $id);
        if ($result['status']) {
            return redirect()->route('portal-social-reel-characters.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function destroy($id)
    {
        $result = $this->repo->destroy((int) $id);
        return redirect()->route('portal-social-reel-characters.index')->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

}
