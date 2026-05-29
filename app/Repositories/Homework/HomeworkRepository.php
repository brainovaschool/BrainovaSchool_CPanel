<?php

namespace App\Repositories\Homework;

use App\Models\Homework;
use App\Models\HomeworkStudent;
use App\Models\OnlineExamination\Answer;
use App\Models\OnlineExamination\AnswerChildren;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeworkRepository implements HomeworkInterface
{
    use ReturnFormatTrait, CommonHelperTrait;

    private $model;

    public function __construct(Homework $model)
    {
        $this->model = $model;
    }

    // =========================================================================
    // READ
    // =========================================================================

    public function all()
    {
        return $this->model->active()
            ->where('session_id', setting('session'))
            ->orderByDesc('id')
            ->get();
    }

    public function getPaginateAll()
    {
        $query = $this->model::latest()
            ->where('session_id', setting('session'));

        if (auth()->user() && !isHomeworkFilterAdmin()) {
            $query->whereIn('subject_id', teacherSubjects());
        }

        return $query->orderByDesc('id')->paginate(10);
    }

    /**
     * Admin-only: returns ALL homework with no teacher/subject scope.
     * Accepts optional GET filters: class, section, subject, task_type.
     * Loads class, section, subject and upload relations for the overview table.
     */
    public function getAllForAdmin($request = null)
    {
        $query = $this->model::with(['class', 'section', 'subject', 'upload'])
            ->orderByDesc('id');

        if ($request) {
            if (!empty($request->class)) {
                $query->where('classes_id', $request->class);
            }
            if (!empty($request->section)) {
                $query->where('section_id', $request->section);
            }
            if (!empty($request->subject)) {
                $query->where('subject_id', $request->subject);
            }
            if (!empty($request->task_type)) {
                $query->where('task_type', $request->task_type);
            }
        }

        return $query->paginate(20)->withQueryString();
    }

    public function search($request)
    {
        $query = $this->model::query()->where('session_id', setting('session'));

        $user = auth()->user();
        if ($user && !isHomeworkFilterAdmin()) {
            $query->whereIn('subject_id', teacherSubjects());
        }

        if (!empty($request->class)) {
            $query->where('classes_id', $request->class);
        }
        if (!empty($request->section)) {
            $query->where('section_id', $request->section);
        }
        if (!empty($request->subject)) {
            $query->where('subject_id', $request->subject);
        }

        return $query->orderByDesc('id')->paginate(10);
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $homework                  = new Homework();
            $homework->session_id      = setting('session');
            $homework->classes_id      = $request->class;
            $homework->section_id      = $request->section;
            $homework->subject_id      = $request->subject;
            $homework->topic           = $request->topic;
            $homework->title           = $request->title;
            $homework->task_type       = $request->task_type;
            $homework->date            = $request->date;
            $homework->submission_date = $request->submission_date;
            $homework->marks           = $request->marks;
            $homework->status          = $request->status ?? 1;
            $homework->description     = $request->description;

            // Save first so we have a homework->id for the CSV pivot inserts below.
            // document_id is updated in a second save after file processing.
            $homework->save();

            // Standard task: optional document (image / PDF / Word)
            if ($request->hasFile('document')) {
                $homework->document_id = $this->UploadImageCreate(
                    $request->document,
                    'backend/uploads/homeworks'
                );
                $homework->save();
            }

            // Quiz CSV: MUST process (parse rows) BEFORE calling UploadImageCreate.
            // UploadImageCreate moves the temp file from /tmp — after that move the
            // original path returned by getRealPath() no longer exists, causing the
            // "Failed to open stream: No such file or directory" error.
            if ($request->hasFile('bulk_questions_file')) {
                // Step 1 — parse CSV while the temp file is still at its original path
                $this->processQuizCsv($request->file('bulk_questions_file'), $homework->id);

                // Step 2 — now safely move/store the file for teacher re-download
                $homework->document_id = $this->UploadImageCreate(
                    $request->bulk_questions_file,
                    'backend/uploads/homeworks'
                );
                $homework->save();
            }

            DB::commit();
            return ['status' => true, 'message' => ___('alert.created_successfully')];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Homework Store Error: ' . $e->getMessage());
            return ['status' => false, 'message' => ___('alert.something_went_wrong')];
        }
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row                  = $this->model->findOrFail($id);
            $row->session_id      = setting('session');
            $row->classes_id      = $request->class;
            $row->section_id      = $request->section;
            $row->subject_id      = $request->subject;
            $row->topic           = $request->topic;
            $row->title           = $request->title;
            $row->task_type       = $request->task_type;
            $row->date            = $request->date;
            $row->submission_date = $request->submission_date;
            $row->marks           = $request->marks;
            $row->status          = $request->status ?? $row->status;
            $row->description     = $request->description;

            // Save core fields first
            $row->save();

            // Replace standard document if a new one is provided
            if ($request->hasFile('document')) {
                $row->document_id = $this->UploadImageUpdate(
                    $request->document,
                    'backend/uploads/homeworks',
                    $row->document_id
                );
                $row->save();
            }

            // Replace quiz CSV — process CSV BEFORE UploadImageUpdate moves the temp file
            if ($request->hasFile('bulk_questions_file')) {
                // Step 1 — delete old questions
                DB::table('homework_quiz_questions')->where('homework_id', $row->id)->delete();
                // Step 2 — parse new CSV while temp file still exists
                $this->processQuizCsv($request->file('bulk_questions_file'), $row->id);
                // Step 3 — now move/store the file (temp file consumed here)
                $row->document_id = $this->UploadImageUpdate(
                    $request->bulk_questions_file,
                    'backend/uploads/homeworks',
                    $row->document_id
                );
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);

        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error('Homework Update Error: ' . $th->getMessage());
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $row = $this->model->findOrFail($id);

            // Remove quiz questions linked to this homework
            DB::table('homework_quiz_questions')->where('homework_id', $id)->delete();

            // Remove uploaded file only if one exists (quizzes may have a CSV upload; others may not)
            if ($row->document_id) {
                $this->UploadImageDelete($row->document_id);
            }

            $row->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);

        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    // =========================================================================
    // EVALUATION
    // =========================================================================

    /**
     * Standard homework manual evaluation.
     * Teacher assigns marks and optional written feedback per student.
     * Automatically awards Brainova E6 Points on save.
     *
     * Expected request fields:
     *   homework_id     — int
     *   students[]      — array of student IDs (only submitted students)
     *   marks[]         — marks per student (parallel array to students[])
     *   feedback[]      — optional feedback text per student (parallel array)
     */
    public function evaluationSubmit($request)
    {
        DB::beginTransaction();
        try {
            foreach ($request->students as $key => $studentId) {
                $record = HomeworkStudent::where('homework_id', $request->homework_id)
                    ->where('student_id', $studentId)
                    ->firstOrFail();

                $record->marks = (int) $request->marks[$key];

                // Save feedback if the teacher provided it for this student
                if (isset($request->feedback[$key]) && trim($request->feedback[$key]) !== '') {
                    $record->feedback = trim($request->feedback[$key]);
                }

                $record->save();

                $this->awardE6Points((int) $studentId, $record->marks);
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.submitted_successfully'), []);

        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /**
     * Answer-sheet based marking for file-upload homework submissions.
     * Teacher grades each answer section; total is written back to homework_students.
     * Automatically awards Brainova E6 Points on save.
     */
    public function markSubmit($request)
    {
        DB::beginTransaction();
        try {
            $totalMark = 0;

            foreach ($request->answer_ids as $key => $answerId) {
                $child = AnswerChildren::find($answerId);
                if ($child) {
                    $child->evaluation_mark = array_key_exists($key, $request->marks)
                        ? (int) $request->marks[$key][0]
                        : 0;
                    $child->save();
                    $totalMark += $child->evaluation_mark;
                }
            }

            // Persist total on the Answer record
            $answer = Answer::where('homework_id', $request->homework_id)
                ->where('student_id', $request->student_id)
                ->firstOrFail();
            $answer->result = $totalMark;
            $answer->save();

            // Mirror onto homework_students so the index/evaluation view reflects the score
            $homeworkStudent = HomeworkStudent::where('homework_id', $request->homework_id)
                ->where('student_id', $request->student_id)
                ->first();

            if ($homeworkStudent) {
                $homeworkStudent->marks = $totalMark;
                $homeworkStudent->save();
                $this->awardE6Points((int) $request->student_id, $totalMark);
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.submitted_successfully'), []);

        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error('Homework Mark Submit Error: ' . $th->getMessage());
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    // =========================================================================
    // DASHBOARD STATS (for teacher portal charts)
    // =========================================================================

    /**
     * Returns aggregated statistics scoped to the current teacher's subjects and session.
     * Used by the teacher index view to power stats cards and Chart.js charts.
     */
    public function getStats(): array
    {
        $sessionId = setting('session');
        $query     = $this->model::where('session_id', $sessionId);

        if (auth()->user() && !isHomeworkFilterAdmin()) {
            $query->whereIn('subject_id', teacherSubjects());
        }

        $hwIds = (clone $query)->pluck('id');

        $byType = (clone $query)
            ->selectRaw('task_type, COUNT(*) as cnt')
            ->groupBy('task_type')
            ->pluck('cnt', 'task_type')
            ->toArray();

        $submitted   = \App\Models\HomeworkStudent::whereIn('homework_id', $hwIds)->count();
        $pendingEval = \App\Models\HomeworkStudent::whereIn('homework_id', $hwIds)
            ->whereNull('marks')
            ->count();

        return [
            'total'        => $hwIds->count(),
            'quizzes'      => $byType['quiz'] ?? 0,
            'submitted'    => $submitted,
            'pending_eval' => $pendingEval,
            'by_type'      => $byType,
        ];
    }

    // =========================================================================
    // TIER 2 — QUIZ ANALYTICS
    // =========================================================================

    /**
     * Returns quiz analytics: class-wide average score, per-student scores for charts,
     * and per-question accuracy / option distribution.
     *
     * submitted_students: rows in homework_students for this quiz.
     * Per-question stats use homework_quiz_answers (written on each submit).
     */
    public function getQuizAnalytics(int $homeworkId): array
    {
        $homework = DB::table('homework')->where('id', $homeworkId)->first();
        $maxMarks = isset($homework->marks) && is_numeric($homework->marks)
            ? (float) $homework->marks
            : 0.0;

        $submissions = DB::table('homework_students as hs')
            ->join('students as s', 's.id', '=', 'hs.student_id')
            ->where('hs.homework_id', $homeworkId)
            ->orderByDesc('hs.marks')
            ->orderBy('s.first_name')
            ->get([
                'hs.student_id',
                'hs.marks',
                DB::raw("TRIM(CONCAT(COALESCE(s.first_name,''),' ',COALESCE(s.last_name,''))) as student_name"),
            ]);

        $submittedCount = $submissions->count();
        $marksValues    = $submissions->pluck('marks')->filter(fn ($m) => $m !== null && $m !== '' && is_numeric($m))->map(fn ($m) => (float) $m);

        $avgScore = $marksValues->isNotEmpty() ? round($marksValues->avg(), 2) : null;
        $avgPct   = ($maxMarks > 0 && $marksValues->isNotEmpty())
            ? round(($marksValues->avg() / $maxMarks) * 100, 1)
            : null;

        $studentScores = $submissions->map(function ($row) use ($maxMarks) {
            $m   = isset($row->marks) && is_numeric($row->marks) ? (float) $row->marks : null;
            $pct = ($maxMarks > 0 && $m !== null) ? round(($m / $maxMarks) * 100, 1) : null;

            return [
                'student_id'   => (int) $row->student_id,
                'student_name' => $row->student_name ?: ('#' . $row->student_id),
                'marks'        => $m,
                'pct'          => $pct,
            ];
        })->values()->all();

        // Histogram buckets (0–10, 11–20, …) for score % — used when enough students
        $histogramLabels = [];
        $histogramData   = [];
        if ($maxMarks > 0 && $marksValues->isNotEmpty()) {
            $bins = array_fill(0, 10, 0);
            foreach ($marksValues as $mv) {
                $p = ($mv / $maxMarks) * 100;
                $idx = (int) floor($p / 10);
                if ($idx > 9) {
                    $idx = 9;
                }
                if ($idx < 0) {
                    $idx = 0;
                }
                $bins[$idx]++;
            }
            for ($b = 0; $b < 10; $b++) {
                $histogramLabels[] = ($b * 10) . '–' . (($b + 1) * 10) . '%';
                $histogramData[]   = $bins[$b];
            }
        }

        $questions = DB::table('homework_quiz_questions')
            ->where('homework_id', $homeworkId)
            ->orderBy('id')
            ->get();

        $allAnswers = collect();
        if (Schema::hasTable('homework_quiz_answers')) {
            $allAnswers = DB::table('homework_quiz_answers')
                ->where('homework_id', $homeworkId)
                ->get()
                ->groupBy('question_id');
        }

        $result = [];

        foreach ($questions as $q) {
            $qAnswers = $allAnswers->get($q->id, collect());

            $attempts     = $qAnswers->count();
            $correctCount = $qAnswers->where('is_correct', 1)->count();
            $accuracyPct  = $attempts > 0 ? round(($correctCount / $attempts) * 100) : 0;

            $optionCounts = [
                'a'       => $qAnswers->where('selected_answer', $q->option_a)->count(),
                'b'       => $qAnswers->where('selected_answer', $q->option_b)->count(),
                'c'       => $qAnswers->where('selected_answer', $q->option_c)->count(),
                'd'       => $qAnswers->where('selected_answer', $q->option_d)->count(),
                'skipped' => max(0, $submittedCount - $attempts),
            ];

            $result[] = [
                'id'             => $q->id,
                'question'       => $q->question,
                'option_a'       => $q->option_a,
                'option_b'       => $q->option_b,
                'option_c'       => $q->option_c,
                'option_d'       => $q->option_d,
                'correct_answer' => $q->correct_answer,
                'hint'           => $q->hint,
                'total_attempts' => $attempts,
                'correct_count'  => $correctCount,
                'accuracy_pct'   => $accuracyPct,
                'option_counts'  => $optionCounts,
            ];
        }

        $totalQuestions = count($result);
        $avgAccuracy    = $totalQuestions > 0
            ? round(array_sum(array_column($result, 'accuracy_pct')) / $totalQuestions)
            : 0;

        return [
            'homework'             => $homework,
            'questions'            => $result,
            'submitted_students'    => $submittedCount,
            'max_marks'            => $maxMarks,
            'avg_score'            => $avgScore,
            'avg_score_pct'        => $avgPct,
            'student_scores'       => $studentScores,
            'histogram_labels'     => $histogramLabels,
            'histogram_data'       => $histogramData,
            'avg_question_accuracy' => $avgAccuracy,
        ];
    }

    // =========================================================================
    // TIER 2 — CSV EXPORT
    // =========================================================================

    /**
     * Streams a CSV file of all student results for a given homework.
     *
     * Columns: Student Name | Admission No | Submission Date | Marks | Total Marks | Feedback
     *
     * Works for both quiz (auto-graded) and standard (teacher-graded) homework.
     * Returns a Laravel streaming download Response.
     */
    public function exportResultsCsv(int $homeworkId)
    {
        $homework = $this->model->findOrFail($homeworkId);

        // Fetch all students in the class/section for this homework
        $students = DB::table('session_class_students as scs')
            ->join('students as s',       's.id',  '=', 'scs.student_id')
            ->leftJoin('homework_students as hs', function ($join) use ($homeworkId) {
                $join->on('hs.student_id',  '=', 'scs.student_id')
                     ->where('hs.homework_id', $homeworkId);
            })
            ->where('scs.session_id',  setting('session'))
            ->where('scs.classes_id',  $homework->classes_id)
            ->where('scs.section_id',  $homework->section_id)
            ->select([
                DB::raw("CONCAT(s.first_name, ' ', s.last_name) as student_name"),
                's.admission_no',
                'hs.date        as submission_date',
                'hs.marks',
                'hs.feedback',
            ])
            ->orderBy('s.first_name')
            ->get();

        // Build CSV content
        $filename = 'results_' . str_replace([' ', '/'], '_', $homework->title ?? $homeworkId) . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($students, $homework) {
            $handle = fopen('php://output', 'w');

            // CSV header row
            fputcsv($handle, [
                'Student Name',
                'Admission No',
                'Submission Date',
                'Marks Earned',
                'Total Marks',
                'Feedback',
            ]);

            foreach ($students as $row) {
                fputcsv($handle, [
                    $row->student_name,
                    $row->admission_no  ?? '—',
                    $row->submission_date ?? 'Not submitted',
                    $row->marks          ?? '—',
                    $homework->marks     ?? '—',
                    $row->feedback       ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // DASHBOARD REPORTING (for admin/teacher filtered dashboard)
    // =========================================================================

    /**
     * Returns global stats for all classes (admin-level view).
     * Fetches: Total Tasks, Submitted, Pending Eval, Cumulative Score (E6 Points conversion).
     */
    public function getGlobalStats(): array
    {
        $sessionId = setting('session');

        // Total homework tasks assigned
        $totalTasks = $this->model::where('session_id', $sessionId)->count();

        // Total submissions (students who submitted something)
        $totalSubmitted = DB::table('homework_students')
            ->whereIn('homework_id', $this->model::where('session_id', $sessionId)->pluck('id'))
            ->count();

        // Pending evaluations (submitted but no marks yet)
        $pendingEval = DB::table('homework_students')
            ->whereIn('homework_id', $this->model::where('session_id', $sessionId)->pluck('id'))
            ->whereNull('marks')
            ->count();

        // Cumulative score in E6 Points (sum of all student scores converted to points)
        $totalMarks = DB::table('homework_students')
            ->whereIn('homework_id', $this->model::where('session_id', $sessionId)->pluck('id'))
            ->sum('marks');
        $e6Points = $totalMarks * config('brainova.e6_points_per_mark', 10);

        return [
            'total_tasks_assigned' => $totalTasks,
            'total_submitted'      => $totalSubmitted,
            'pending_evaluations'  => $pendingEval,
            'cumulative_score_e6'  => $e6Points,
        ];
    }

    /**
     * Returns filtered homework report data for charts and table.
     * Respects teacher permissions (if not admin, only returns assigned classes/sections).
     * Returns: table_html, chart_data (donut + line), statistics.
     */
    public function getFilteredHomeworkReport($filters): array
    {
        $sessionId = setting('session');
        
        $query = $this->model::with(['class', 'section', 'subject', 'upload'])
            ->where('session_id', $sessionId);

        // Apply filters
        if (!empty($filters['class'])) {
            $query->where('classes_id', $filters['class']);
        }
        if (!empty($filters['section'])) {
            $query->where('section_id', $filters['section']);
        }
        if (!empty($filters['subject'])) {
            $query->where('subject_id', $filters['subject']);
        }
        if (!empty($filters['task_type']) && $filters['task_type'] !== 'all') {
            $query->where('task_type', $filters['task_type']);
        }

        // Apply teacher permission scope (Super Admin + Admin see all homework)
        if (auth()->user() && !isHomeworkFilterAdmin()) {
            $query->whereIn('subject_id', teacherSubjects());
        }

        // Quest log: newest due date first (PHP sort — handles NULL/empty strings & driver quirks)
        $homeworks = $this->sortQuestLogByDueDateLatestFirst($query->get());

        // Build task status donut data (submission pipeline)
        $donutData = $this->getTaskStatisticsForFilters($homeworks);

        // Teacher marking backlog — manual tasks only (quizzes are auto-scored on submit)
        $evaluationStatus = $this->getEvaluationMarkingStatusForFilters($homeworks);
        $evaluationStatus['pipeline_submitted'] = (int) ($donutData['data'][0] ?? 0);
        $evaluationStatus['pipeline_pending'] = (int) ($donutData['data'][1] ?? 0);
        $evaluationStatus['pipeline_overdue'] = (int) ($donutData['data'][2] ?? 0);
        $evaluationStatus['pipeline_not_submitted'] = $evaluationStatus['pipeline_pending']
            + $evaluationStatus['pipeline_overdue'];

        // Build score trend line data
        $trendData = $this->getScoreTrendForFilters($homeworks);

        // Build table rows HTML (asterisk = has submitted work still awaiting marks)
        $tableHtml = view('backend.homework.partials.filtered-table', [
            'homeworks'                 => $homeworks,
            'homeworkIdsPendingMarks'   => $evaluationStatus['homework_ids_pending_marks'] ?? [],
            'perHomeworkStats'          => $evaluationStatus['per_homework'] ?? [],
        ])->render();

        return [
            'success'             => true,
            'table_html'          => $tableHtml,
            'donut_data'          => $donutData,
            'evaluation_status'   => $evaluationStatus,
            'trend_data'          => $trendData,
            'total_records'       => $homeworks->count(),
        ];
    }

    /**
     * Due column in UI is submission_date; sort newest deadline first, missing dates last.
     */
    private function sortQuestLogByDueDateLatestFirst(Collection $homeworks): Collection
    {
        return $homeworks->sort(function ($a, $b) {
            $tsA = $this->homeworkSubmissionDueTimestamp($a);
            $tsB = $this->homeworkSubmissionDueTimestamp($b);

            if ($tsA === null && $tsB === null) {
                return $b->id <=> $a->id;
            }
            if ($tsA === null) {
                return 1;
            }
            if ($tsB === null) {
                return -1;
            }
            if ($tsA !== $tsB) {
                return $tsB <=> $tsA;
            }

            return $b->id <=> $a->id;
        })->values();
    }

    private function homeworkSubmissionDueTimestamp($hw): ?int
    {
        $raw = $hw->submission_date ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($raw)->startOfDay()->timestamp;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Helper: Task status donut — disjoint student-assignment slots summed across
     * filtered homework. Same units for every slice (no overlap).
     *
     * - Submitted: distinct students who have a homework_students row for that task.
     * - Pending: enrolled students with no submission yet, deadline not passed (or no deadline).
     * - Overdue: enrolled students with no submission, submission_date is past end of due day.
     */
    private function getTaskStatisticsForFilters($homeworks): array
    {
        $sessionId = setting('session');

        $submitted = 0;
        $pending   = 0;
        $overdue   = 0;

        foreach ($homeworks as $hw) {
            $submittedHere = (int) DB::table('homework_students')
                ->where('homework_id', $hw->id)
                ->selectRaw('COUNT(DISTINCT student_id) as c')
                ->value('c');

            $eligible = (int) DB::table('session_class_students')
                ->where('session_id', $sessionId)
                ->where('classes_id', $hw->classes_id)
                ->where('section_id', $hw->section_id)
                ->selectRaw('COUNT(DISTINCT student_id) as c')
                ->value('c');

            $submitted += $submittedHere;

            if ($eligible < 1) {
                continue;
            }

            $missing = max(0, $eligible - $submittedHere);

            if ($missing === 0) {
                continue;
            }

            $deadlinePassed = $hw->submission_date
                && \Carbon\Carbon::parse($hw->submission_date)->endOfDay()->isPast();

            if ($deadlinePassed) {
                $overdue += $missing;
            } else {
                $pending += $missing;
            }
        }

        return [
            'labels' => ['Submitted', 'Pending', 'Overdue'],
            'data'   => [$submitted, $pending, $overdue],
            'colors' => ['#10b981', '#f59e0b', '#dc2626'],
        ];
    }

    /**
     * Submissions only (homework_students rows). Students with no row are not in evaluation stats.
     * awaiting_marks = submissions where marks are still null (teacher evaluation pending).
     */
    private function getEvaluationMarkingStatusForFilters($homeworks): array
    {
        $homeworksPendingEvaluation = 0;
        $submissionsAwaitingMarks    = 0;
        $submissionsGraded           = 0;
        $homeworkIdsPendingMarks     = [];
        $perHomework                 = [];

        foreach ($homeworks as $hw) {
            $submittedHere = (int) DB::table('homework_students')
                ->where('homework_id', $hw->id)
                ->selectRaw('COUNT(DISTINCT student_id) as c')
                ->value('c');

            $awaitingHere = $this->countHomeworkSubmissionsAwaitingTeacherMarks((int) $hw->id, $hw->task_type ?? null);

            $gradedHere = max(0, $submittedHere - $awaitingHere);

            $submissionsAwaitingMarks += $awaitingHere;
            $submissionsGraded += $gradedHere;

            $perHomework[(int) $hw->id] = [
                'submitted'       => $submittedHere,
                'awaiting_marks'  => $awaitingHere,
                'graded'          => $gradedHere,
            ];

            if ($awaitingHere > 0) {
                $homeworksPendingEvaluation++;
                $homeworkIdsPendingMarks[] = (int) $hw->id;
            }
        }

        return [
            'homeworks_pending_evaluation' => $homeworksPendingEvaluation,
            'submissions_awaiting_marks'   => $submissionsAwaitingMarks,
            'submissions_graded'           => $submissionsGraded,
            'homework_ids_pending_marks'   => $homeworkIdsPendingMarks,
            'per_homework'                 => $perHomework,
        ];
    }

    /**
     * Submissions that still need a teacher to enter marks (excludes auto-scored quizzes).
     */
    private function countHomeworkSubmissionsAwaitingTeacherMarks(int $homeworkId, ?string $taskType = null): int
    {
        if (($taskType ?? '') === 'quiz') {
            return 0;
        }

        return (int) DB::table('homework_students')
            ->where('homework_id', $homeworkId)
            ->where(function ($q) {
                $q->whereNull('marks');
            })
            ->selectRaw('COUNT(DISTINCT student_id) as c')
            ->value('c');
    }

    /**
     * Positive numeric max marks from homework row (column may be string in DB).
     */
    private function homeworkNumericMaxMarks($hw): ?float
    {
        $m = $hw->marks ?? null;
        if ($m === null || $m === '') {
            return null;
        }

        $str = trim((string) $m);

        return is_numeric($str) && (float) $str > 0 ? (float) $str : null;
    }

    /**
     * Fallback when homework_students.avg(marks) is empty but quiz answer rows exist.
     */
    private function averageQuizMarksFromAnswers(int $homeworkId, ?float $maxMarks): ?float
    {
        if ($maxMarks === null || $maxMarks <= 0) {
            return null;
        }

        try {
            $qCount = DB::table('homework_quiz_questions')->where('homework_id', $homeworkId)->count();
            if ($qCount < 1) {
                return null;
            }

            $perStudent = DB::table('homework_quiz_answers')
                ->where('homework_id', $homeworkId)
                ->groupBy('student_id')
                ->select('student_id')
                ->selectRaw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_n')
                ->get();
        } catch (\Throwable $e) {
            return null;
        }

        if ($perStudent->isEmpty()) {
            return null;
        }

        $sum = 0.0;
        foreach ($perStudent as $row) {
            $sum += ((int) $row->correct_n / $qCount) * $maxMarks;
        }

        return $sum / $perStudent->count();
    }

    /**
     * Score trend — chronological by assignment date.
     * Series A: average graded score (% of max when every task has numeric max, else raw marks).
     * Series B: % of enrolled students (class/section) who submitted — always populated when roster exists.
     */
    private function getScoreTrendForFilters($homeworks): array
    {
        if ($homeworks->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
                'dual_axis' => false,
                'y_left_title' => '',
                'y_right_title' => '',
                'y_suggested_max' => null,
            ];
        }

        $sessionId = setting('session');

        $sorted = $homeworks->sortBy(function ($h) {
            $ts = $h->date
                ? \Carbon\Carbon::parse($h->date)->timestamp
                : ($h->created_at ? \Carbon\Carbon::parse($h->created_at)->timestamp : (int) $h->id);

            return [$ts, (int) $h->id];
        })->values();

        $hwIds = $sorted->pluck('id')->all();

        $gradedStats = DB::table('homework_students')
            ->whereIn('homework_id', $hwIds)
            ->groupBy('homework_id')
            ->select('homework_id')
            ->selectRaw('AVG(CASE WHEN marks IS NOT NULL THEN marks END) as avg_graded')
            ->selectRaw('COUNT(DISTINCT student_id) as submitters')
            ->get()
            ->keyBy('homework_id');

        $usePercent = $sorted->every(fn ($h) => $this->homeworkNumericMaxMarks($h) !== null);

        $labels = [];
        $scorePoints = [];
        $submitPoints = [];

        foreach ($sorted as $hw) {
            $labels[] = $hw->date
                ? \Carbon\Carbon::parse($hw->date)->format('M j')
                : '#' . $hw->id;

            $stat = $gradedStats[$hw->id] ?? null;

            $avgGraded = null;
            if ($stat && $stat->avg_graded !== null && $stat->avg_graded !== '') {
                $avgGraded = (float) $stat->avg_graded;
            }

            $maxMarks = $this->homeworkNumericMaxMarks($hw);

            if ($avgGraded === null && ($hw->task_type ?? '') === 'quiz') {
                $avgGraded = $this->averageQuizMarksFromAnswers((int) $hw->id, $maxMarks);
            }

            if ($avgGraded !== null && $usePercent && $maxMarks !== null) {
                $scorePoints[] = round($avgGraded / $maxMarks * 100, 1);
            } elseif ($avgGraded !== null) {
                $scorePoints[] = round($avgGraded, 2);
            } else {
                $scorePoints[] = null;
            }

            $eligible = (int) DB::table('session_class_students')
                ->where('session_id', $sessionId)
                ->where('classes_id', $hw->classes_id)
                ->where('section_id', $hw->section_id)
                ->selectRaw('COUNT(DISTINCT student_id) as c')
                ->value('c');

            $submitters = $stat ? (int) $stat->submitters : 0;

            if ($eligible > 0) {
                $submitPoints[] = round(min(100, ($submitters / $eligible) * 100), 1);
            } else {
                $submitPoints[] = $submitters > 0 ? 100.0 : 0.0;
            }
        }

        $hex = '#2563eb';
        $rgb = sscanf($hex, '#%02x%02x%02x') ?: [37, 99, 235];
        $rgbaFill = sprintf('rgba(%d,%d,%d,0.12)', (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);

        $grayHex = '#64748b';
        $rgbG = sscanf($grayHex, '#%02x%02x%02x') ?: [100, 116, 139];
        $rgbaFillG = sprintf('rgba(%d,%d,%d,0.08)', (int) $rgbG[0], (int) $rgbG[1], (int) $rgbG[2]);

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => $usePercent ? 'Avg score (% of max)' : 'Avg marks (graded)',
                    'data'            => $scorePoints,
                    'y_axis_id'       => 'y',
                    'borderColor'     => $hex,
                    'backgroundColor' => $rgbaFill,
                    'borderWidth'     => 2,
                    'tension'         => 0.35,
                    'spanGaps'        => true,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Class submission rate',
                    'data'            => $submitPoints,
                    'y_axis_id'       => 'y1',
                    'borderColor'     => $grayHex,
                    'backgroundColor' => $rgbaFillG,
                    'borderWidth'     => 2,
                    'tension'         => 0.35,
                    'spanGaps'        => false,
                    'fill'            => false,
                ],
            ],
            'dual_axis'       => true,
            'y_left_title'    => $usePercent ? 'Avg score %' : 'Avg marks',
            'y_right_title'   => 'Submitted % (class)',
            'y_suggested_max' => $usePercent ? 100 : null,
        ];
    }

    /**
     * Returns sections for a given class (used by dependent dropdown).
     */
    /**
    *public function getSectionsByClass(int $classId): array
    *{
    *    $sections = DB::table('class_sections')
     *       ->where('classes_id', $classId)
     *       ->get(['id', 'name']);

     *   return $sections->toArray();
    *}
        */
 public function getSectionsByClass(int $classId): array
{
    $query = DB::table('subject_assigns as sa')
        ->join('sections', 'sections.id', '=', 'sa.section_id')
        ->where('sa.classes_id', $classId)
        ->where('sa.session_id', setting('session'))
        ->where('sections.status', 1);

    $user = auth()->user();
    if ($user && !isHomeworkFilterAdmin()) {
        $allowed = teacherSubjects();
        if ($allowed === []) {
            return [];
        }
        $query->join('subject_assign_childrens as sac', 'sac.subject_assign_id', '=', 'sa.id')
            ->whereIn('sac.subject_id', $allowed)
            ->distinct();
    } else {
        $query->distinct();
    }

    return $query->get(['sections.id', 'sections.name'])->toArray();
}

    /**
     * Returns subjects for a given class/section combo (used by dependent dropdown).
     */
    /**
    *public function getSubjectsByClassSection(int $classId, int $sectionId): array
   * {
      *  $subjects = DB::table('subject_assigns')
       *     ->join('subjects', 'subjects.id', '=', 'subject_assigns.subject_id')
       *     ->where('subject_assigns.classes_id', $classId)
       *     ->where('subject_assigns.section_id', $sectionId)
      *      ->distinct()
       *     ->get(['subjects.id', 'subjects.name']);

      *  return $subjects->toArray();
   * }
 */
public function getSubjectsByClassSection(int $classId, int $sectionId): array
{
    $query = DB::table('subject_assigns as sa')
        ->join('subject_assign_childrens as sac', 'sac.subject_assign_id', '=', 'sa.id')
        ->join('subjects', 'subjects.id', '=', 'sac.subject_id')
        ->where('sa.classes_id', $classId)
        ->where('sa.section_id', $sectionId)
        ->where('sa.session_id', setting('session'))
        ->distinct();

    // Teachers (non-admin): only subjects assigned to this staff member — same scope as homework lists / filtered report.
    $user = auth()->user();
    if ($user && !isHomeworkFilterAdmin()) {
        $allowed = teacherSubjects();
        if ($allowed === []) {
            return [];
        }
        $query->whereIn('subjects.id', $allowed);
    }

    return $query->get(['subjects.id', 'subjects.name'])->toArray();
}





    /**
     * Returns all unique task types used in this session.
     */
    public function getTaskTypes(): array
    {
        $types = DB::table('homework')
            ->where('session_id', setting('session'))
            ->distinct()
            ->pluck('task_type')
            ->filter()
            ->toArray();

        return $types;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * BRAINOVA E6 POINTS HOOK — DO NOT DELETE
     * ─────────────────────────────────────────
     * Awards E6 Points to a student when marks are saved.
     * Multiplier is set in config/brainova.php (default: 1 mark = 10 points).
     * To change the ratio, update BRAINOVA_E6_MULTIPLIER in .env — no code changes needed.
     */
    private function awardE6Points(int $studentId, int $marks): void
    {
        $points = $marks * config('brainova.e6_points_per_mark', 10);

        if ($points > 0) {
            DB::table('students')
                ->where('id', $studentId)
                ->increment('total_score', $points);
        }
    }

    /**
     * Parses a CSV file and inserts rows into homework_quiz_questions.
     *
     * Expected CSV column order:
     *   question | option_a | option_b | option_c | option_d | correct_answer | hint | explanation
     *
     * IMPORTANT: This table is completely separate from the online-examination
     * question_banks table. Homework quizzes must NEVER write to question_banks.
     */
    private function processQuizCsv($file, int $homeworkId): void
    {
        $handle = fopen($file->getRealPath(), 'r');
        fgetcsv($handle); // Skip the header row

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            // Skip rows that are too short or have a blank question
            if (count($row) < 6 || empty(trim($row[0]))) {
                continue;
            }

            DB::table('homework_quiz_questions')->insert([
                'homework_id'    => $homeworkId,
                'question'       => trim($row[0]),
                'option_a'       => trim($row[1] ?? ''),
                'option_b'       => trim($row[2] ?? ''),
                'option_c'       => trim($row[3] ?? ''),
                'option_d'       => trim($row[4] ?? ''),
                'correct_answer' => trim($row[5] ?? ''),
                'hint'           => isset($row[6]) && $row[6] !== '' ? trim($row[6]) : null,
                'explanation'    => isset($row[7]) && $row[7] !== '' ? trim($row[7]) : null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        fclose($handle);
    }
}
