<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\WatchLearnVideo;
use App\Traits\ReturnFormatTrait;

class WatchLearnVideoRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(WatchLearnVideo $model)
    {
        $this->model = $model;
    }

    public function getAll(?string $orientation = null)
    {
        return $this->model->with(['tab', 'template.upload'])
            ->when($orientation, fn ($q) => $q->where('orientation', $orientation))
            ->orderBy('sort_order')->orderByDesc('id')
            ->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->model->with(['tab', 'template.upload'])->find($id);
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
            $this->fill($row, $request, true);
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

    /** Orientation is locked at creation (whichever "Add ... Video" button
     *  the admin used) and never changes on update — a video keeps the
     *  template/tile it was built around. */
    private function fill($row, $request, bool $isUpdate = false): void
    {
        if (!$isUpdate) {
            $row->orientation = $request->orientation === 'portrait' ? 'portrait' : 'landscape';
        }
        $row->title       = $request->title;
        $row->video_url   = trim((string) $request->video_url);
        $row->tab_id      = $request->filled('tab_id') ? $request->tab_id : null;
        $row->template_id = $request->filled('template_id') ? $request->template_id : null;
        $row->sort_order  = (int) $request->sort_order;
        $row->status      = $request->status;
    }
}
