<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\ProgramCategory;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramCategoryRepository
{
    use ReturnFormatTrait;
    use CommonHelperTrait;

    private $category;

    public function __construct(ProgramCategory $category)
    {
        $this->category = $category;
    }

    public function getAll()
    {
        return $this->category->withCount(['focuses', 'programs'])
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(Settings::PAGINATE);
    }

    public function all()
    {
        return $this->category->where('status', 1)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function show($id)
    {
        return $this->category->find($id);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row                = new $this->category;
            $row->name          = $request->name;
            $row->slug          = $this->uniqueSlug($request->name);
            $row->tagline       = $request->tagline;
            $row->hero_title    = $request->hero_title;
            $row->hero_subtitle = $request->hero_subtitle;
            $row->image_url     = $request->image_url;
            $row->accent        = $request->accent ?: 'teal';
            $row->sort_order    = (int) $request->sort_order;
            $row->status        = $request->status;

            if ($request->hasFile('image')) {
                $row->upload_id = $this->UploadImageCreate($request->image, 'backend/uploads/program-categories');
            }

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
            $row                = $this->category->findOrFail($id);
            $row->name          = $request->name;
            $row->slug          = $this->uniqueSlug($request->name, $row->id);
            $row->tagline       = $request->tagline;
            $row->hero_title    = $request->hero_title;
            $row->hero_subtitle = $request->hero_subtitle;
            $row->image_url     = $request->image_url;
            $row->accent        = $request->accent ?: 'teal';
            $row->sort_order    = (int) $request->sort_order;
            $row->status        = $request->status;

            if ($request->hasFile('image')) {
                $row->upload_id = $this->UploadImageUpdate($request->image, 'backend/uploads/program-categories', $row->upload_id);
            }

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
            $row = $this->category->findOrFail($id);
            $this->UploadImageDelete($row->upload_id);
            $row->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids)
    {
        DB::beginTransaction();
        try {
            foreach ($this->category->whereIn('id', $ids)->get() as $row) {
                $this->UploadImageDelete($row->upload_id);
                $row->delete();
            }
            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->category->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    private function uniqueSlug(string $name, $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i    = 2;

        while (
            $this->category->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
