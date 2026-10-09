<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Portal\PortalSetting;
use App\Repositories\Portal\TaskRepository;

class SettingsController extends Controller
{
    private $repo;

    public function __construct(TaskRepository $repo)
    {
        $this->repo = $repo;
    }

    public function edit()
    {
        $data['settings'] = PortalSetting::current();
        $data['title']    = 'Team Portal Settings';
        return view('portal.settings.edit', compact('data'));
    }

    public function update(Request $request)
    {
        $result = $this->repo->updateSettings($request);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
