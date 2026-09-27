<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\LearningEngine\Mission;
use App\Traits\ReturnFormatTrait;

class MissionRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(Mission $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with(['hub', 'building'])->orderBy('hub_id')->orderBy('sort_order')->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function store($request): array
    {
        try {
            $row                 = new $this->model;
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
        $row->hub_id      = $request->hub_id;
        $row->building_id = $request->building_id ?: null;
        $row->theme       = $request->theme;
        $row->character   = $request->character;
        $row->title       = $request->title;
        $row->intro_line  = $request->intro_line;
        $row->ending_line = $request->ending_line;
        $row->reward_note = $request->reward_note;
        $row->sort_order  = (int) $request->sort_order;
        $row->status      = $request->status;

        $row->clue_lines = collect(explode("\n", (string) $request->clue_lines))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->take(7)
            ->all();

        if ($request->filled('linkable_type') && $request->filled('linkable_id')) {
            $row->linkable_type = $request->linkable_type;
            $row->linkable_id   = $request->linkable_id;
        } else {
            $row->linkable_type = null;
            $row->linkable_id   = null;
        }
    }
}
