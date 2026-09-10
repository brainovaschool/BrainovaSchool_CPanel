<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteSetup\TrialSlot\TrialSlotRequest;
use App\Repositories\WebsiteSetup\TrialSlotRepository;
use Illuminate\Support\Facades\Schema;

class TrialSlotController extends Controller
{
    private $repo;

    public function __construct(TrialSlotRepository $repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')) {
            abort(400);
        }
        $this->repo = $repo;
    }

    public function index()
    {
        $data['slots'] = $this->repo->getAll();
        $data['title'] = ___('settings.free_trial_slots');
        return view('website-setup.trial-slot.index', compact('data'));
    }

    public function create()
    {
        $data['title'] = ___('settings.add_free_trial_slot');
        return view('website-setup.trial-slot.create', compact('data'));
    }

    public function store(TrialSlotRequest $request)
    {
        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('trial-slot.index')->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['slot'] = $this->repo->show($id);
        if (!$data['slot']) {
            return redirect()->route('trial-slot.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = ___('settings.edit_free_trial_slot');
        return view('website-setup.trial-slot.edit', compact('data'));
    }

    public function update(TrialSlotRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('trial-slot.index')->with('success', $result['message']);
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
}
