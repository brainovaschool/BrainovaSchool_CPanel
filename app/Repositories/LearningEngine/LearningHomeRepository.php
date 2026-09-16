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
    private const COMEBACK_GAP_DAYS    = 3;
    private const INACTIVITY_GAP_DAYS  = 5;

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

        // take(4): one becomes the featured "Next Best Action" below and is
        // removed from this list afterward, leaving 3 still visible here —
        // not 2.
        $needsReview = $masteries
            ->filter(fn ($m) => in_array($m->mastery_level, ['not_started', 'developing'], true) && $m->correct_count < $m->attempts_count)
            ->sortByDesc('last_practiced_at')
            ->take(4)
            ->pluck('skill')
            ->filter();

        $masteredIds = $masteries->where('mastery_level', 'advanced')->pluck('skill_id');
        $nextSkills  = Skill::active()
            ->with('subject')
            ->when($classesId, fn ($q) => $q->where('classes_id', $classesId))
            ->whereNotIn('id', $masteredIds)
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        $nextAction = $this->nextBestAction($student->id, $masteries, $needsReview, $nextSkills);

        // Don't show the same skill twice — once as THE featured next
        // action, and again in the supporting lists right below it.
        if ($nextAction['skill']) {
            $needsReview = $needsReview->reject(fn ($s) => $s->id === $nextAction['skill']->id)->values();
            $nextSkills  = $nextSkills->reject(fn ($s) => $s->id === $nextAction['skill']->id)->values();
        }
        $nextSkills = $nextSkills->take(3)->values();

        // Mistake Bank (idea #4): each remaining "needs review" skill shows
        // its recovery progress — computed straight from the event log, no
        // new table needed. "Needs practice" -> "First recovery" -> "Second
        // recovery", read off how many corrects in a row since the last miss.
        $needsReviewDisplay = $needsReview->map(fn ($skill) => [
            'skill' => $skill,
            'stage' => $this->recoveryStage($student->id, $skill->id),
        ]);

        // Spaced Review Engine (idea #13) + "I forgot this" (idea #32): a
        // mastered skill that's gone stale — computed from next_review_at,
        // which updateMastery() schedules and backs off automatically.
        // Genuinely mastered skills stay mastered; this just flags upkeep.
        $dueForReview = $masteries
            ->filter(fn ($m) => $m->mastery_level === 'advanced' && $m->next_review_at && $m->next_review_at->isPast())
            ->sortBy('next_review_at')
            ->take(2)
            ->pluck('skill')
            ->filter();

        $brainLevel = $this->brainLevel($this->events->totalXp($student->id));

        return [
            'greeting_character' => $character,
            'greeting_name'      => Character::name($character),
            'greeting_image'     => $this->mascotUrl($character),
            'greeting_line'      => Character::line($character, $context),
            'is_comeback'        => $isComeback,
            'next_action'        => $nextAction,
            'next_skills'        => $nextSkills,
            'needs_review'       => $needsReviewDisplay,
            'review_line'        => Character::line('brainbot', 'mistake_review'),
            'due_for_review'     => $dueForReview,
            'refresher_line'     => Character::line('kea', 'refresher'),
            'milestone'          => $this->claimMilestone($masteries),
            'brain_level'        => $brainLevel,
            'knowledge_tree'     => $this->knowledgeTree($student->id, $brainLevel['level']),
            'badges'             => $this->badges($masteries),
            'personal_best'      => $this->personalBest($student->id),
            'verified_skills'    => $this->verifiedSkills($masteries),
            'inactivity_nudge'   => $this->inactivityNudge($masteries),
            'mastery_counts'     => [
                'not_started' => $notStarted,
                'developing'  => $masteries->where('mastery_level', 'developing')->count(),
                'proficient'  => $masteries->where('mastery_level', 'proficient')->count(),
                'advanced'    => $masteries->where('mastery_level', 'advanced')->count(),
            ],
        ];
    }

    /**
     * Phase 3, idea #14: "Brain Level," not Grade Level — a number built
     * entirely from XP earned through mastery-weighted behavior (see
     * LearningEventRepository's XP formula), never from age or grade. A
     * simple, fully transparent curve: level N starts at 25*(N-1)^2 XP, so
     * each level takes a bit more than the last — no AI, no black box, easy
     * to explain to a parent or a student.
     */
    private function brainLevel(int $totalXp): array
    {
        $level          = (int) floor(sqrt($totalXp / 25)) + 1;
        $xpAtLevelStart = 25 * ($level - 1) ** 2;
        $xpAtNextLevel  = 25 * $level ** 2;
        $xpIntoLevel    = $totalXp - $xpAtLevelStart;
        $xpForLevel     = max(1, $xpAtNextLevel - $xpAtLevelStart);

        return [
            'level'         => $level,
            'total_xp'      => $totalXp,
            'xp_into_level' => $xpIntoLevel,
            'xp_for_level'  => $xpForLevel,
            'progress_pct'  => min(100, (int) round($xpIntoLevel / $xpForLevel * 100)),
            'next_level_at' => $xpAtNextLevel,
        ];
    }

    /**
     * Phase 3: the Knowledge Tree — replaces the streak. Grows with points
     * (via Brain Level, which is itself XP-driven) and with days actually
     * spent learning. The critical difference from a streak: it has no way
     * to go backward. A quiet week just means growth pauses; it can never
     * "break," so there's nothing here to lose sleep over.
     */
    private function knowledgeTree(int $studentId, int $brainLevel): array
    {
        $stage = match (true) {
            $brainLevel >= 17 => ['emoji' => '🌳', 'label' => 'Ancient Tree'],
            $brainLevel >= 12 => ['emoji' => '🌳', 'label' => 'Flourishing Tree'],
            $brainLevel >= 8  => ['emoji' => '🌲', 'label' => 'Young Tree'],
            $brainLevel >= 5  => ['emoji' => '🌳', 'label' => 'Sapling'],
            $brainLevel >= 3  => ['emoji' => '🌿', 'label' => 'Sprout'],
            default           => ['emoji' => '🌱', 'label' => 'Seed'],
        };

        $activeDays = (int) (LearningEvent::where('student_id', $studentId)
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as c')
            ->value('c') ?? 0);

        return array_merge($stage, ['active_days' => $activeDays]);
    }

    /**
     * Phase 3, idea #15: badges tied to real evidence — skills actually
     * mastered in a subject — not arbitrary unlocks. One badge type per
     * subject with three honest tiers, rather than 200 meaningless ones.
     */
    private function badges($masteries): array
    {
        return $masteries
            ->where('mastery_level', 'advanced')
            ->groupBy(fn ($m) => optional($m->skill->subject ?? null)->name ?? 'General')
            ->map(function ($group, $subjectName) {
                $count = $group->count();
                $tier  = match (true) {
                    $count >= 10 => 'gold',
                    $count >= 6  => 'silver',
                    $count >= 3  => 'bronze',
                    default      => null,
                };

                return $tier ? ['subject' => $subjectName, 'tier' => $tier, 'count' => $count] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Phase 3, idea #11: Personal Best — this week's accuracy vs last week's,
     * the default comparison instead of a leaderboard. Returns null (not a
     * fabricated 0%) when there isn't a full week on each side to compare.
     */
    private function personalBest(int $studentId): ?array
    {
        $thisWeek = $this->accuracyBetween($studentId, now()->startOfWeek(), now());
        $lastWeek = $this->accuracyBetween($studentId, now()->subWeek()->startOfWeek(), now()->startOfWeek());

        if ($thisWeek === null || $lastWeek === null) {
            return null;
        }

        return ['this_week' => $thisWeek, 'last_week' => $lastWeek, 'delta' => $thisWeek - $lastWeek];
    }

    private function accuracyBetween(int $studentId, $start, $end): ?int
    {
        $total = LearningEvent::where('student_id', $studentId)
            ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($total === 0) {
            return null;
        }

        $correct = LearningEvent::where('student_id', $studentId)
            ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
            ->whereBetween('created_at', [$start, $end])
            ->where('payload->correct', true)
            ->count();

        return (int) round($correct / $total * 100);
    }

    /**
     * Phase 3, idea #33: "Verified Skill" instead of a generic course
     * certificate — named this way (not "certificate") to avoid colliding
     * with this app's existing, unrelated admin Certificate feature. Each
     * card is evidence — the skill, the subject, and the date it was
     * genuinely earned — not a participation trophy.
     */
    private function verifiedSkills($masteries): array
    {
        return $masteries
            ->where('mastery_level', 'advanced')
            ->whereNotNull('mastered_at')
            ->sortByDesc('mastered_at')
            ->take(6)
            ->map(fn ($m) => [
                'skill'      => $m->skill,
                'mastered_at' => $m->mastered_at,
                'accuracy'   => $m->attempts_count > 0 ? (int) round($m->correct_count / $m->attempts_count * 100) : 0,
            ])
            ->filter(fn ($row) => $row['skill'])
            ->values()
            ->all();
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

        // Silent Struggle Detector (idea #18), teacher-facing half: sustained
        // low accuracy (3+ attempts, under 40% correct) on a not-yet-advanced
        // skill — one query for the whole class, not one per student.
        $strugglingByStudent = StudentSkillMastery::whereIn('student_id', $studentIds)
            ->where('mastery_level', '!=', 'advanced')
            ->where('attempts_count', '>=', 3)
            ->whereRaw('correct_count / attempts_count < 0.4')
            ->selectRaw('student_id, count(*) as c')
            ->groupBy('student_id')
            ->pluck('c', 'student_id');

        $rows = $students->map(function ($student) use ($totalSkills, $countsByStudent, $needsReviewByStudent, $strugglingByStudent) {
            $counts  = optional($countsByStudent->get($student->id))->pluck('c', 'mastery_level') ?? collect();
            $touched = (int) $counts->sum();

            return [
                'student'      => $student,
                'not_started'  => max(0, $totalSkills - $touched),
                'developing'   => (int) ($counts['developing'] ?? 0),
                'proficient'   => (int) ($counts['proficient'] ?? 0),
                'advanced'     => (int) ($counts['advanced'] ?? 0),
                'needs_review' => (int) ($needsReviewByStudent[$student->id] ?? 0),
                'struggling'   => (int) ($strugglingByStudent[$student->id] ?? 0),
            ];
        });

        return ['total_skills' => $totalSkills, 'rows' => $rows];
    }

    /**
     * Phase 2, idea #3 + #34: one clear next action instead of a menu of 7
     * options, with a plain-language, honest reason attached — never a bare
     * recommendation with no explanation. Priority: a skill actively being
     * gotten wrong beats an unstarted one, which beats "you're caught up."
     * Entirely rule-based, reusing data already computed above.
     */
    private function nextBestAction(int $studentId, $masteries, $needsReview, $nextSkills): array
    {
        $reviewTarget = $needsReview->first();
        if ($reviewTarget) {
            $mastery = $masteries->firstWhere('skill_id', $reviewTarget->id);

            // Silent Struggle Detector (idea #18): a single recent miss stays
            // Brainbot's methodical "let's investigate" tone, but sustained
            // low accuracy on a skill is a stronger signal — Kea's more
            // caring "I noticed this is tough" tone, and this same signal
            // also surfaces to the teacher (see classSnapshot()).
            if ($mastery && $this->isStruggling($mastery)) {
                return [
                    'skill'  => $reviewTarget,
                    'reason' => "I've noticed {$reviewTarget->title} has been tricky for a few tries now — want to work through it together?",
                    'type'   => 'struggle',
                ];
            }

            $stage  = $this->recoveryStage($studentId, $reviewTarget->id);
            $reason = match ($stage['level']) {
                'first'  => "You got {$reviewTarget->title} right last time — one more like that and it's recovered.",
                'second' => "You've gotten {$reviewTarget->title} right twice in a row now — one step from mastered.",
                default  => "You've gotten a few {$reviewTarget->title} questions wrong recently — let's take another look before moving on.",
            };

            return ['skill' => $reviewTarget, 'reason' => $reason, 'type' => 'review'];
        }

        $target = $nextSkills->first();
        if ($target) {
            $mastery = $masteries->firstWhere('skill_id', $target->id);
            $reason  = $mastery
                ? "You're partway through {$target->title} — a bit more practice and it's mastered."
                : "You haven't started {$target->title} yet — it's next up" . ($target->subject ? " in {$target->subject->name}" : '') . '.';

            return ['skill' => $target, 'reason' => $reason, 'type' => 'next'];
        }

        return [
            'skill'  => null,
            'reason' => "You've worked through everything set up for your grade right now — nice work. Ask your teacher what's next.",
            'type'   => 'caught_up',
        ];
    }

    /**
     * Phase 2, idea #4: the Mistake Bank recovery loop — Needs practice ->
     * First recovery -> Second recovery — read directly off the event log
     * (every graded answer is already there from Phase 0/1) instead of a new
     * table. Counts correct answers in a row for this skill since the last
     * wrong one; resets the moment another wrong answer comes in.
     */
    private function recoveryStage(int $studentId, int $skillId): array
    {
        $lastWrongAt = LearningEvent::where('student_id', $studentId)
            ->where('skill_id', $skillId)
            ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
            ->where('payload->correct', false)
            ->latest('created_at')
            ->value('created_at');

        $correctSinceWrong = $lastWrongAt
            ? LearningEvent::where('student_id', $studentId)
                ->where('skill_id', $skillId)
                ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
                ->where('payload->correct', true)
                ->where('created_at', '>', $lastWrongAt)
                ->count()
            : 0;

        return match (true) {
            $correctSinceWrong >= 2 => ['level' => 'second', 'label' => 'Second recovery'],
            $correctSinceWrong === 1 => ['level' => 'first', 'label' => 'First recovery'],
            default => ['level' => 'new', 'label' => 'Needs practice'],
        };
    }

    /**
     * Phase 2, idea #18: a repeated, low-accuracy pattern on a skill — not
     * just one miss — the difference between "everyone gets something wrong
     * sometimes" and "this student may actually need help here."
     */
    private function isStruggling(StudentSkillMastery $mastery): bool
    {
        if ($mastery->mastery_level === 'advanced' || $mastery->attempts_count < 3) {
            return false;
        }

        return ($mastery->correct_count / $mastery->attempts_count) < 0.4;
    }

    /**
     * Phase 4, idea #6: Kea as an observant companion — "You haven't
     * practiced X in N days," for one specific skill the student has
     * started but drifted away from. Distinct from the comeback greeting
     * above: comeback fires on overall inactivity; this fires even on an
     * active day, for a skill the student is otherwise quietly avoiding.
     * Entirely rule-based off last_practiced_at — no AI call, always
     * available even if an AI service is down.
     */
    private function inactivityNudge($masteries): ?array
    {
        $stale = $masteries
            ->filter(fn ($m) => $m->mastery_level !== 'advanced'
                && $m->last_practiced_at
                && $m->skill
                && $m->last_practiced_at->diffInDays(now()) >= self::INACTIVITY_GAP_DAYS)
            ->sortByDesc(fn ($m) => $m->last_practiced_at->diffInDays(now()))
            ->first();

        if (!$stale) {
            return null;
        }

        $days = (int) $stale->last_practiced_at->diffInDays(now());

        return [
            'skill' => $stale->skill,
            'days'  => $days,
            'line'  => "I noticed you haven't practiced {$stale->skill->title} in {$days} days — want to pick it back up?",
        ];
    }

    /**
     * Phase 2, idea #21: real wins from the last 7 days for the Parent
     * Snapshot — skills newly mastered and correct answers logged — instead
     * of only ever surfacing a problem. Returns has_wins = false rather than
     * fabricating a win when nothing real happened this week.
     */
    public function weeklyWins(Student $student): array
    {
        $since = now()->subDays(7);

        $masteredTitles = StudentSkillMastery::where('student_id', $student->id)
            ->where('mastered_at', '>=', $since)
            ->with('skill')
            ->get()
            ->pluck('skill.title')
            ->filter()
            ->values();

        $correctThisWeek = LearningEvent::where('student_id', $student->id)
            ->where('event_type', LearningEventRepository::EVENT_ANSWER_SUBMITTED)
            ->where('created_at', '>=', $since)
            ->where('payload->correct', true)
            ->count();

        return [
            'mastered_titles'   => $masteredTitles,
            'correct_this_week' => $correctThisWeek,
            'has_wins'          => $masteredTitles->count() > 0 || $correctThisWeek > 0,
        ];
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
