<?php

namespace App\Repositories\LearningEngine;

use App\Models\LearningEngine\ReflectionJournalEntry;

/**
 * Phase 4: a short daily reflection — what was hard, what strategy worked —
 * read straight back on the dashboard so a student can see their own past
 * answers, not just re-fill a blank box. One entry per day; re-saving the
 * same day edits it in place instead of creating a second row.
 */
class ReflectionJournalRepository
{
    private $events;

    public function __construct(LearningEventRepository $events)
    {
        $this->events = $events;
    }

    public function today(int $studentId): ?ReflectionJournalEntry
    {
        return ReflectionJournalEntry::where('student_id', $studentId)
            ->where('entry_date', now()->toDateString())
            ->first();
    }

    public function save(int $studentId, ?string $whatWasHard, ?string $whatWorked): ReflectionJournalEntry
    {
        $entry = ReflectionJournalEntry::firstOrNew([
            'student_id' => $studentId,
            'entry_date' => now()->toDateString(),
        ]);

        $isFirstToday = !$entry->exists;

        $entry->what_was_hard = $whatWasHard;
        $entry->what_worked   = $whatWorked;
        $entry->save();

        if ($isFirstToday) {
            $this->events->record($studentId, LearningEventRepository::EVENT_REFLECTION_SUBMITTED, null, ['source' => 'reflection_journal']);
        }

        return $entry;
    }
}
