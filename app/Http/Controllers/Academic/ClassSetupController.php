<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Academic\ClassSetup\ClassSetupStoreRequest;
use App\Http\Requests\Academic\ClassSetup\ClassSetupUpdateRequest;
use App\Interfaces\Academic\ClassesInterface;
use App\Interfaces\Academic\ClassSetupInterface;
use App\Interfaces\Academic\SectionInterface;
use App\Interfaces\SessionInterface;

class ClassSetupController extends Controller
{
    private $repo;
    private $session;
    private $classes;
    private $section;

    function __construct(
        ClassSetupInterface $repo,
        SessionInterface $session,
        ClassesInterface $classes,
        SectionInterface $section
        )
    {
        $this->repo          = $repo;
        $this->session       = $session;
        $this->classes       = $classes;
        $this->section       = $section;
    }

    public function getSections(Request $request){
        $data = $this->repo->getSections($request->id);
        return response()->json($data);
    }

    public function index()
    {
        $data['title']              = ___('academic.class_setup');
        $data['class_setups']       = $this->repo->getPaginateAll();

        return view('backend.academic.class_setup.index', compact('data'));

    }

    public function create()
    {
        $data['title']              = ___('academic.class_setup');
        $data['classes']            = $this->classes->all();
        $data['section']            = $this->section->all();
        return view('backend.academic.class_setup.create', compact('data'));

    }

    public function store(ClassSetupStoreRequest $request)
    {
        // dd($request->all());
        $result = $this->repo->store($request);
        if($result['status']){
            return redirect()->route('class-setup.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['class_setup']        = $this->repo->show($id);
        $data['title']              = ___('academic.class_setup');
        $data['classes']            = $this->classes->all();
        $data['section']            = $this->section->all();

        $data['class_setup_sections']  = $data['class_setup']->classSetupChildrenAll->pluck('section_id')->toArray();

        return view('backend.academic.class_setup.edit', compact('data'));
    }

    public function update(ClassSetupUpdateRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if($result['status']){
            return redirect()->route('class-setup.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {

        $result = $this->repo->destroy($id);
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
        $deleted = $this->repo->bulkDestroy($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status'));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
