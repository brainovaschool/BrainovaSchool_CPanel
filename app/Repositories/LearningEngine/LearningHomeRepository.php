<?php

namespace App\Repositories\LearningEngine;

use App\Support\Character;
use App\Models\LearningEngine\Skill;
use App\Models\StudentInfo\Student;
use App\Models\LearningEngine\LearningEvent;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\LearningEngine\StudentSkillMastery;

/**
 * Builds the "Personal Learning Home" panel shown at the top of the student
 * dashboard: a character greeting (welcome-back aware), a short "what's
 * next" list, and a skill-mastery snapshot. Entirely rule-based — no AI
 * call here — so the dashboard always has something real to show even
 * before any AI-assisted feature is layered on top of it.
 */
class LearningHomeRepository
{
    private const COMEBACK_GAP_DAYS = 3;

    private $events;

    public function __construct(LearningEventRepository $events)
    {
        $this->events = $events;
    }

    public function forStudent(Student $student): array
    {
        $classesId = SessionClassStudent::where('session_id', setting('session'))
            ->where('student_id', $student->id)
            ->value('classes_id');

        $lastEvent = LearningEvent::where('student_id', $student->id)->latest('created_at')->first();

        $isFirstVisit = !$lastEvent;
        $isComeback   = $lastEvent && $lastEvent->created_at->diffInDays(now()) >= self::COMEBACK_GAP_DAYS;

        if ($isFirstVisit) {
            $character = 'kea';
            $context   = 'welcome';
        } elseif ($isComeback) {
            $character = 'kea';
            $context   = 'comeback';
        } else {
            $character = 'brainbot';
            $context   = 'welcome';
        }

        $counts = StudentSkillMastery::where('student_id', $student->id)
            ->selectRaw('mastery_level, count(*) as c')
            ->groupBy('mastery_level')
            ->pluck('c', 'mastery_level');

        // A skill nobody has attempted yet has no mastery row at all (rows are
        // only created on a first attempt), so "not started" has to be derived
        // from the skill catalogue, not just counted from existing rows.
        $totalSkills   = Skill::active()->when($classesId, fn ($q) => $q->where('classes_id', $classesId))->count();
        $touchedSkills = StudentSkillMastery::where('student_id', $student->id)->count();
        $notStarted    = max(0, $totalSkills - $touchedSkills);

        $needsReview = StudentSkillMastery::where('student_id', $student->id)
            ->whereIn('mastery_level', ['not_started', 'developing'])
            ->whereColumn('correct_count', '<', 'attempts_count')
            ->with('skill.subject')
            ->orderByDesc('last_practiced_at')
            ->take(3)
            ->get()
            ->pluck('skill')
            ->filter();

        return [
            'greeting_character' => $character,
            'greeting_name'      => Character::name($character),
            'greeting_image'     => $this->mascotUrl($character),
            'greeting_line'      => Character::line($character, $context),
            'is_comeback'        => $isComeback,
            'next_skills'        => $this->events->nextSkills($student->id, null, $classesId, 3),
            'needs_review'       => $needsReview,
            'review_line'        => Character::line('brainbot', 'mistake_review'),
            'milestone'          => $this->claimMilestone($student->id),
            'mastery_counts'     => [
                'not_started' => $notStarted,
                'developing'  => (int) ($counts['developing'] ?? 0),
                'proficient'  => (int) ($counts['proficient'] ?? 0),
                'advanced'    => (int) ($counts['advanced'] ?? 0),
            ],
        ];
    }

    /**
     * Per-student mastery breakdown for a whole class — the teacher-facing
     * counterpart to forStudent(). Growth and mastery per student, not a
     * ranking: rows are returned in enrollment order, never sorted by score.
     */
    public function classSnapshot(int $classesId, ?int $sectionId = null): array
    {
        $students = SessionClassStudent::where('session_id', setting('session'))
            ->where('classes_id', $classesId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter()
            ->values();

        $totalSkills = Skill::active()->where('classes_id', $classesId)->count();

        $rows = $students->map(function ($student) use ($totalSkills) {
            $counts = StudentSkillMastery::where('student_id', $student->id)
                ->selectRaw('mastery_level, count(*) as c')
                ->groupBy('mastery_level')
                ->pluck('c', 'mastery_level');

            $touched          = (int) $counts->sum();
            $needsReviewCount = StudentSkillMastery::where('student_id', $student->id)
                ->whereIn('mastery_level', ['not_started', 'developing'])
                ->whereColumn('correct_count', '<', 'attempts_count')
                ->count();

            return [
                'student'      => $student,
                'not_started'  => max(0, $totalSkills - $touched),
                'developing'   => (int) ($counts['developing'] ?? 0),
                'proficient'   => (int) ($counts['proficient'] ?? 0),
                'advanced'     => (int) ($counts['advanced'] ?? 0),
                'needs_review' => $needsReviewCount,
            ];
        });

        return ['total_skills' => $totalSkills, 'rows' => $rows];
    }

    /**
     * The "notification" piece of Phase 1: a one-time celebration the moment a
     * skill first reaches Advanced. Shown exactly once (marked seen here, on
     * the same request that returns it) — a real event, not a manufactured
     * streak, and never repeated into a nag.
     */
    private function claimMilestone(int $studentId): ?array
    {
        $mastery = StudentSkillMastery::where('student_id', $studentId)
            ->whereNotNull('mastered_at')
            ->whereNull('milestone_seen_at')
            ->with('skill')
            ->orderBy('mastered_at')
            ->first();

        if (!$mastery || !$mastery->skill) {
            return null;
        }

        $mastery->milestone_seen_at = now();
        $mastery->save();

        return [
            'skill_title' => $mastery->skill->title,
            'line'        => Character::line('kea', 'milestone'),
        ];
    }

    /** Reuses the mascot artwork already uploaded in Website Setup -> AI Helper,
     *  instead of depending on static image files that were never actually
     *  added to public/frontend/img/mascots/. Returns null if not uploaded. */
    private function mascotUrl(string $character): ?string
    {
        $settingKey = $character === 'brainbot' ? 'ai_helper_teacher_mascot' : 'ai_helper_student_mascot';
        $path       = setting($settingKey);

        return $path ? globalAsset($path) : null;
    }
}
