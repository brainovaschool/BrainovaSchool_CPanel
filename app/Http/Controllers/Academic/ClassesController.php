<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Repositories\LanguageRepository;
use App\Interfaces\Academic\ClassesInterface;
use App\Http\Requests\Academic\Classes\ClassesStoreRequest;
use App\Http\Requests\Academic\Classes\ClassesUpdateRequest;

class ClassesController extends Controller
{
    private $classes;
    private $lang_repo;

    function __construct(ClassesInterface $classes, LanguageRepository $lang_repo)
    {

        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        }
        $this->classes       = $classes;
        $this->lang_repo       = $lang_repo;
    }

    public function index()
    {
        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            $data['class'] = $this->classes->getAllForAssignedTeacher((int) Auth::user()->staff->id);
        } else {
            $data['class'] = $this->classes->getAll();
        }
        $data['title'] = ___('academic.class');
        return view('backend.academic.class.index', compact('data'));
    }

    public function create()
    {
        if (Auth::check() && Auth::user()->role_id == 5) {
            abort(403);
        }
        $data['title']       = ___('academic.create_class');
        return view('backend.academic.class.create', compact('data'));
    }

    public function store(ClassesStoreRequest $request)
    {
        if (Auth::check() && Auth::user()->role_id == 5) {
            abort(403);
        }
        $result = $this->classes->store($request);
        if($result['status']){
            return redirect()->route('classes.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            if (! $this->classes->staffTeachesClass((int) Auth::user()->staff->id, (int) $id)) {
                abort(403);
            }
        }
        $data['class']       = $this->classes->show($id);
        $data['title']       = ___('academic.edit_class');
        return view('backend.academic.class.edit', compact('data'));
    }

    public function translate($id)
    {
        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            if (! $this->classes->staffTeachesClass((int) Auth::user()->staff->id, (int) $id)) {
                abort(403);
            }
        }
        $data['class']        = $this->classes->show($id);
        $data['translates']      = $this->classes->translates($id);
        $data['languages']      = $this->lang_repo->all();
        $data['title']       = ___('academic.edit_class');
        return view('backend.academic.class.translate', compact('data'));
    }

    public function translateUpdate(Request $request, $id){

        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            if (! $this->classes->staffTeachesClass((int) Auth::user()->staff->id, (int) $id)) {
                abort(403);
            }
        }
        $result = $this->classes->translateUpdate($request, $id);
        if($result['status']){
            return redirect()->route('classes.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function update(ClassesUpdateRequest $request, $id)
    {
        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            if (! $this->classes->staffTeachesClass((int) Auth::user()->staff->id, (int) $id)) {
                abort(403);
            }
        }
        $result = $this->classes->update($request, $id);
        if($result['status']){
            return redirect()->route('classes.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            abort(403);
        }
        $result = $this->classes->destroy($id);
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

    public function bulkDelete(\Illuminate\Http\Request $request)
    {
        if (Auth::check() && Auth::user()->role_id == 5 && Auth::user()->staff) {
            abort(403);
        }
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $deleted = $this->classes->bulkDestroy($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }
}
