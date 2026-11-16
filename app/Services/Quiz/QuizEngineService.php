<?php

namespace App\Services\Quiz;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Owner-agnostic CSV-quiz engine shared by Homework (homework_id) and
 *  Program Class lessons (program_class_id) - the same homework_quiz_questions/
 *  homework_quiz_answers tables, with exactly one of those two columns set
 *  per row. Ported verbatim from the logic that already lived inline in
 *  HomeworkRepository::processQuizCsv()/getQuizAnalytics() and
 *  StudentPanel\HomeworkController::submitInteractiveQuiz() - the existing
 *  Homework code paths are untouched; this is the one place that logic now
 *  also gets reused from. */
class QuizEngineService
{
    public const OWNER_HOMEWORK      = 'homework_id';
    public const OWNER_PROGRAM_CLASS = 'program_class_id';

    public function questionsForOwner(string $ownerColumn, int $ownerId, bool $shuffle = false): Collection
    {
        $query = DB::table('homework_quiz_questions')->where($ownerColumn, $ownerId);

        return $shuffle ? $query->inRandomOrder()->get() : $query->orderBy('id')->get();
    }

    public function hasAnyAttempts(string $ownerColumn, int $ownerId): bool
    {
        if (!Schema::hasTable('homework_quiz_answers')) {
            return false;
        }

        return DB::table('homework_quiz_answers')->where($ownerColumn, $ownerId)->exists();
    }

    /** Deletes this owner's existing question set and imports a new one from
     *  the uploaded CSV - same column order/skip rules as
     *  HomeworkRepository::processQuizCsv(). Refuses (does nothing
     *  destructive) if this owner already has recorded attempts, so a
     *  re-upload can never silently invalidate a student's result.
     *  @return array{status:string,message?:string,imported?:int} */
    public function replaceQuestions(UploadedFile $file, string $ownerColumn, int $ownerId): array
    {
        if ($this->hasAnyAttempts($ownerColumn, $ownerId)) {
            return [
                'status'  => 'blocked',
                'message' => "This quiz already has student submissions - re-uploading a new question set would invalidate their results. The existing questions were kept; remove the existing submissions first if you really need to replace them.",
            ];
        }

        DB::table('homework_quiz_questions')->where($ownerColumn, $ownerId)->delete();

        $handle = fopen($file->getRealPath(), 'r');
        fgetcsv($handle); // skip the header row

        $imported = 0;
        $now      = now();

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 6 || empty(trim($row[0]))) {
                continue;
            }

            DB::table('homework_quiz_questions')->insert([
                $ownerColumn     => $ownerId,
                'question'       => trim($row[0]),
                'option_a'       => trim($row[1] ?? ''),
                'option_b'       => trim($row[2] ?? ''),
                'option_c'       => trim($row[3] ?? ''),
                'option_d'       => trim($row[4] ?? ''),
                'correct_answer' => trim($row[5] ?? ''),
                'hint'           => isset($row[6]) && $row[6] !== '' ? trim($row[6]) : null,
                'explanation'    => isset($row[7]) && $row[7] !== '' ? trim($row[7]) : null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $imported++;
        }

        fclose($handle);

        return ['status' => 'ok', 'imported' => $imported];
    }

    /** Grades a submitted attempt against $questions - same scoring rule as
     *  the Homework quiz: each correct answer earns (maxMarks / question
     *  count), halved if that question's hint was used. Returns the earned
     *  total plus the per-question answer rows and any skill-mastery events
     *  to record - writing those rows/events to the right tables is left to
     *  the caller, since where they land differs per owner type.
     *  @return array{earned:float, answer_rows:array, skill_events:array} */
    public function grade(Collection $questions, array $answers, array $hintsUsed, float $maxMarks): array
    {
        $totalQuestions   = $questions->count();
        $marksPerQuestion = ($totalQuestions > 0 && $maxMarks > 0) ? $maxMarks / $totalQuestions : 1.0;

        $earnedMarks = 0.0;
        $answerRows  = [];
        $skillEvents = [];

        foreach ($questions as $question) {
            $questionId = (int) $question->id;
            $rawAnswer  = $answers[$questionId] ?? $answers[(string) $questionId] ?? null;
            $selected   = is_string($rawAnswer) ? trim($rawAnswer) : '';

            $picked    = strtolower($selected);
            $expected  = strtolower(trim((string) ($question->correct_answer ?? '')));
            $isCorrect = $picked !== '' && $picked === $expected;

            if ($isCorrect) {
                $points = $marksPerQuestion;
                $qKey   = (string) $questionId;
                if (!empty($hintsUsed[$questionId]) || !empty($hintsUsed[$qKey])) {
                    $points *= 0.5;
                }
                $earnedMarks += $points;
            }

            if ($selected !== '' && !empty($question->skill_id)) {
                $skillEvents[] = ['skill_id' => (int) $question->skill_id, 'correct' => $isCorrect];
            }

            $answerRows[] = [
                'question_id'     => $questionId,
                'selected_answer' => $selected !== '' ? $selected : null,
                'is_correct'      => $isCorrect ? 1 : 0,
            ];
        }

        return [
            'earned'       => round($earnedMarks, 2),
            'answer_rows'  => $answerRows,
            'skill_events' => $skillEvents,
        ];
    }

