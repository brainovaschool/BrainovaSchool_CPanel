<?php

namespace App\Repositories\LearningEngine;

use App\Models\LearningEngine\Skill;
use App\Models\LearningEngine\LearningEvent;
use App\Models\LearningEngine\StudentSkillMastery;
use Illuminate\Support\Facades\Log;

/**
 * Every feature (lessons, quizzes, future AI mentor modes) logs what a student
 * does through record(), and answer/practice events roll up into that
 * student's per-skill mastery here. Mastery is a fixed rule of thumb, not AI,
 * on purpose — this data has to stay correct and available even when an AI
 * service is down, since later phases (adaptive difficulty, "what's next")
 * read it as their non-AI fallback.
 */
class LearningEventRepository
{
    public const EVENT_LESSON_STARTED   = 'lesson_started';
    public const EVENT_LESSON_COMPLETED = 'lesson_completed';
    public const EVENT_ANSWER_SUBMITTED = 'answer_submitted';
    public const EVENT_HINT_USED        = 'hint_used';
    public const EVENT_QUEST_CLAIMED    = 'quest_claimed';

    public function record(int $studentId, string $eventType, ?int $skillId = null, array $payload = []): void
    {
        try {
            LearningEvent::create([
                'student_id' => $studentId,
                'skill_id'   => $skillId,
                'event_type' => $eventType,
                'payload'    => $payload,
            ]);

            if ($eventType === self::EVENT_ANSWER_SUBMITTED && $skillId) {
                $this->updateMastery($studentId, $skillId, (bool) ($payload['correct'] ?? false));
            }
        } catch (\Throwable $th) {
            Log::warning('Learning event record failed: ' . $th->getMessage());
        }
    }

    private function updateMastery(int $studentId, int $skillId, bool $correct): void
    {
        $mastery = StudentSkillMastery::firstOrNew([
            'student_id' => $studentId,
            'skill_id'   => $skillId,
        ]);

        $wasAdvanced = $mastery->mastery_level === 'advanced';

        $mastery->attempts_count    = ($mastery->attempts_count ?? 0) + 1;
        $mastery->correct_count     = ($mastery->correct_count ?? 0) + ($correct ? 1 : 0);
        $mastery->last_practiced_at = now();

        $accuracy      = $mastery->attempts_count > 0 ? $mastery->correct_count / $mastery->attempts_count : 0;
        $previousLevel = $mastery->mastery_level ?? 'not_started';

        if ($mastery->attempts_count >= 8 && $accuracy >= 0.9) {
            $mastery->mastery_level = 'advanced';
        } elseif ($mastery->attempts_count >= 5 && $accuracy >= 0.75) {
            $mastery->mastery_level = 'proficient';
        } elseif ($mastery->attempts_count >= 1) {
            $mastery->mastery_level = 'developing';
        }

        if ($previousLevel !== 'advanced' && $mastery->mastery_level === 'advanced') {
            $mastery->mastered_at          = now();
            $mastery->review_interval_days = 7;
            $mastery->next_review_at       = now()->addDays(7);
        } elseif ($wasAdvanced && $mastery->mastery_level === 'advanced') {
            // Practiced again after already being mastered — a spaced-review
            // check-in, not ordinary practice. Correct: push the next check
            // further out (classic spaced-repetition backoff, capped at 60
            // days). Wrong: this is "I forgot this" — schedule a refresher
            // soon, but mastery itself isn't revoked; they did master it once.
            $mastery->review_interval_days = $correct
                ? min(60, ($mastery->review_interval_days ?: 7) * 2)
                : 2;
            $mastery->next_review_at = now()->addDays($mastery->review_interval_days);
        }

        $mastery->save();
    }

    /**
     * The rule-based "what should this student do next" answer every later
     * AI-assisted feature must be able to fall back to: the least-mastered
     * active skills for this student in a subject/grade, sorted the same way
     * an admin ordered them. No AI call involved.
     */
    public function nextSkills(int $studentId, ?int $subjectId = null, ?int $classesId = null, int $limit = 5)
    {
        $mastered = StudentSkillMastery::where('student_id', $studentId)
            ->where('mastery_level', 'advanced')
            ->pluck('skill_id');

        return Skill::active()
            ->with('subject')
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($classesId, fn ($q) => $q->where('classes_id', $classesId))
            ->whereNotIn('id', $mastered)
            ->orderBy('sort_order')
            ->take($limit)
            ->get();
    }

    public function nextSkillFor(int $studentId, ?int $subjectId = null, ?int $classesId = null): ?Skill
    {
        return $this->nextSkills($studentId, $subjectId, $classesId, 1)->first();
    }
}
