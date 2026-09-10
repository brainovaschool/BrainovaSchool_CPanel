<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\Program;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramRepository
{
    use ReturnFormatTrait;
    use CommonHelperTrait;

    private $program;

    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    public function getAll()
    {
        return $this->program->with(['category', 'focus'])
            ->orderBy('program_category_id')->orderBy('sort_order')->orderBy('title')
            ->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->program->find($id);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row = new $this->program;
            $this->fill($row, $request);

            if ($request->hasFile('image')) {
                $row->upload_id = $this->UploadImageCreate($request->image, 'backend/uploads/programs');
            }

            $row->slug = $this->uniqueSlug($request->title);
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
            $row = $this->program->findOrFail($id);
            $this->fill($row, $request);

            if ($request->hasFile('image')) {
                $row->upload_id = $this->UploadImageUpdate($request->image, 'backend/uploads/programs', $row->upload_id);
            }

            $row->slug = $this->uniqueSlug($request->title, $row->id);
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
            $row = $this->program->findOrFail($id);
            $this->UploadImageDelete($row->upload_id);
            $row->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    private function fill(Program $row, $request): void
    {
        $row->program_category_id = $request->program_category_id;
        $row->program_focus_id    = $request->program_focus_id ?: null;
        $row->title               = $request->title;
        $row->badge               = $request->badge;
        $row->description         = $request->description;
        $row->age_range           = $request->age_range;
        $row->grade               = $request->grade;
        $row->lessons             = $request->lessons;
        $row->duration            = $request->duration;
        $row->enrolled            = $request->enrolled;
        $row->price               = $request->price;
        $row->accent              = $request->accent ?: 'teal';
        $row->image_url           = $request->image_url;
        $row->meta_description    = $request->meta_description;
        $row->overview            = $this->linesToArray($request->overview);
        $row->highlights          = $this->linesToArray($request->highlights);
        $row->format              = $request->format;
        $row->sort_order          = (int) $request->sort_order;
        $row->status              = $request->status;
    }

    /** Textarea -> array: one item per non-empty line. */
    private function linesToArray($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), 'strlen'));
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $value);

        return array_values(array_filter(array_map('trim', $lines), 'strlen'));
    }

    private function uniqueSlug(string $title, $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'program';
        $slug = $base;
        $i    = 2;

        while (
            $this->program->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
