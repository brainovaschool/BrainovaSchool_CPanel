<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\LearningEngine\CharacterLine;
use App\Traits\ReturnFormatTrait;

class CharacterLineRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(CharacterLine $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->orderBy('character')->orderBy('context')->orderBy('sort_order')->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function store($request)
    {
        try {
            $row              = new $this->model;
            $row->character   = $request->character;
            $row->context     = trim($request->context);
            $row->line        = $request->line;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;
            $row->save();

            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, $id)
    {
        try {
            $row              = $this->model->findOrFail($id);
            $row->character   = $request->character;
            $row->context     = trim($request->context);
            $row->line        = $request->line;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;
            $row->save();

            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $this->model->findOrFail($id)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids)
    {
        try {
            $this->model->whereIn('id', $ids)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
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
