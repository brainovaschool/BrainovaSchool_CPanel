<?php

namespace App\Repositories\LearningEngine;

use App\Models\LearningEngine\LearningEvent;
use App\Models\LearningEngine\StudentDailyGoal;
use App\Models\LearningEngine\StudentSkillMastery;

/**
 * Phase 3, idea #31: student-chosen daily goals. A fixed, small catalogue
 * (not free text) so every goal is something completion can actually be
 * checked against — pulled straight from today's event log, same as
 * everything else in the learning engine. No AI, nothing to grade by hand.
 */
class DailyGoalRepository
{
    public const CATALOGUE = [
        'xp_20'        => ['label' => 'Earn 20 XP today', 'icon' => 'bolt'],
        'practice_5'   => ['label' => 'Answer 5 questions today', 'icon' => 'list-check'],
        'master_skill' => ['label' => 'Master one skill today', 'icon' => 'star'],
        'fix_mistake'  => ['label' => 'Fix an old mistake today', 'icon' => 'arrow-rotate-left'],
    ];

    public const MAX_GOALS_PER_DAY = 3;

    /** Today's chosen goals with live completion status — completing one
     *  outside the app (e.g. grading an exam) is picked up automatically
     *  the next time this loads, no separate "mark done" click needed. */
    public function forStudent(int $studentId): array
    {
        $today  = now()->toDateString();
        $chosen = StudentDailyGoal::where('student_id', $studentId)->where('goal_date', $today)->get();

        return $chosen->map(function ($goal) use ($studentId) {
            if (!$goal->completed_at && $this->isComplete($studentId, $goal->goal_key)) {
                $goal->completed_at = now();
                $goal->save();
            }

            $meta = self::CATALOGUE[$goal->goal_key] ?? ['label' => $goal->goal_key, 'icon' => 'flag'];

            return [
                'key'       => $goal->goal_key,
                'label'     => $meta['label'],
                'icon'      => $meta['icon'],
                'completed' => (bool) $goal->completed_at,
            ];
        })->values()->all();
    }

    /** Diffs against today's existing selection rather than deleting and
     *  recreating everything — a goal that stays selected keeps its
     *  completed_at, so toggling one goal can never silently un-complete
     *  another. */
    public function setGoals(int $studentId, array $goalKeys): void
    {
        $today = now()->toDateString();
        $keys  = array_slice(array_values(array_unique(array_intersect($goalKeys, array_keys(self::CATALOGUE)))), 0, self::MAX_GOALS_PER_DAY);

        $existing = StudentDailyGoal::where('student_id', $studentId)->where('goal_date', $today)->get()->keyBy('goal_key');

        foreach ($existing as $key => $row) {
            if (!in_array($key, $keys, true)) {
                $row->delete();
            }
        }

        foreach ($keys as $key) {
            if (!$existing->has($key)) {
                StudentDailyGoal::create(['student_id' => $studentId, 'goal_date' => $today, 'goal_key' => $key]);
            }
        }
    }

    private function isComplete(int $studentId, string $key): bool
    {
        $todayStart = now()->startOfDay();

        return match ($key) {
            'xp_20' => LearningEvent::where('student_id', $studentId)
                ->where('created_at', '>=', $todayStart)
                ->sum('xp') >= 20,

            'practice_5' => LearningEvent::where('student_id', $studentId)
                ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
                ->where('created_at', '>=', $todayStart)
                ->count() >= 5,

            'master_skill' => StudentSkillMastery::where('student_id', $studentId)
                ->where('mastered_at', '>=', $todayStart)
                ->exists(),

            'fix_mistake' => LearningEvent::where('student_id', $studentId)
                ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
                ->where('created_at', '>=', $todayStart)
                ->where('payload->was_recovery', true)
                ->exists(),

            default => false,
        };
    }
}
