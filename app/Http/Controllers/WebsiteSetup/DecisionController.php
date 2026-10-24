<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Repositories\WebsiteSetup\DecisionRuleRepository;
use Illuminate\Http\Request;

class DecisionController extends Controller
{
    private $repo;

    public function __construct(DecisionRuleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['title']      = 'Decisions';
        $data['rules']       = $this->repo->all();
        $data['categories']  = DecisionRuleRepository::CATEGORIES;
        $data['roles']       = Role::whereNotIn('id', [RoleEnum::SUPERADMIN])->orderBy('name')->get();

        return view('website-setup.decision.index', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:' . implode(',', array_keys(DecisionRuleRepository::CATEGORIES)),
        ]);

        $result = $this->repo->store($request);

        return redirect()->route('decision.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->repo->delete($id);

        return redirect()->route('decision.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
