<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramClass;
use App\Models\WebsiteSetup\ProgramClassMark;
use App\Traits\ReturnFormatTrait;

/** Marks for a single lesson's gradable items (Quiz/Project/Worksheet/
 *  Assignment), one row per student per item - what the teacher-facing
 *  grading screen reads and writes, and what Progress & Report reads
 *  back out per program. */
class ProgramClassMarkRepository
{
    use ReturnFormatTrait;

    /** Every enrolled student x every gradable item on this lesson, with
     *  whatever mark already exists slotted in - exactly what the
     *  grading table needs to render. */
    public function gridFor(ProgramClass $class): array
    {
        $students = $class->program->students()->orderBy('first_name')->get();
        $items    = $class->gradableItems();

        $existing = ProgramClassMark::where('program_class_id', $class->id)->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->keyBy('assessment_type'));

        return [
            'students' => $students,
            'items'    => $items,
            'marks'    => $existing,
        ];
    }

    /** Upserts one ProgramClassMark per (student, gradable item) that was
     *  posted - a blank input clears that mark back to ungraded (null)
     *  rather than leaving a stale value behind. */
    public function save(ProgramClass $class, $request, ?int $gradedByStaffId): array
    {
        $items    = array_keys($class->gradableItems());
        $students = $class->program->students()->pluck('id');
        $input    = (array) $request->input('marks', []);

        foreach ($students as $studentId) {
            foreach ($items as $type) {
                $raw = $input[$studentId][$type] ?? null;
                $value = ($raw === null || $raw === '') ? null : (float) $raw;

                ProgramClassMark::updateOrCreate(
                    [
                        'program_class_id' => $class->id,
                        'assessment_type'  => $type,
                        'student_id'       => $studentId,
                    ],
                    [
                        'marks'     => $value,
                        'graded_by' => $gradedByStaffId,
                        'graded_at' => $value === null ? null : now(),
                    ]
                );
            }
        }

        return $this->responseWithSuccess('Marks saved.', []);
    }

    /** Everything Progress & Report shows for one program: a lesson-wise
     *  breakdown (how many of its gradable items are graded, and the
     *  class average for them) and a student-wise breakdown (each
     *  student's overall percentage across every graded item). Percentage
     *  figures only count items that have a max marks set - an item left
     *  without one still records raw marks but can't be turned into a
     *  percentage. */
    public function progressForProgram(Program $program): array
    {
        $students = $program->students()->orderBy('first_name')->get();
        $classes  = $program->programClasses()->with('marks')->orderBy('sort_order')->get();

        $studentTotals = $students->mapWithKeys(fn ($s) => [$s->id => ['obtained' => 0.0, 'possible' => 0.0, 'graded' => 0]]);

        $lessonRows  = [];
        $totalGraded = 0;
        $totalSlots  = 0;

        foreach ($classes as $class) {
            $items = $class->gradableItems();
            if (empty($items)) {
                continue;
            }

            $lessonObtained = 0.0;
            $lessonPossible = 0.0;
            $lessonGraded   = 0;
            $lessonSlots    = count($items) * $students->count();

            foreach ($items as $type => $item) {
                foreach ($students as $student) {
                    $mark = $class->marks->first(fn ($m) => (int) $m->student_id === $student->id && $m->assessment_type === $type);
                    if (!$mark || $mark->marks === null) {
                        continue;
                    }

                    $lessonGraded++;
                    $totalGraded++;
                    $studentTotals[$student->id]['graded']++;

                    if ($item['max_marks']) {
                        $lessonObtained += $mark->marks;
                        $lessonPossible += $item['max_marks'];
                        $studentTotals[$student->id]['obtained'] += $mark->marks;
                        $studentTotals[$student->id]['possible'] += $item['max_marks'];
                    }
                }
            }

            $totalSlots += $lessonSlots;
            $lessonRows[] = [
                'class'   => $class,
                'items'   => $items,
                'graded'  => $lessonGraded,
                'total'   => $lessonSlots,
                'avgPct'  => $lessonPossible > 0 ? round($lessonObtained / $lessonPossible * 100, 1) : null,
            ];
        }

        $studentRows = $students->map(function ($student) use ($studentTotals) {
            $t = $studentTotals[$student->id];
            return [
                'student' => $student,
                'graded'  => $t['graded'],
                'pct'     => $t['possible'] > 0 ? round($t['obtained'] / $t['possible'] * 100, 1) : null,
            ];
        });

        $lessonPcts  = collect($lessonRows)->pluck('avgPct')->filter(fn ($v) => $v !== null);
        $classAvgPct = $lessonPcts->isNotEmpty() ? round($lessonPcts->avg(), 1) : null;

        return [
            'students'        => $students,
            'lessonRows'      => $lessonRows,
            'studentRows'     => $studentRows,
            'totalGraded'     => $totalGraded,
            'totalSlots'      => $totalSlots,
            'classAvgPct'     => $classAvgPct,
            'lessonsWithItems' => count($lessonRows),
            'approvedLessons' => $classes->where('review_status', ProgramClass::APPROVED)->count(),
            'totalLessons'    => $classes->count(),
        ];
    }
}
