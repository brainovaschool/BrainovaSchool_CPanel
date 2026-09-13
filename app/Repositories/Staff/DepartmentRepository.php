<?php

namespace App\Repositories\Staff;

use App\Models\Staff\Department;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\Auth;
use App\Interfaces\Staff\DepartmentInterface;

class DepartmentRepository implements DepartmentInterface
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(Department $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->active()->get();
    }

    public function getPaginateAll()
    {
        return $this->model::with('manager')->latest()->paginate(10);
    }

    public function getAsOptions()
    {
        return $this->model->active()->pluck('name', 'id');
    }

    public function staffDepartment()
    {
        return $this->model->active()->where("staff_user_id", Auth::user()->staff->id)->pluck('name', 'id');
    }

    public function store($request)
    {
        try {
            $row                = new $this->model;
            $row->name          = $request->name;
            $row->staff_user_id = blank($request->staff_user_id) ? null : $request->staff_user;
            $row->status        = $request->status;
            $row->save();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function update($request, $id)
    {
        try {
            $row                = $this->model->findOrfail($id);
            $row->name          = $request->name;
            $row->staff_user_id = blank($request->staff_user_id) ? null : $request->staff_user;
            $row->status        = $request->status;
            $row->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $row = $this->model->find($id);
            $row->delete();
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

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->model->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }
}
