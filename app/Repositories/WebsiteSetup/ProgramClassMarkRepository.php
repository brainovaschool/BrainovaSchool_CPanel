<?php

namespace App\Repositories\WebsiteSetup;

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
}
