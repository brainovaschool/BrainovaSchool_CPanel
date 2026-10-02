<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\KnowledgeHubTopic;
use App\Traits\ReturnFormatTrait;

class KnowledgeHubTopicRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(KnowledgeHubTopic $model)
    {
        $this->model = $model;
    }

    public function getAll(int $pageId)
    {
        return $this->model->where('page_id', $pageId)->orderBy('sort_order')->orderBy('id')->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function store($request, int $pageId): array
    {
        try {
            $row = new $this->model;
            $row->page_id     = $pageId;
            $row->title       = $request->title;
            $row->explanation = $request->explanation;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;
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
            $row->title       = $request->title;
            $row->explanation = $request->explanation;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;
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
}
