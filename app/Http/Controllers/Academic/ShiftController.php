<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Shift\ShiftStoreRequest;
use App\Http\Requests\Academic\Shift\ShiftUpdateRequest;
use App\Interfaces\Academic\ShiftInterface;
use App\Repositories\LanguageRepository;
use Illuminate\Support\Facades\Schema;

class ShiftController extends Controller
{
    private $shift;
    private $lang_repo;

    function __construct(ShiftInterface $shift , LanguageRepository $lang_repo)
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        }
        $this->shift       = $shift;
        $this->lang_repo       = $lang_repo;
    }

    public function index()
    {
        $data['shift'] = $this->shift->getAll();
        $data['title'] = ___('academic.shift');
        return view('backend.academic.shift.index', compact('data'));
    }

    public function create()
    {
        $data['title']       = ___('academic.create_shift');
        return view('backend.academic.shift.create', compact('data'));
    }

    public function store(ShiftStoreRequest $request)
    {
        $result = $this->shift->store($request);
        if($result['status']){
            return redirect()->route('shift.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['shift']        = $this->shift->show($id);
        $data['title']        = ___('academic.edit_shift');
        return view('backend.academic.shift.edit', compact('data'));
    }

    public function update(ShiftUpdateRequest $request, $id)
    {
        $result = $this->shift->update($request, $id);
        if($result['status']){
            return redirect()->route('shift.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->shift->destroy($id);
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
        $deleted = $this->shift->bulkDestroy($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    public function translate($id)
    {
        $data['shift']        = $this->shift->show($id);
        $data['translates']      = $this->shift->translates($id);
        $data['languages']      = $this->lang_repo->all();
        $data['title']       = ___('academic.edit_shift');
        return view('backend.academic.shift.translate', compact('data'));
    }

    public function translateUpdate(Request $request, $id){
        $result = $this->shift->translateUpdate($request, $id);
        if($result['status']){
            return redirect()->route('shift.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }
}
