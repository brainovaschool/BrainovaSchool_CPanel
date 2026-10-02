<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\WatchLearnTab;
use App\Traits\ReturnFormatTrait;

class WatchLearnTabRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(WatchLearnTab $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->orderBy('sort_order')->orderBy('id')->paginate(Settings::PAGINATE);
    }

    public function activeOrdered()
    {
        return $this->model->active()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function store($request): array
    {
        try {
            $row = new $this->model;
            $this->fill($row, $request);
            $row->save();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, $id): array
    {
        try {
            $row = $this->model->findOrFail($id);
            $this->fill($row, $request);
            $row->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id): array
    {
        try {
            $this->model->findOrFail($id)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids): array
    {
        try {
            $this->model->whereIn('id', $ids)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkStatus(array $ids, int $status): array
    {
        try {
            $this->model->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    private function fill($row, $request): void
    {
        $row->title      = $request->title;
        $row->sort_order = (int) $request->sort_order;
        $row->status     = $request->status;
    }
}
