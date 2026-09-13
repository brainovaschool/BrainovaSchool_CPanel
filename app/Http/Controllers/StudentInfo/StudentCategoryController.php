<?php

namespace App\Http\Controllers\StudentInfo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\StudentInfo\StudentCategoryRepository;
use App\Http\Requests\StudentInfo\StudentCategory\StudentCategoryStoreRequest;
use App\Http\Requests\StudentInfo\StudentCategory\StudentCategoryUpdateRequest;

class StudentCategoryController extends Controller
{
    private $repo;

    function __construct(StudentCategoryRepository $repo)
    {
        $this->repo       = $repo; 
    }
    
    public function index()
    {
        $data['title']              = ___('student_info.category_list');
        $data['student_categories'] = $this->repo->getPaginateAll();

        return view('backend.student-info.student-category.index', compact('data'));
        
    }

    public function create()
    {
        $data['title']              = ___('student_info.category_create');
        return view('backend.student-info.student-category.create', compact('data'));
        
    }

    public function store(StudentCategoryStoreRequest $request)
    {
        $result = $this->repo->store($request);
        if($result['status']){
            return redirect()->route('student_category.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['student_category']        = $this->repo->show($id);
        $data['title']       = ___('student_info.category_edit');
        return view('backend.student-info.student-category.edit', compact('data'));
    }

    public function update(StudentCategoryUpdateRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);
        if($result){
            return redirect()->route('student_category.index')->with('success', ___('alert.updated_successfully'));
        }
        return back()->with('danger', ___('alert.something_went_wrong_please_try_again'));
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
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
