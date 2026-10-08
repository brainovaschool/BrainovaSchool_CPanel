<?php

namespace App\Repositories\ClassContent;

use App\Models\ClassContent\ClassContentActivity;
use App\Models\ClassContent\ClassContentLesson;
use App\Models\ClassContent\ClassContentMaterial;
use App\Models\ClassContent\ClassContentOutcome;
use App\Traits\ReturnFormatTrait;

class ClassContentLessonRepository
{
    use ReturnFormatTrait;

    public function forModule(int $moduleId)
    {
        return ClassContentLesson::where('module_id', $moduleId)
            ->withCount(['materials', 'activities', 'outcomes'])
            ->orderBy('sort_order')
            ->get();
    }

    /** Scoped to $moduleId so a lesson id can't be swapped in across module
     *  boundaries — the controller only ever authorizes the module in the
     *  URL, so without this filter any lesson id would be readable/editable
     *  through a module its author doesn't actually own. */
    public function show(int $id, int $moduleId): ?ClassContentLesson
    {
        return ClassContentLesson::with(['materials', 'activities', 'outcomes'])
            ->where('module_id', $moduleId)
            ->find($id);
    }

    public function store($request, int $moduleId): array
    {
        try {
            $row             = new ClassContentLesson();
            $row->module_id  = $moduleId;
            $row->title      = $request->title;
            $row->description = $request->description;
            $row->video_url  = trim((string) $request->video_url) ?: null;
            $row->class_date = $request->class_date ?: null;
            $row->sort_order = (int) $request->sort_order;
            $row->save();

            $this->syncChildren($row, $request);

            return $this->responseWithSuccess(___('alert.created_successfully'), ['id' => $row->id]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, int $id, int $moduleId): array
    {
        try {
            $row              = ClassContentLesson::where('module_id', $moduleId)->findOrFail($id);
            $row->title       = $request->title;
            $row->description = $request->description;
            $row->video_url   = trim((string) $request->video_url) ?: null;
            $row->class_date  = $request->class_date ?: null;
            $row->sort_order  = (int) $request->sort_order;
            $row->save();

            $this->syncChildren($row, $request);

            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy(int $id, int $moduleId): array
    {
        try {
            ClassContentLesson::where('module_id', $moduleId)->findOrFail($id)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Materials/Activities/Outcomes are always replaced whole on save —
     *  simpler and safer than diffing individual rows, and the teacher's
     *  one form already shows everything at once, so nothing is ever
     *  edited blind. */
    private function syncChildren(ClassContentLesson $lesson, $request): void
    {
        ClassContentMaterial::where('lesson_id', $lesson->id)->delete();
        foreach ((array) $request->input('materials.label', []) as $i => $label) {
            $url = $request->input("materials.url.{$i}");
            if (trim((string) $label) === '' || trim((string) $url) === '') {
                continue;
            }
            ClassContentMaterial::create([
                'lesson_id'  => $lesson->id,
                'label'      => $label,
                'url'        => $url,
                'sort_order' => $i,
            ]);
        }

        ClassContentActivity::where('lesson_id', $lesson->id)->delete();
        foreach ((array) $request->input('activities.title', []) as $i => $title) {
            if (trim((string) $title) === '') {
                continue;
            }
            ClassContentActivity::create([
                'lesson_id'   => $lesson->id,
                'title'       => $title,
                'description' => $request->input("activities.description.{$i}"),
                'video_url'   => trim((string) $request->input("activities.video_url.{$i}")) ?: null,
                'link_url'    => trim((string) $request->input("activities.link_url.{$i}")) ?: null,
                'sort_order'  => $i,
            ]);
        }

        ClassContentOutcome::where('lesson_id', $lesson->id)->delete();
        foreach ((array) $request->input('outcomes', []) as $i => $text) {
            if (trim((string) $text) === '') {
                continue;
            }
            ClassContentOutcome::create([
                'lesson_id'    => $lesson->id,
                'outcome_text' => $text,
                'sort_order'   => $i,
            ]);
        }
    }
}
