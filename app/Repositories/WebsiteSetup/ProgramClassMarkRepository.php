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

    /** Upserts one ProgramClassMark per (lesson, gradable item) posted for
     *  THIS one student - the Grade page's own save, scoped by student
     *  rather than by lesson, since that's how the page itself is now
     *  filtered. A blank input clears that mark back to ungraded (null)
     *  rather than leaving a stale value behind. $input is keyed
     *  program_class_id => assessment_type => raw value, exactly how the
     *  Grade page names its "marks[...][...]" inputs. Re-derives each
     *  lesson's real gradable items rather than trusting posted keys, so
     *  a stray/tampered key can't create a mark for something that was
     *  never actually gradable. */
    public function saveForStudent(Program $program, Student $student, $request, ?int $gradedByStaffId): array
    {
        $input = (array) $request->input('marks', []);

        $classes = $program->programClasses()->whereIn('id', array_keys($input))->get();

        foreach ($classes as $class) {
            $validTypes  = array_keys($class->gradableItems());
            $postedTypes = (array) ($input[$class->id] ?? []);

            foreach ($postedTypes as $type => $raw) {
                if (!in_array($type, $validTypes, true)) {
                    continue;
                }

                $value = ($raw === null || $raw === '') ? null : (float) $raw;

                ProgramClassMark::updateOrCreate(
                    [
                        'program_class_id' => $class->id,
                        'assessment_type'  => $type,
                        'student_id'       => $student->id,
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

        // A plain array, not a Collection - the loop below does nested
        // increments like $studentTotals[$id]['graded']++, which throws
        // "Indirect modification of overloaded element" against a
        // Collection (its ArrayAccess returns values by copy, not
        // reference) the moment there's an actual graded mark to count.
        // Never surfaced before this exact program had any real marks.
        $studentTotals = $students->mapWithKeys(fn ($s) => [$s->id => ['obtained' => 0.0, 'possible' => 0.0, 'graded' => 0]])->all();

        $lessonRows  = [];
        $totalGraded = 0;
        $totalSlots  = 0;
        $pendingSubmissions = [];
        $recentActivity     = [];

        foreach ($classes as $class) {
            $items = $class->gradableItems();
            if (empty($items)) {
                continue;
            }

            $lessonObtained = 0.0;
            $lessonPossible = 0.0;
            $lessonGraded   = 0;
            $lessonPending  = 0;
            $lessonSlots    = count($items) * $students->count();

            foreach ($items as $type => $item) {
                foreach ($students as $student) {
                    $mark = $class->marks->first(fn ($m) => (int) $m->student_id === $student->id && $m->assessment_type === $type);
                    if (!$mark || $mark->marks === null) {
                        if ($mark && $mark->isSubmitted()) {
                            $lessonPending++;
                            $pendingSubmissions[] = [
                                'student'   => $student,
                                'class'     => $class,
                                'itemTitle' => $item['title'],
                                'mark'      => $mark,
                            ];
                            $recentActivity[] = [
                                'type'      => 'submitted',
                                'at'        => $mark->submitted_at,
                                'student'   => $student,
                                'class'     => $class,
                                'itemTitle' => $item['title'],
                                'mark'      => $mark,
                            ];
                        }
                        continue;
                    }

                    $lessonGraded++;
                    $totalGraded++;
                    $studentTotals[$student->id]['graded']++;

                    if ($mark->graded_at) {
                        $recentActivity[] = [
                            'type'      => 'graded',
                            'at'        => $mark->graded_at,
                            'student'   => $student,
                            'class'     => $class,
                            'itemTitle' => $item['title'],
                            'marks'     => $mark->marks,
                            'maxMarks'  => $item['max_marks'],
                        ];
                    }

                    if ($item['max_marks']) {
                        $lessonObtained += $mark->marks;
                        $lessonPossible += $item['max_marks'];
                        $studentTotals[$student->id]['obtained'] += $mark->marks;
                        $studentTotals[$student->id]['possible'] += $item['max_marks'];
                    }
                }
            }

            $totalSlots += $lessonSlots;

            $lessonStatus = 'not_started';
            if ($lessonSlots > 0) {
                if ($lessonGraded === $lessonSlots) {
                    $lessonStatus = 'completed';
                } elseif ($lessonGraded > 0 || $lessonPending > 0) {
                    $lessonStatus = 'in_progress';
                }
            }

            $lessonRows[] = [
                'class'   => $class,
                'items'   => $items,
                'graded'  => $lessonGraded,
                'total'   => $lessonSlots,
                'avgPct'  => $lessonPossible > 0 ? round($lessonObtained / $lessonPossible * 100, 1) : null,
                'status'  => $lessonStatus,
                // Short, chart/table-friendly label - this lesson's position
                // within THIS list (gradable-only), not its raw sort_order,
                // so "L1" in the trend chart always lines up with row 1 of
                // the table right below it, with no gaps for skipped lessons.
                'label'   => 'L' . (count($lessonRows) + 1),
            ];
        }

        $studentRows = $students->map(function ($student) use ($studentTotals) {
            $t = $studentTotals[$student->id];
            return [
                'student' => $student,
                'graded'  => $t['graded'],
                'pct'     => $t['possible'] > 0 ? round($t['obtained'] / $t['possible'] * 100, 1) : null,
            ];
        })->sortByDesc(fn ($row) => $row['pct'] ?? -1)->values();

        $lessonPcts  = collect($lessonRows)->pluck('avgPct')->filter(fn ($v) => $v !== null);
        $classAvgPct = $lessonPcts->isNotEmpty() ? round($lessonPcts->avg(), 1) : null;

        $statusCounts = collect($lessonRows)->countBy('status');

        usort($recentActivity, fn ($a, $b) => $b['at'] <=> $a['at']);
        $recentActivity = array_slice($recentActivity, 0, 8);

        return [
            'students'           => $students,
            'lessonRows'         => $lessonRows,
            'studentRows'        => $studentRows,
            'totalGraded'        => $totalGraded,
            'totalSlots'         => $totalSlots,
            'classAvgPct'        => $classAvgPct,
            'lessonsWithItems'   => count($lessonRows),
            'approvedLessons'    => $classes->where('review_status', ProgramClass::APPROVED)->count(),
            'totalLessons'       => $classes->count(),
            'pendingSubmissions' => $pendingSubmissions,
            'recentActivity'     => $recentActivity,
            'statusCounts'       => [
                'completed'   => (int) ($statusCounts['completed'] ?? 0),
                'in_progress' => (int) ($statusCounts['in_progress'] ?? 0),
                'not_started' => (int) ($statusCounts['not_started'] ?? 0),
            ],
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
        return $this->studentBreakdown($program, $student, true);
    }

    /** What clicking a student's name on the teacher-facing Progress &
     *  Report's "Student scores" table opens - the same per-student
     *  breakdown as progressForStudent(), but across every lesson with
     *  gradable items regardless of review status, not just Approved
     *  ones, since a teacher can grade a lesson before it's approved and
     *  should still see that mark here. */
    public function studentBreakdownForTeacher(Program $program, Student $student): array
    {
        return $this->studentBreakdown($program, $student, false);
    }

    private function studentBreakdown(Program $program, Student $student, bool $approvedOnly): array
    {
        $classes = $program->programClasses()
            ->when($approvedOnly, fn ($q) => $q->approved())
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
            $lessonRows[$class->id] = ['class' => $class, 'items' => $itemRows];
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

        // A student can change their mind and resubmit (wrong link pasted
        // by accident, say) right up until it's graded - once marks exist
        // the submission is the record a mark was given against, so it's
        // locked from here on.
        if ($mark->exists && $mark->isGraded()) {
            return $this->responseWithError('This has already been graded - ask your teacher if you need to change it.', []);
        }

        if ($file) {
            if (!$file->isValid()) {
                if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    return $this->responseWithError('That file is too large for this server to accept right now - try a smaller photo, or paste a link instead.', []);
                }
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

        $mark->submission_note = trim((string) $request->input('note')) ?: null;
        $mark->submitted_at    = now();
        $mark->save();

        return $this->responseWithSuccess('Submitted.', []);
    }

    /** The auto-graded counterpart to submitForStudent() - a CSV-backed
     *  quiz (see QuizEngineService) grades itself the moment the student
     *  submits, so taking it IS the submission; this writes the result
     *  straight into marks/graded_at, bypassing the manual Grade page
     *  entirely. graded_by stays null ("system-graded", not a staff
     *  member) - isGraded() only ever checks marks !== null, so nothing
     *  else has to know the difference. */
    public function autoGradeQuiz(ProgramClass $class, Student $student, float $earnedMarks): ProgramClassMark
    {
        return ProgramClassMark::updateOrCreate(
            [
                'program_class_id' => $class->id,
                'assessment_type'  => ProgramClassMark::QUIZ,
                'student_id'       => $student->id,
            ],
            [
                'marks'       => $earnedMarks,
                'graded_by'   => null,
                'graded_at'   => now(),
                'submitted_at' => now(),
            ]
        );
    }

    /** The "Grading" page's data: every lesson with at least one gradable
     *  item, across whichever programs the viewer can see (already
     *  filtered to just one program if they picked one), each with its
     *  own graded/awaiting-marks/no-hand-ins-yet breakdown - plus the
     *  same three numbers totalled, for the stat cards at the top. Mirrors
     *  the Homework "quest log" page's shape (same idea: filter, stat
     *  cards, a table of gradable work with a Grade action), but reusing
     *  our own simpler per-lesson grading screen rather than Homework's
     *  AJAX/modal machinery - there's no separate submission workflow to
     *  replicate here. */
    public function gradingOverview(array $programIds, ?int $selectedProgramId): array
    {
        $classes = ProgramClass::with(['program.students', 'marks'])
            ->whereIn('program_id', $programIds)
            ->when($selectedProgramId, fn ($q) => $q->where('program_id', $selectedProgramId))
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($c) => !empty($c->gradableItems()))
            ->values();

        $graded    = 0;
        $needMarks = 0;
        $noHandIns = 0;
        $rows      = [];

        foreach ($classes as $class) {
            $items    = $class->gradableItems();
            $students = $class->program->students;

            $lessonGraded    = 0;
            $lessonNeedMarks = 0;
            $lessonNoHandIns = 0;

            foreach ($items as $type => $item) {
                foreach ($students as $student) {
                    $mark = $class->marks->first(fn ($m) => (int) $m->student_id === $student->id && $m->assessment_type === $type);

                    if ($mark && $mark->marks !== null) {
                        $lessonGraded++;
                    } elseif ($mark && $mark->isSubmitted()) {
                        $lessonNeedMarks++;
                    } else {
                        $lessonNoHandIns++;
                    }
                }
            }

            $graded    += $lessonGraded;
            $needMarks += $lessonNeedMarks;
            $noHandIns += $lessonNoHandIns;

            $rows[] = [
                'class'     => $class,
                'items'     => $items,
                'graded'    => $lessonGraded,
                'needMarks' => $lessonNeedMarks,
                'noHandIns' => $lessonNoHandIns,
                'total'     => count($items) * $students->count(),
            ];
        }

        return [
            'rows'  => $rows,
            'stats' => ['graded' => $graded, 'needMarks' => $needMarks, 'noHandIns' => $noHandIns],
        ];
    }
}
