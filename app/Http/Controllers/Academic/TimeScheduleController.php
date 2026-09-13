<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\TimeSchedule\TimeScheduleStoreRequest;
use App\Http\Requests\Academic\TimeSchedule\TimeScheduleUpdateRequest;
use App\Interfaces\Academic\TimeScheduleInterface;
use Illuminate\Support\Facades\Schema;

class TimeScheduleController extends Controller
{
    private $timeRepo;

    function __construct(TimeScheduleInterface $timeRepo)
    {

        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        }
        $this->timeRepo       = $timeRepo;
    }

    public function index()
    {
        $data['time_schedule'] = $this->timeRepo->getAll();
        $data['title'] = ___('academic.time_schedule');
        return view('backend.academic.time-schedule.index', compact('data'));
    }

    public function create()
    {
        $data['title']       = ___('academic.create_time_schedule');
        return view('backend.academic.time-schedule.create', compact('data'));
    }

    public function store(TimeScheduleStoreRequest $request)
    {
        $result = $this->timeRepo->store($request);
        if($result['status']){
            return redirect()->route('time_schedule.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function edit($id)
    {
        $data['time_schedule']        = $this->timeRepo->show($id);
        $data['title']        = ___('academic.edit_time_schedule');
        return view('backend.academic.time-schedule.edit', compact('data'));
    }

    public function update(TimeScheduleUpdateRequest $request, $id)
    {
        $result = $this->timeRepo->update($request, $id);
        if($result['status']){
            return redirect()->route('time_schedule.index')->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->timeRepo->destroy($id);
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
        $deleted = $this->timeRepo->bulkDestroy($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }
}
