<?php

namespace App\Http\Controllers\ClassContent;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ClassContent\ClassContentModuleRepository;

/** Admin's final approval queue — only ever shows what the coordinator has
 *  already reviewed (see ClassContentModuleRepository::forAdmin). Approving
 *  is what actually makes a module visible to students/parents. */
class ClassContentAdminController extends Controller
{
    private $repo;

    public function __construct(ClassContentModuleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $data['modules'] = $this->repo->forAdmin($request->get('status'));
        $data['status']  = $request->get('status');
        $data['counts']  = $this->repo->statusCounts();
        $data['title']   = 'Approve Class Content';
        return view('class-content.admin.index', compact('data'));
    }

    public function show($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('class-content-admin.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Approve — ' . $data['item']->title;
        return view('class-content.admin.show', compact('data'));
    }

    public function decide(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:approve,back_to_coordinator,request_changes',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $adminStaffId = Auth::user()->staff?->id;
        $result = $this->repo->adminDecision($request, (int) $id, $adminStaffId !== null ? (int) $adminStaffId : null);

        $messages = [
            'approve'             => 'Approved and published to students.',
            'back_to_coordinator' => 'Sent back to the coordinator for another look.',
            'request_changes'     => 'Sent back to the teacher with your feedback.',
        ];

        return redirect()->route('class-content-admin.index')
            ->with($result['status'] ? 'success' : 'danger', $result['status'] ? $messages[$request->decision] : $result['message']);
    }
}
