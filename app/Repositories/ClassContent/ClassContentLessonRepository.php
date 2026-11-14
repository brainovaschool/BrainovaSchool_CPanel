<?php

namespace App\Repositories\ClassContent;

use App\Models\ClassContent\ClassContentActivity;
use App\Models\ClassContent\ClassContentLesson;
use App\Models\ClassContent\ClassContentMaterial;
use App\Models\ClassContent\ClassContentOutcome;
use App\Services\B2Storage;
use App\Traits\ReturnFormatTrait;

class ClassContentLessonRepository
{
    use ReturnFormatTrait;

    private B2Storage $storage;

    public function __construct(B2Storage $storage)
    {
        $this->storage = $storage;
    }

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
            $this->fillProjectAndQuiz($row, $request);
            $row->save();

            $warnings = $this->syncChildren($row, $request);

            $message = ___('alert.created_successfully') . ($warnings ? ' — but: ' . implode(' ', $warnings) : '');
            return $this->responseWithSuccess($message, ['id' => $row->id]);
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
            $this->fillProjectAndQuiz($row, $request);
            $row->save();

            $warnings = $this->syncChildren($row, $request);

            $message = ___('alert.updated_successfully') . ($warnings ? ' — but: ' . implode(' ', $warnings) : '');
            return $this->responseWithSuccess($message, []);
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

    /** Project/Quiz are plain columns on the lesson, not child rows - a
     *  blank title means "this lesson has no project/quiz", so both halves
     *  are cleared together rather than leaving an orphaned description
     *  with no title behind it. */
    private function fillProjectAndQuiz(ClassContentLesson $row, $request): void
    {
        $projectTitle = trim((string) $request->input('project_title'));
        $row->project_title       = $projectTitle ?: null;
        $row->project_description = $projectTitle ? $request->input('project_description') : null;
        $row->project_link_url    = $projectTitle ? (trim((string) $request->input('project_link_url')) ?: null) : null;

        $quizTitle = trim((string) $request->input('quiz_title'));
        $row->quiz_title       = $quizTitle ?: null;
        $row->quiz_description = $quizTitle ? $request->input('quiz_description') : null;
        $row->quiz_link_url    = $quizTitle ? (trim((string) $request->input('quiz_link_url')) ?: null) : null;
    }

    /** Materials/Activities/Outcomes are always replaced whole on save —
     *  simpler and safer than diffing individual rows, and the teacher's
     *  one form already shows everything at once, so nothing is ever
     *  edited blind. A material's file can't be resubmitted by the browser
     *  on edit (file inputs never carry a previous selection), so the form
     *  echoes the existing storage_key/original_filename back as hidden
     *  fields and this carries them forward untouched unless a new file
     *  was actually chosen. */
    private function syncChildren(ClassContentLesson $lesson, $request): array
    {
        $warnings = [];

        ClassContentMaterial::where('lesson_id', $lesson->id)->delete();
        foreach ((array) $request->input('materials.label', []) as $i => $label) {
            $label = trim((string) $label);

            $storageKey = $request->input("materials.existing_storage_key.{$i}") ?: null;
            $originalFilename = $request->input("materials.existing_original_filename.{$i}") ?: null;
            $url = trim((string) $request->input("materials.url.{$i}"));

            $file = $request->file("materials.file.{$i}");
            if ($file && !$file->isValid()) {
                $warnings[] = "\"" . ($label ?: 'Untitled material') . "\": the file didn't upload properly (code {$file->getError()}) - try again.";
            } elseif ($file && $file->isValid()) {
                $uploaded = $this->storage->upload($file, 'class-content-materials');
                if ($uploaded['ok']) {
                    $storageKey = $uploaded['storage_key'];
                    $originalFilename = $uploaded['original_filename'];
                    $url = '';
                } else {
                    $warnings[] = "\"" . ($label ?: 'Untitled material') . "\": " . $uploaded['message'];
                }
            }

            if ($label === '' && !$storageKey && $url === '') {
                continue;
            }

            // A blank label with a real file/link attached is a minor
            // oversight, not a reason to throw the whole thing away - fall
            // back to the filename (or just "Link") rather than silently
            // dropping it, which is what used to happen here.
            if ($label === '') {
                $label = $originalFilename ?: 'Link';
            }

            ClassContentMaterial::create([
                'lesson_id'         => $lesson->id,
                'label'             => $label,
                'url'               => $storageKey ? null : $url,
                'storage_key'       => $storageKey,
                'original_filename' => $originalFilename,
                'sort_order'        => $i,
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

        return $warnings;
    }
}
