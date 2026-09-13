<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Subject\SubjectStoreRequest;
use App\Http\Requests\Academic\Subject\SubjectUpdateRequest;
use App\Interfaces\Academic\SubjectInterface;
use Illuminate\Support\Facades\Schema;

class SubjectController extends Controller
{
    private $subject;

    function __construct(SubjectInterface $subject)
    {

        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        } 
        $this->subject       = $subject; 
    }

    public function index()
    {
        $data['subject'] = $this->subject->getAll();
        $data['title'] = ___('academic.subject');
        return view('backend.academic.subject.index', compact('data'));
    }

    public function create()
    {
        $data['title']       = ___('academic.create_subject');
        return view('backend.academic.subject.create', compact('data'));
    }

    public function store(SubjectStoreRequest $request)
    {
        $result = $this->subject->store($request);
        if($result['status']){
            return redirect()->route('subject.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['subject']        = $this->subject->show($id);
        $data['title']       = ___('academic.edit_subject');
        return view('backend.academic.subject.edit', compact('data'));
    }

    public function update(SubjectUpdateRequest $request, $id)
    {
        $result = $this->subject->update($request, $id);
        if($result['status']){
            return redirect()->route('subject.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->subject->destroy($id);
        if($result['status']):
            $success[0] = $result['message'];
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
            return response()->json($success);
        else:
            $success[0] = $result['message'];
            $success[1] = 'error';
            $success[2] = ___('alert.oops');
            return response()->json($success);
        endif;
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $deleted = $this->subject->bulkDestroy($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }
}
