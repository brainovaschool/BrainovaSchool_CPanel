<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\WatchLearnTemplate;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;

class WatchLearnTemplateRepository
{
    use ReturnFormatTrait;
    use CommonHelperTrait;

    private const UPLOAD_PATH = 'backend/uploads/watch-learn-templates';

    private $model;

    public function __construct(WatchLearnTemplate $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with('upload')->orderBy('sort_order')->orderBy('id')->paginate(Settings::PAGINATE);
    }

    public function activeByOrientation(string $orientation)
    {
        return $this->model->active()->where('orientation', $orientation)->orderBy('sort_order')->get();
    }

    public function show($id)
    {
        return $this->model->with('upload')->find($id);
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
            $row = $this->model->findOrFail($id);
            if ($row->upload_id) {
                $this->UploadImageDelete($row->upload_id);
            }
            $row->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids): array
    {
        try {
            foreach ($this->model->whereIn('id', $ids)->get() as $row) {
                if ($row->upload_id) {
                    $this->UploadImageDelete($row->upload_id);
                }
                $row->delete();
            }
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
        $row->name        = $request->name;
        $row->orientation = $request->orientation === 'portrait' ? 'portrait' : 'landscape';
        $row->sort_order  = (int) $request->sort_order;
        $row->status      = $request->status;

        if ($request->hasFile('image')) {
            if ($row->upload_id) {
                $this->UploadImageDelete($row->upload_id);
            }
            $row->upload_id = $this->UploadImageCreate($request->file('image'), self::UPLOAD_PATH);
        }
    }
}
