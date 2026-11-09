<?php

namespace App\Repositories\ClassContent;

use App\Models\ClassContent\ClassContentCompletion;
use App\Traits\ReturnFormatTrait;

class ClassContentCompletionRepository
{
    use ReturnFormatTrait;

    /** Every completion row this student has, keyed "type:id" for O(1)
     *  lookups while rendering a dashboard or lesson page full of
     *  activities/projects/quizzes - one query instead of one per item. */
    public function mapForStudent(int $studentId): array
    {
        return ClassContentCompletion::where('student_id', $studentId)->get()
            ->keyBy(fn ($c) => $c->completable_type . ':' . $c->completable_id)
            ->all();
    }

    /** Phase 1: a plain "done" marker, no file/answer, no real grading -
     *  $score always stays null for now (reserved for a later quiz
     *  integration). Phase 2 (coins/XP) hangs off this same call - see the
     *  xp_awarded column and LearningEventRepository::EVENT_LESSON_COMPLETED
     *  - nothing here pays out yet. */
    public function markDone(int $studentId, string $type, int $id): array
    {
        if (!in_array($type, [ClassContentCompletion::LESSON, ClassContentCompletion::ACTIVITY, ClassContentCompletion::PROJECT, ClassContentCompletion::QUIZ], true)) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }

        ClassContentCompletion::firstOrCreate(
            ['student_id' => $studentId, 'completable_type' => $type, 'completable_id' => $id],
            ['completed_at' => now()]
        );

        return $this->responseWithSuccess('Marked complete.', []);
    }

    /** See ClassContentLesson::isDoneFor() - kept here too since callers
     *  that already have this repository shouldn't need to know the model
     *  carries the same logic. */
    public function lessonIsDone($lesson, array $map): bool
    {
        return $lesson->isDoneFor($map);
    }
}
