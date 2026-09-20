<?php

namespace App\Repositories\LearningEngine;

use App\Support\Character;
use App\Models\Homework;
use App\Models\HomeworkStudent;
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

    public function forStudent(Student $student, ?int $subjectId = null): array
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
            ->when($subjectId, fn ($q) => $q->whereHas('skill', fn ($sq) => $sq->where('subject_id', $subjectId)))
            ->get();

        $totalSkills = Skill::active()
            ->when($classesId, fn ($q) => $q->where('classes_id', $classesId))
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->count();
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
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
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

        // Points, all-time and unscoped by subject — this is what Level is
        // built from, alongside skill-practice XP, so it has to see every
        // subject regardless of which one the student has filtered to.
        $allMarkedWork  = $this->markedWork($student->id, null);
        $homeworkPoints = (int) collect($allMarkedWork)->sum('points');
        $scopedMarked   = $subjectId ? $this->markedWork($student->id, $subjectId) : $allMarkedWork;
        $averageMarks   = count($scopedMarked) ? (int) round(collect($scopedMarked)->avg('percent')) : null;

        $brainLevel  = $this->brainLevel($this->events->totalXp($student->id) + $homeworkPoints);
        $tree        = $this->knowledgeTree($student->id);
        $personalBest = $this->personalBest($student->id);

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
            'level_trend'        => $this->levelTrend($student->id),
            'knowledge_tree'     => $tree,
            'badges'             => $this->badges($masteries),
            'medals'             => $this->medals($tree['stage_index']),
            'awards'             => $this->awards($allMarkedWork, $masteries, $personalBest, $needsReviewDisplay),
            'personal_best'      => $personalBest,
            'verified_skills'    => $this->verifiedSkills($masteries),
            'inactivity_nudge'   => $this->inactivityNudge($masteries),
            'marked_work'        => $scopedMarked,
            'average_marks'      => $averageMarks,
            'skills_total'       => $totalSkills,
            'mastery_counts'     => [
                'not_started' => $notStarted,
                'developing'  => $masteries->where('mastery_level', 'developing')->count(),
                'proficient'  => $masteries->where('mastery_level', 'proficient')->count(),
                'advanced'    => $masteries->where('mastery_level', 'advanced')->count(),
            ],
        ];
    }

    /**
     * Phase 3, idea #14: "Brain Level," not Grade Level — a number built from
     * points, never from age or grade. Points come from two places: XP earned
     * through mastery-weighted practice (LearningEventRepository's formula)
     * and marks-based points from homework/exams — every marked task's
     * percentage is added as that many points (5 out of 30 = 17% = 17
     * points), so the two feel like one honest number instead of two things
     * to keep straight. A simple, fully transparent curve: level N starts at
     * 25*(N-1)^2 points, so each level takes a bit more than the last — no
     * AI, no black box, easy to explain to a parent or a student. Points only
     * ever accumulate, so Level can never go down.
     */
    private function brainLevel(int $totalPoints): array
    {
        $level            = (int) floor(sqrt($totalPoints / 25)) + 1;
        $pointsAtStart    = 25 * ($level - 1) ** 2;
        $pointsAtNext     = 25 * $level ** 2;
        $pointsIntoLevel  = $totalPoints - $pointsAtStart;
        $pointsForLevel   = max(1, $pointsAtNext - $pointsAtStart);

        return [
            'level'         => $level,
            'total_points'  => $totalPoints,
            'xp_into_level' => $pointsIntoLevel,
            'xp_for_level'  => $pointsForLevel,
            'progress_pct'  => min(100, (int) round($pointsIntoLevel / $pointsForLevel * 100)),
            'next_level_at' => $pointsAtNext,
        ];
    }

    /**
     * This week's points earned vs last week's — the same "are you
     * improving" comparison Personal Best already does, applied to Level
     * instead of accuracy. Returns null rather than a fabricated 0 when
     * there's been no activity in either week to compare.
     */
    private function levelTrend(int $studentId): ?array
    {
        $thisWeek = $this->pointsBetween($studentId, now()->startOfWeek(), now());
        $lastWeek = $this->pointsBetween($studentId, now()->subWeek()->startOfWeek(), now()->startOfWeek());

        if ($thisWeek === 0 && $lastWeek === 0) {
            return null;
        }

        return ['this_week' => $thisWeek, 'last_week' => $lastWeek, 'delta' => $thisWeek - $lastWeek, 'improving' => $thisWeek >= $lastWeek];
    }

    private function pointsBetween(int $studentId, $start, $end): int
    {
        $xp = (int) LearningEvent::where('student_id', $studentId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('xp');

        $hwTable = (new Homework)->getTable();
        $rows = HomeworkStudent::query()
            ->join($hwTable, $hwTable . '.id', '=', 'homework_students.homework_id')
            ->where('homework_students.student_id', $studentId)
            ->whereNotNull('homework_students.marks')
            ->whereBetween('homework_students.updated_at', [$start, $end])
            ->select([$hwTable . '.marks as total_marks', 'homework_students.marks as earned_marks'])
            ->get();

        $hwPoints = 0;
        foreach ($rows as $row) {
            $hwPoints += $this->pctPoints($row->earned_marks, $row->total_marks);
        }

        return $xp + $hwPoints;
    }

    /** Every marked homework task turned into a percentage and points — the
     *  single source both Level (unscoped) and the "Marked work" list
     *  (optionally scoped to one subject) are built from. */
    private function markedWork(int $studentId, ?int $subjectId): array
    {
        $hwTable = (new Homework)->getTable();

        $rows = HomeworkStudent::query()
            ->join($hwTable, $hwTable . '.id', '=', 'homework_students.homework_id')
            ->join('subjects', 'subjects.id', '=', $hwTable . '.subject_id')
            ->where('homework_students.student_id', $studentId)
            ->whereNotNull('homework_students.marks')
            ->where($hwTable . '.session_id', setting('session'))
            ->when($subjectId, fn ($q) => $q->where($hwTable . '.subject_id', $subjectId))
            ->orderByDesc('homework_students.updated_at')
            ->select([
                $hwTable . '.date',
                $hwTable . '.marks as total_marks',
                'subjects.name as subject_name',
                'homework_students.marks as earned_marks',
                'homework_students.updated_at as marked_at',
            ])
            ->get();

        return $rows->map(function ($row) {
            $percent = $this->pctPoints($row->earned_marks, $row->total_marks);

            return [
                'title'     => $row->subject_name . ' — ' . \Carbon\Carbon::parse($row->date)->format('d M'),
                'subject'   => $row->subject_name,
                'earned'    => (float) $row->earned_marks,
                'total'     => (float) $row->total_marks,
                'percent'   => $percent,
                'points'    => $percent,
                'marked_at' => $row->marked_at,
            ];
        })->values()->all();
    }

    private function pctPoints($earned, $total): int
    {
        $total = (float) $total;

        return $total > 0 ? min(100, (int) round(((float) $earned / $total) * 100)) : 0;
    }

    /**
     * Phase 3: the Knowledge Tree — replaces the streak. Grows purely with
     * how often the dashboard is opened (see
     * LearningEventRepository::recordVisitIfNeeded()), completely separate
     * from marks, points or Level — a student who visits daily grows a full
     * tree even on a rough week. Every 10 distinct days visited, the plant
     * grows one stage taller; leaves fill back in toward the next stage
     * after that. Built on a plain COUNT(DISTINCT date), which can only ever
     * hold steady or grow — so a quiet stretch pauses growth without ever
     * shrinking the tree back.
     */
    private function knowledgeTree(int $studentId): array
    {
        $stages = ['Seed', 'Sprout', 'Sapling', 'Young Tree', 'Full Bloom'];
        $band   = 10;

        $activeDays = (int) (LearningEvent::where('student_id', $studentId)
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as c')
            ->value('c') ?? 0);

        $stageIndex = min(intdiv($activeDays, $band), count($stages) - 1);
        $atCap      = $stageIndex === count($stages) - 1;
        $daysInBand = $atCap ? ($band - 1) : ($activeDays % $band);

        return [
            'label'        => $stages[$stageIndex],
            'stage_index'  => $stageIndex,
            'active_days'  => $activeDays,
            'days_in_band' => $daysInBand,
            'days_to_grow' => $atCap ? null : ($band - $daysInBand),
            'at_cap'       => $atCap,
        ];
    }

    /** Reaching each Knowledge Tree stage earns a medal — recognising
     *  consistency (showing up) as its own kind of achievement, separate
     *  from Badges (mastery) and Awards (one-off milestones). */
    private function medals(int $stageIndex): array
    {
        $tiers = ['Sprout', 'Sapling', 'Young Tree', 'Full Bloom'];

        return collect($tiers)
            ->map(fn ($name, $i) => ['name' => $name, 'earned' => $stageIndex >= ($i + 1)])
            ->values()
            ->all();
    }

    /** Small, evidence-based one-off milestones — same "real evidence, not
     *  a participation trophy" rule as Badges, just for moments rather than
     *  ongoing mastery. */
    private function awards(array $allMarkedWork, $masteries, ?array $personalBest, $needsReviewDisplay): array
    {
        return [
            ['icon' => '🌟', 'name' => 'First Skill Mastered', 'earned' => $masteries->where('mastery_level', 'advanced')->isNotEmpty()],
            ['icon' => '🎯', 'name' => 'Perfect Score',        'earned' => collect($allMarkedWork)->contains(fn ($r) => $r['percent'] >= 100)],
            ['icon' => '📈', 'name' => 'On The Rise',          'earned' => $personalBest && $personalBest['delta'] > 0],
            ['icon' => '🧹', 'name' => 'All Caught Up',        'earned' => $needsReviewDisplay->isEmpty()],
        ];
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
