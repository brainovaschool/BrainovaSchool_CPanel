<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\AiHelperRepository;

class AiHelperController extends Controller
{
    private $repo;

    public function __construct(AiHelperRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['title'] = ___('settings.ai_helper');
        return view('website-setup.ai-helper.index', compact('data'));
    }

    public function update(Request $request)
    {
        $result = $this->repo->updateSettings($request);
        if ($result) {
            return redirect()->back()->with('success', ___('alert.updated_successfully'));
        }
        return redirect()->back()->with('danger', ___('alert.something_went_wrong_please_try_again'));
    }
}
