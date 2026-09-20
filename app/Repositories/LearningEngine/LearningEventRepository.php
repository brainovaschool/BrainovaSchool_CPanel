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
    public const EVENT_TAUGHT_KEA       = 'taught_kea';
    public const EVENT_REFLECTION_SUBMITTED = 'reflection_submitted';

    /** A bare "the dashboard was opened today" marker — 0 XP, 0 coins,
     *  deliberately outside the points/Level economy. This is the only
     *  input to the Knowledge Tree (see LearningHomeRepository::knowledgeTree()),
     *  which is meant to reward showing up, not performance. */
    public const EVENT_DASHBOARD_VISIT = 'dashboard_visit';

    // Phase 3, idea #8: reward the behavior, not just raw correctness — fixing
    // a past mistake and mastering a skill earn far more than a routine
    // correct answer, on purpose, so the incentive is "learn", not "click
    // fast". These three numbers are the entire XP formula — no AI, no
    // hidden weighting, easy to explain to a parent or a student.
    private const XP_CORRECT_ANSWER = 5;
    private const XP_RECOVERY_BONUS = 15;
    private const XP_MASTERY_BONUS  = 50;

    // Phase 4, idea #7 "Teach Kea": explaining a skill in your own words is
    // harder than answering a multiple-choice question, so it pays more than
    // a routine correct answer even on a partial attempt.
    private const XP_TEACH_KEA_UNDERSTOOD = 30;
    private const XP_TEACH_KEA_ATTEMPT    = 10;

    // Phase 4: reflecting on a learning session — what was hard, what
    // strategy worked — is its own worthwhile habit, once per day (the
    // repository only calls record() the first time a day's entry is
    // saved, so re-editing the same day's entry doesn't re-earn XP).
    private const XP_REFLECTION = 10;

    public function record(int $studentId, string $eventType, ?int $skillId = null, array $payload = []): void
    {
        try {
            $xp = 0;

            if ($eventType === self::EVENT_ANSWER_SUBMITTED && $skillId) {
                $correct = (bool) ($payload['correct'] ?? false);
                $result  = $this->updateMastery($studentId, $skillId, $correct);

                // Stamped onto the event's own payload (not just folded into
                // the xp total) so "was this a recovery?" stays answerable
                // later without having to reverse-engineer it from a number
                // — Phase 3's daily goals need exactly this.
                $payload['was_recovery']   = $result['was_recovery'];
                $payload['newly_mastered'] = $result['newly_mastered'];
                $xp = $this->xpFor($correct, $result['was_recovery'], $result['newly_mastered']);
            } elseif ($eventType === self::EVENT_TAUGHT_KEA) {
                // Teaching Kea doesn't touch mastery — it's a separate,
                // reflective way of practicing, not a graded answer — so it
                // earns XP on its own track instead of running through
                // updateMastery().
                $xp = ($payload['understood'] ?? false) ? self::XP_TEACH_KEA_UNDERSTOOD : self::XP_TEACH_KEA_ATTEMPT;
            } elseif ($eventType === self::EVENT_REFLECTION_SUBMITTED) {
                $xp = self::XP_REFLECTION;
            }

            LearningEvent::create([
                'student_id' => $studentId,
                'skill_id'   => $skillId,
                'event_type' => $eventType,
                'payload'    => $payload,
                'xp'         => $xp,
                // Coins are a second, SPENDABLE currency — earned 1-for-1
                // with XP on every event, but tracked separately so the
                // avatar shop can spend them without ever touching the XP
                // total that drives Brain Level. See totalCoinsEarned().
                'coins'      => $xp,
            ]);
        } catch (\Throwable $th) {
            Log::warning('Learning event record failed: ' . $th->getMessage());
        }
    }

    private function xpFor(bool $correct, bool $wasRecovery, bool $newlyMastered): int
    {
        if (!$correct) {
            return 0;
        }

        $xp = self::XP_CORRECT_ANSWER;
        $xp += $wasRecovery ? self::XP_RECOVERY_BONUS : 0;
        $xp += $newlyMastered ? self::XP_MASTERY_BONUS : 0;

        return $xp;
    }

    /** Total XP a student has earned — Brain Level (LearningHomeRepository)
     *  is derived entirely from this one number, always recomputable from
     *  the event log rather than kept in a separate ledger that could drift. */
    public function totalXp(int $studentId): int
    {
        return (int) LearningEvent::where('student_id', $studentId)->sum('xp');
    }

    /** Lifetime coins earned — spending in the avatar shop is tracked
     *  separately (student_avatar_purchases), never subtracted here, so this
     *  number always matches "coins earned" even after spending some. */
    public function totalCoinsEarned(int $studentId): int
    {
        return (int) LearningEvent::where('student_id', $studentId)->sum('coins');
    }

    /** Marks today as "visited" for the Knowledge Tree, once per day — safe
     *  to call on every dashboard load, since the dedupe check means a page
     *  refresh never creates a second row for the same day. */
    public function recordVisitIfNeeded(int $studentId): void
    {
        $alreadyToday = LearningEvent::where('student_id', $studentId)
            ->where('event_type', self::EVENT_DASHBOARD_VISIT)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if (!$alreadyToday) {
            LearningEvent::create([
                'student_id' => $studentId,
                'event_type' => self::EVENT_DASHBOARD_VISIT,
                'xp'         => 0,
                'coins'      => 0,
            ]);
        }
    }

    private function updateMastery(int $studentId, int $skillId, bool $correct): array
    {
        $mastery = StudentSkillMastery::firstOrNew([
            'student_id' => $studentId,
            'skill_id'   => $skillId,
        ]);

        $wasAdvanced           = $mastery->mastery_level === 'advanced';
        $hadOutstandingMistake = ($mastery->attempts_count ?? 0) > 0 && ($mastery->correct_count ?? 0) < $mastery->attempts_count;

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

        $newlyMastered = $previousLevel !== 'advanced' && $mastery->mastery_level === 'advanced';

        if ($newlyMastered) {
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

        return [
            'was_recovery'   => $correct && $hadOutstandingMistake,
            'newly_mastered' => $newlyMastered,
        ];
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
