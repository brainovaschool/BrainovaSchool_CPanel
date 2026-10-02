<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\KnowledgeHubPage;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Str;

class KnowledgeHubPageRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(KnowledgeHubPage $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->withCount('topics')->orderBy('sort_order')->orderBy('id')->paginate(Settings::PAGINATE);
    }

    public function activeOrdered()
    {
        return $this->model->active()->withCount('activeTopics')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function showBySlug(string $slug)
    {
        return $this->model->active()->where('slug', $slug)->first();
    }

    public function store($request): array
    {
        try {
            $row = new $this->model;
            $row->title      = $request->title;
            $row->slug       = $this->uniqueSlug($request->title);
            $row->sort_order = (int) $request->sort_order;
            $row->status     = $request->status;
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
            if ($row->title !== $request->title) {
                $row->slug = $this->uniqueSlug($request->title, $row->id);
            }
            $row->title      = $request->title;
            $row->sort_order = (int) $request->sort_order;
            $row->status     = $request->status;
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

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $i    = 2;
        while ($this->model->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
