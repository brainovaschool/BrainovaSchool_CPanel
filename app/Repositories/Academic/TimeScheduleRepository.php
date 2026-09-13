<?php

namespace App\Repositories\Academic;

use App\Enums\Settings;
use App\Interfaces\Academic\TimeScheduleInterface;
use App\Models\Academic\TimeSchedule;
use App\Traits\ReturnFormatTrait;

class TimeScheduleRepository implements TimeScheduleInterface
{
    use ReturnFormatTrait;
    private $time;

    public function __construct(TimeSchedule $time)
    {
        $this->time = $time;
    }

    public function all()
    {
        return $this->time->active()->get();
    }

    public function allClassSchedule()
    {
        return $this->time->active()->where('type', 1)->get();
    }
    public function allExamSchedule()
    {
        return $this->time->active()->where('type', 2)->get();
    }

    public function getAll()
    {
        return $this->time->latest()->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        try {
            $result = $this->time->where('type', $request->type)->get();

            foreach ($result as $key => $value) {
                if($value->start_time <= $request->start_time && $request->start_time <= $value->end_time || $value->start_time <= $request->end_time && $request->end_time <= $value->end_time) {
                    return $this->responseWithError(___('alert.Already assigned.'), []);
                }
            }

            $timeStore              = new $this->time;
            $timeStore->type        = $request->type;
            $timeStore->status      = $request->status;
            $timeStore->start_time  = $request->start_time;
            $timeStore->end_time    = $request->end_time;
            $timeStore->save();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->time->find($id);
    }

    public function update($request, $id)
    {
        try {
            $result = $this->time->where('type', $request->type)->get();

            foreach ($result as $key => $value) {
                if($value->start_time <= $request->start_time && $request->start_time <= $value->end_time && $value->id != $id || $value->start_time <= $request->end_time && $request->end_time <= $value->end_time && $value->id != $id) {
                    return $this->responseWithError(___('alert.Already assigned.'), []);
                }
            }
            
            $timeUpdate              = $this->time->findOrfail($id);
            $timeUpdate->type        = $request->type;
            $timeUpdate->status      = $request->status;
            $timeUpdate->start_time  = $request->start_time;
            $timeUpdate->end_time    = $request->end_time;
            $timeUpdate->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $timeDestroy = $this->time->find($id);
            $timeDestroy->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->destroy($id)['status']) {
                $deleted++;
            }
        }
        return $deleted;
    }
}
