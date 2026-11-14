<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\StudentInfo\Student;
use App\Models\WebsiteSetup\Program;
use App\Models\WebsiteSetup\ProgramClass;
use App\Models\WebsiteSetup\ProgramClassMark;
use App\Services\B2Storage;
use App\Traits\ReturnFormatTrait;

/** Marks for a single lesson's gradable items (Quiz/Project/Worksheet/
 *  Assignment), one row per student per item - what the teacher-facing
 *  grading screen reads and writes, and what Progress & Report reads
 *  back out per program. Also where a student's own submission (a link
 *  or an uploaded file) for that same item lives - see submitForStudent(). */
class ProgramClassMarkRepository
{
    use ReturnFormatTrait;

    private B2Storage $storage;

    public function __construct(B2Storage $storage)
    {
        $this->storage = $storage;
    }

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

    /** The single-student counterpart to progressForProgram() above - what
     *  a student's own "My Courses" page reads. Only Approved lessons,
     *  and only this one student's own marks (the eager load itself is
     *  scoped to them, so a classmate's mark row never even loads into
     *  memory here). Keyed by program_class_id rather than a plain list,
     *  since the student view looks a lesson's row up by id while walking
     *  the same program->programClasses() list Course Details shows. */
    public function progressForStudent(Program $program, Student $student): array
    {
        $classes = $program->programClasses()
            ->approved()
            ->with(['marks' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderBy('sort_order')
            ->get();

        $lessonRows = [];
        $obtained   = 0.0;
        $possible   = 0.0;
        $graded     = 0;
        $totalSlots = 0;

        foreach ($classes as $class) {
            $items = $class->gradableItems();
            if (empty($items)) {
                continue;
            }

            $itemRows = [];
            foreach ($items as $type => $item) {
                $mark  = $class->marks->firstWhere('assessment_type', $type);
                $value = $mark?->marks;

                if ($value !== null) {
                    $graded++;
                    if ($item['max_marks']) {
                        $obtained += $value;
                        $possible += $item['max_marks'];
                    }
                }

                $itemRows[$type] = ['title' => $item['title'], 'max_marks' => $item['max_marks'], 'marks' => $value, 'mark' => $mark];
            }

            $totalSlots += count($items);
            $lessonRows[$class->id] = ['items' => $itemRows];
        }

        return [
            'lessonRows' => $lessonRows,
            'graded'     => $graded,
            'totalSlots' => $totalSlots,
            'pct'        => $possible > 0 ? round($obtained / $possible * 100, 1) : null,
        ];
    }

    /** A student submitting their own work for one gradable item on a
     *  lesson - a link or an uploaded file, same file-or-link convention
     *  as teacher materials. Only ever touches submission_* columns;
     *  marks/graded_by/graded_at are left exactly as they were; grading
     *  is the teacher's separate action via save() above. */
    public function submitForStudent(ProgramClass $class, Student $student, string $type, $request): array
    {
        if (!array_key_exists($type, $class->gradableItems())) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $linkUrl = trim((string) $request->input('link_url'));
        $file    = $request->file('file');

        $mark = ProgramClassMark::firstOrNew([
            'program_class_id' => $class->id,
            'assessment_type'  => $type,
            'student_id'       => $student->id,
        ]);

        if ($file) {
            if (!$file->isValid()) {
                return $this->responseWithError("The file didn't upload properly (code {$file->getError()}) - try again.", []);
            }

            $uploaded = $this->storage->upload($file, 'program-class-submissions');
            if (!$uploaded['ok']) {
                return $this->responseWithError($uploaded['message'], []);
            }

            $mark->submission_storage_key       = $uploaded['storage_key'];
            $mark->submission_original_filename = $uploaded['original_filename'];
            $mark->submission_link_url          = null;
        } elseif ($linkUrl !== '') {
            $mark->submission_link_url          = $linkUrl;
            $mark->submission_storage_key       = null;
            $mark->submission_original_filename = null;
        } else {
            return $this->responseWithError('Add a link or choose a file before submitting.', []);
        }

        $mark->submitted_at = now();
        $mark->save();

        return $this->responseWithSuccess('Submitted.', []);
    }
}
