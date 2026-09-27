<?php

namespace App\Repositories\LearningEngine;

use App\Models\LearningEngine\Mission;
use App\Models\LearningEngine\StudentAvatarProfile;
use App\Models\Homework;
use App\Models\OnlineExamination\OnlineExam;
use App\Models\OnlineExamination\Answer;
use App\Models\MarkSheetApproval;
use Illuminate\Support\Facades\DB;

/**
 * Turns a mission's linked real assignment (a Homework or an Online Exam)
 * into story progress — how many of its clues are revealed, and whether
 * it's finished — without ever storing per-student mission state of its
 * own. The mark a student already has on that assignment IS the mission's
 * progress; this only ever reads, so it can never drift from the real
 * gradebook.
 *
 * Homework and Online Exam keep their scores in different tables (that's
 * how the rest of the app already stores them — see markedWork() in
 * LearningHomeRepository for homework, and OnlineExamRepository::answer()
 * for exams), so this is the one place that reads both the same way.
 */
class StudentMissionRepository
{
    /** Same "good enough to move on" bar as the product plan's suggested
     *  default for unlocking a building — a mission (and the building it
     *  unlocks) is complete once the linked assignment is scored at or
     *  above this percentage. */
    public const MASTERY_THRESHOLD = 70;

    /** Null means "not attempted yet" (0%), not "not found" — a mission
     *  with nothing linked yet also reads as null, shown as "coming soon"
     *  rather than a real 0%. */
    public function progressPercent(int $studentId, Mission $mission): ?int
    {
        if (!$mission->linkable_type || !$mission->linkable_id) {
            return null;
        }

        if ($mission->linkable_type === 'homework') {
            $homework = Homework::find($mission->linkable_id);
            if (!$homework) {
                return null;
            }

            $earned = DB::table('homework_students')
                ->where('homework_id', $homework->id)
                ->where('student_id', $studentId)
                ->whereNotNull('marks')
                ->value('marks');

            if ($earned === null) {
                return 0;
            }

            return $this->pct((float) $earned, (float) $homework->marks);
        }

        if ($mission->linkable_type === 'online_exam') {
            $exam = OnlineExam::find($mission->linkable_id);
            if (!$exam) {
                return null;
            }

            $result = Answer::where('online_exam_id', $exam->id)
                ->where('student_id', $studentId)
                ->value('result');

            if ($result === null) {
                return 0;
            }

            return $this->pct((float) $result, (float) $exam->total_mark);
        }

        return null;
    }

    public function cluesRevealed(int $studentId, Mission $mission): int
    {
        $percent = $this->progressPercent($studentId, $mission);
        $total   = $mission->clueCount();

        if ($percent === null || $total === 0) {
            return 0;
        }

        return min($total, (int) ceil(($percent / 100) * $total));
    }

    public function isComplete(int $studentId, Mission $mission): bool
    {
        return ($this->progressPercent($studentId, $mission) ?? 0) >= self::MASTERY_THRESHOLD;
    }

    /** Whether ANY mission tied to this Building is complete for this
     *  student — that's what unlocks it. A Building with no mission
     *  pointed at it yet is simply never locked (nothing to gate on). */
    public function buildingUnlocked(int $studentId, int $buildingId): bool
    {
        $missions = Mission::active()->where('building_id', $buildingId)->get();
        if ($missions->isEmpty()) {
            return true;
        }

        return $missions->contains(fn ($m) => $this->isComplete($studentId, $m));
    }

    /** Everything the Room view needs for one Building, for a student's
     *  current theme — the mission matching both, its clue reveal state,
     *  and where to go do the real work. Null when no mission has been
     *  written yet for this building+theme combination (a normal state
     *  while content is still being added, not an error). */
    public function roomData(int $studentId, int $buildingId, ?string $theme): ?array
    {
        $mission = Mission::active()
            ->with('hub')
            ->where('building_id', $buildingId)
            ->when($theme, fn ($q) => $q->where('theme', $theme))
            ->orderBy('sort_order')
            ->first();

        if (!$mission) {
            return null;
        }

        $percent  = $this->progressPercent($studentId, $mission);
        $revealed = $this->cluesRevealed($studentId, $mission);
        $complete = $this->isComplete($studentId, $mission);

        return [
            'mission'   => $mission,
            'percent'   => $percent,
            'revealed'  => $revealed,
            'complete'  => $complete,
            'activity'  => $this->activityRoute($mission),
        ];
    }

    /** Where the "go do this" button on a mission sends the student —
     *  reuses the real Homework/Online Exam pages, never a copy of them. */
    public function activityRoute(Mission $mission): ?array
    {
        if ($mission->linkable_type === 'homework' && $mission->linkable_id) {
            return ['label' => 'Go to homework', 'url' => route('student-panel-homeworks.index')];
        }

        if ($mission->linkable_type === 'online_exam' && $mission->linkable_id) {
            return ['label' => 'Go to the exam', 'url' => route('student-panel-online-examination.index')];
        }

        return null;
    }

    private function pct(float $earned, float $total): int
    {
        return $total > 0 ? min(100, (int) round(($earned / $total) * 100)) : 0;
    }

    /** The term ends once every mission written for the student's current
     *  theme is finished — the one signal fully under this system's own
     *  control, so it can never fire twice for the same result. (Real
     *  Examination approval is surfaced separately as its own achievement,
     *  via approvedExamResults() below — it doesn't drive this, because an
     *  approval has no "which term was this for" marker to check against,
     *  and using it here would re-advance the term every time the island
     *  loads for as long as that approval exists.) Advancing clears the
     *  theme, so the next visit offers the picker again for the new term.
     *  Returns whether it just advanced, so the caller can show a
     *  celebration instead of this happening silently. */
    public function maybeAdvanceTerm(int $studentId, StudentAvatarProfile $profile): bool
    {
        if (!$profile->theme || $profile->current_term >= 6) {
            return false;
        }

        $missions = Mission::active()->where('theme', $profile->theme)->get();
        if ($missions->isEmpty() || !$missions->every(fn ($m) => $this->isComplete($studentId, $m))) {
            return false;
        }

        $profile->current_term = $profile->current_term + 1;
        $profile->theme        = null;
        $profile->save();

        return true;
    }

    /** Real Examination results, approved by the school, for this student
     *  — shown as an achievement on the island. Reads the Examination
     *  module; never writes to it, and never drives term-advancement (see
     *  the note on maybeAdvanceTerm() above). */
    public function approvedExamResults(int $studentId)
    {
        return MarkSheetApproval::with('exam_type')
            ->where('student_id', $studentId)
            ->where('status', 'approved')
            ->get();
    }
}
