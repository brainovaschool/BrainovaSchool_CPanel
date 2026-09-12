<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\ProgramFocus;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramFocusRepository
{
    use ReturnFormatTrait;

    private $focus;

    public function __construct(ProgramFocus $focus)
    {
        $this->focus = $focus;
    }

    public function getAll()
    {
        return $this->focus->with('category')->withCount('programs')
            ->orderBy('program_category_id')->orderBy('sort_order')->orderBy('name')
            ->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->focus->find($id);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row                      = new $this->focus;
            $row->program_category_id = $request->program_category_id;
            $row->name                = $request->name;
            $row->slug                = $this->uniqueSlug($request->name, $request->program_category_id);
            $row->description         = $request->description;
            $row->sort_order          = (int) $request->sort_order;
            $row->status              = $request->status;
            $row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row                      = $this->focus->findOrFail($id);
            $row->program_category_id = $request->program_category_id;
            $row->name                = $request->name;
            $row->slug                = $this->uniqueSlug($request->name, $request->program_category_id, $row->id);
            $row->description         = $request->description;
            $row->sort_order          = (int) $request->sort_order;
            $row->status              = $request->status;
            $row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->focus->findOrFail($id)->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids)
    {
        try {
            $this->focus->whereIn('id', $ids)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->focus->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    private function uniqueSlug(string $name, $categoryId, $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'focus';
        $slug = $base;
        $i    = 2;

        while (
            $this->focus->where('program_category_id', $categoryId)->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
