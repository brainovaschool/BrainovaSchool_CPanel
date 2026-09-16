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
 *
 * Query-budget note: every method here fetches each student's/class's
 * mastery rows ONCE and derives counts/needs-review/milestone from that one
 * collection in memory, instead of issuing a separate query per figure (or,
 * in classSnapshot's case, per student) — that pattern was a real N+1 and a
 * measurable contributor to slow dashboard loads.
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

        // One fetch for all of this student's mastery rows — counts,
        // needs-review, "what's next", and the milestone check are all
        // derived from this single collection below.
        $masteries = StudentSkillMastery::where('student_id', $student->id)
            ->with('skill.subject')
            ->get();

        $totalSkills = Skill::active()->when($classesId, fn ($q) => $q->where('classes_id', $classesId))->count();
        $notStarted  = max(0, $totalSkills - $masteries->count());

        $needsReview = $masteries
            ->filter(fn ($m) => in_array($m->mastery_level, ['not_started', 'developing'], true) && $m->correct_count < $m->attempts_count)
            ->sortByDesc('last_practiced_at')
            ->take(3)
            ->pluck('skill')
            ->filter();

        $masteredIds = $masteries->where('mastery_level', 'advanced')->pluck('skill_id');
        $nextSkills  = Skill::active()
            ->with('subject')
            ->when($classesId, fn ($q) => $q->where('classes_id', $classesId))
            ->whereNotIn('id', $masteredIds)
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        return [
            'greeting_character' => $character,
            'greeting_name'      => Character::name($character),
            'greeting_image'     => $this->mascotUrl($character),
            'greeting_line'      => Character::line($character, $context),
            'is_comeback'        => $isComeback,
            'next_skills'        => $nextSkills,
            'needs_review'       => $needsReview,
            'review_line'        => Character::line('brainbot', 'mistake_review'),
            'milestone'          => $this->claimMilestone($masteries),
            'mastery_counts'     => [
                'not_started' => $notStarted,
                'developing'  => $masteries->where('mastery_level', 'developing')->count(),
                'proficient'  => $masteries->where('mastery_level', 'proficient')->count(),
                'advanced'    => $masteries->where('mastery_level', 'advanced')->count(),
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

        $studentIds  = $students->pluck('id');
        $totalSkills = Skill::active()->where('classes_id', $classesId)->count();

        // One query for every student's mastery counts, one for needs-review
        // — not two queries per student, which was a real N+1 for a full class.
        $countsByStudent = StudentSkillMastery::whereIn('student_id', $studentIds)
            ->selectRaw('student_id, mastery_level, count(*) as c')
            ->groupBy('student_id', 'mastery_level')
            ->get()
            ->groupBy('student_id');

        $needsReviewByStudent = StudentSkillMastery::whereIn('student_id', $studentIds)
            ->whereIn('mastery_level', ['not_started', 'developing'])
            ->whereColumn('correct_count', '<', 'attempts_count')
            ->selectRaw('student_id, count(*) as c')
            ->groupBy('student_id')
            ->pluck('c', 'student_id');

        $rows = $students->map(function ($student) use ($totalSkills, $countsByStudent, $needsReviewByStudent) {
            $counts  = optional($countsByStudent->get($student->id))->pluck('c', 'mastery_level') ?? collect();
            $touched = (int) $counts->sum();

            return [
                'student'      => $student,
                'not_started'  => max(0, $totalSkills - $touched),
                'developing'   => (int) ($counts['developing'] ?? 0),
                'proficient'   => (int) ($counts['proficient'] ?? 0),
                'advanced'     => (int) ($counts['advanced'] ?? 0),
                'needs_review' => (int) ($needsReviewByStudent[$student->id] ?? 0),
            ];
        });

        return ['total_skills' => $totalSkills, 'rows' => $rows];
    }

    /**
     * The "notification" piece of Phase 1: a one-time celebration the moment a
     * skill first reaches Advanced. Shown exactly once (marked seen here, on
     * the same request that returns it) — a real event, not a manufactured
     * streak, and never repeated into a nag. Takes the already-loaded
     * mastery collection instead of querying again.
     */
    private function claimMilestone($masteries): ?array
    {
        $mastery = $masteries->first(fn ($m) => $m->mastered_at && !$m->milestone_seen_at);

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
