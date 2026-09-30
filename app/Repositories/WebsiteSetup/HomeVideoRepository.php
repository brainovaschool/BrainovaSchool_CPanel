<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\HomeVideo;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;

class HomeVideoRepository
{
    use ReturnFormatTrait;
    use CommonHelperTrait;

    private const UPLOAD_PATH = 'backend/uploads/home-videos';

    private $model;

    public function __construct(HomeVideo $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with('upload')->orderBy('sort_order')->orderByDesc('id')->paginate(Settings::PAGINATE);
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

    /** A file and a link are mutually exclusive — whichever one the
     *  admin just supplied wins, and if that means switching away from
     *  an old uploaded file, it gets deleted rather than left orphaned
     *  in storage. Leaving both blank on an update keeps whatever was
     *  already saved (same "blank = don't touch it" rule as every other
     *  upload field in this app). */
    private function fill($row, $request): void
    {
        $row->title       = $request->title;
        $row->orientation = $request->orientation === 'portrait' ? 'portrait' : 'landscape';
        $row->autoplay    = (bool) $request->boolean('autoplay');
        $row->sort_order  = (int) $request->sort_order;
        $row->status      = $request->status;

        if ($request->hasFile('video_file')) {
            if ($row->upload_id) {
                $this->UploadImageDelete($row->upload_id);
            }
            $row->upload_id = $this->UploadImageCreate($request->file('video_file'), self::UPLOAD_PATH);
            $row->video_url = null;
        } elseif ($request->filled('video_url')) {
            if ($row->upload_id) {
                $this->UploadImageDelete($row->upload_id);
                $row->upload_id = null;
            }
            $row->video_url = trim((string) $request->video_url);
        }
    }
}
