<?php

namespace App\Repositories\ClassContent;

use App\Models\ClassContent\ClassContentUnit;
use App\Traits\ReturnFormatTrait;

class ClassContentUnitRepository
{
    use ReturnFormatTrait;

    /** $staffId null (Admin) sees every unit - same "admin sees/edits
     *  everything" rule as ClassContentModuleRepository. */
    public function forTeacher(?int $staffId)
    {
        return ClassContentUnit::with(['class', 'section', 'subject'])
            ->withCount('modules')
            ->when($staffId !== null, fn ($q) => $q->where('created_by', $staffId))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    public function show(int $id): ?ClassContentUnit
    {
        return ClassContentUnit::find($id);
    }

    public function store($request, ?int $staffId): array
    {
        try {
            ClassContentUnit::create([
                'session_id' => setting('session'),
                'classes_id' => $request->classes_id,
                'section_id' => $request->section_id ?: null,
                'subject_id' => $request->subject_id,
                'title'      => $request->title,
                'description' => $request->description,
                'created_by' => $staffId,
                'sort_order' => (int) $request->sort_order,
            ]);
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, int $id): array
    {
        $row = ClassContentUnit::find($id);
        if (!$row) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        try {
            $row->classes_id   = $request->classes_id;
            $row->section_id   = $request->section_id ?: null;
            $row->subject_id   = $request->subject_id;
            $row->title        = $request->title;
            $row->description  = $request->description;
            $row->sort_order   = (int) $request->sort_order;
            $row->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Never touches its modules - they just lose their unit_id
     *  (nullOnDelete) and fall back into the dashboard's "Other modules"
     *  bucket, same as a module that was never grouped at all. */
    public function destroy(int $id): array
    {
        $row = ClassContentUnit::find($id);
        if (!$row) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        $row->delete();
        return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
    }
}
