<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\LearningEngine\Skill;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SkillRepository
{
    use ReturnFormatTrait;

    private $skill;

    public function __construct(Skill $skill)
    {
        $this->skill = $skill;
    }

    public function getAll()
    {
        return $this->skill->with(['subject', 'classroom'])
            ->orderBy('classes_id')->orderBy('subject_id')->orderBy('sort_order')
            ->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->skill->find($id);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row              = new $this->skill;
            $row->subject_id  = $request->subject_id;
            $row->classes_id  = $request->classes_id;
            $row->title       = $request->title;
            $row->slug        = $this->uniqueSlug($request->title);
            $row->description = $request->description;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;
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
            $row              = $this->skill->findOrFail($id);
            $row->subject_id  = $request->subject_id;
            $row->classes_id  = $request->classes_id;
            $row->title       = $request->title;
            $row->slug        = $this->uniqueSlug($request->title, $row->id);
            $row->description = $request->description;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;
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
        try {
            $this->skill->findOrFail($id)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids)
    {
        try {
            $this->skill->whereIn('id', $ids)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->skill->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    private function uniqueSlug(string $title, $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'skill';
        $slug = $base;
        $i    = 2;

        while (
            $this->skill->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
