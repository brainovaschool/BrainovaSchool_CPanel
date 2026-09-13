<?php

namespace App\Repositories\StudentInfo;

use App\Enums\ApiStatus;
use App\Traits\ReturnFormatTrait;
use App\Models\StudentInfo\StudentCategory;
use App\Interfaces\StudentInfo\StudentCategoryInterface;

class StudentCategoryRepository implements StudentCategoryInterface
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(StudentCategory $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->active()->get();
    }

    public function getPaginateAll()
    {
        return $this->model::latest()->paginate(10);
    }

    public function store($request)
    {
        try {
            $store              = new $this->model;
            $store->name        = $request->name;
            $store->status      = $request->status;
            $store->save();
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
            $update              = $this->model->findOrfail($id);
            $update->name        = $request->name;
            $update->status      = $request->status;
            $update->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('school.Something went wrong'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $bloodGroupDestroy = $this->model->find($id);
            $bloodGroupDestroy->delete();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('school.Something went wrong'), []);
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