    /** Chunked insert of this attempt's per-question answers into
     *  homework_quiz_answers, tagged with whichever owner column applies. */
    public function recordAnswers(string $ownerColumn, int $ownerId, int $studentId, array $answerRows): void
    {
        if (!Schema::hasTable('homework_quiz_answers') || $answerRows === []) {
            return;
        }

        $now  = now();
        $rows = array_map(function (array $row) use ($ownerColumn, $ownerId, $studentId, $now) {
            return [
                $ownerColumn      => $ownerId,
                'student_id'      => $studentId,
                'question_id'     => $row['question_id'],
                'selected_answer' => $row['selected_answer'],
                'is_correct'      => $row['is_correct'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }, $answerRows);

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('homework_quiz_answers')->insert($chunk);
        }
    }

    /** The same analytics shape HomeworkRepository::getQuizAnalytics()
     *  already returns (submission count/avg score/histogram/per-question
     *  accuracy), minus the 'homework' key - the caller fills that in with
     *  its own owner object. $submissions: a Collection of rows (object or
     *  array) each exposing student_id/marks/student_name - where those
     *  come from differs per owner (homework_students vs program_class_marks). */
    public function questionStats(string $ownerColumn, int $ownerId, Collection $submissions, float $maxMarks): array
    {
        $submittedCount = $submissions->count();
        $marksValues    = $submissions->pluck('marks')
            ->filter(fn ($m) => $m !== null && $m !== '' && is_numeric($m))
            ->map(fn ($m) => (float) $m);

        $avgScore = $marksValues->isNotEmpty() ? round($marksValues->avg(), 2) : null;
        $avgPct   = ($maxMarks > 0 && $marksValues->isNotEmpty())
            ? round(($marksValues->avg() / $maxMarks) * 100, 1)
            : null;

        $studentScores = $submissions->map(function ($row) use ($maxMarks) {
            $row = (object) $row;
            $m   = isset($row->marks) && is_numeric($row->marks) ? (float) $row->marks : null;
            $pct = ($maxMarks > 0 && $m !== null) ? round(($m / $maxMarks) * 100, 1) : null;

            return [
                'student_id'   => (int) $row->student_id,
                'student_name' => $row->student_name ?: ('#' . $row->student_id),
                'marks'        => $m,
                'pct'          => $pct,
            ];
        })->values()->all();

        $histogramLabels = [];
        $histogramData   = [];
        if ($maxMarks > 0 && $marksValues->isNotEmpty()) {
            $bins = array_fill(0, 10, 0);
            foreach ($marksValues as $mv) {
                $p   = ($mv / $maxMarks) * 100;
                $idx = max(0, min(9, (int) floor($p / 10)));
                $bins[$idx]++;
            }
            for ($b = 0; $b < 10; $b++) {
                $histogramLabels[] = ($b * 10) . '–' . (($b + 1) * 10) . '%';
                $histogramData[]   = $bins[$b];
            }
        }

        $questions = DB::table('homework_quiz_questions')->where($ownerColumn, $ownerId)->orderBy('id')->get();

        $allAnswers = collect();
        if (Schema::hasTable('homework_quiz_answers')) {
            $allAnswers = DB::table('homework_quiz_answers')->where($ownerColumn, $ownerId)->get()->groupBy('question_id');
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
            'questions'              => $result,
            'submitted_students'     => $submittedCount,
            'max_marks'              => $maxMarks,
            'avg_score'              => $avgScore,
            'avg_score_pct'          => $avgPct,
            'student_scores'         => $studentScores,
            'histogram_labels'       => $histogramLabels,
            'histogram_data'         => $histogramData,
            'avg_question_accuracy'  => $avgAccuracy,
        ];
    }
}
