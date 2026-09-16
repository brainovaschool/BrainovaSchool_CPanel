<?php

namespace App\Http\Controllers\StudentPanel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\HomeworkStudent;
use App\Http\Requests\StudentPanel\HomeworkSubmit;
use App\Repositories\StudentPanel\Homework\HomeworkInterface;
use App\Repositories\LearningEngine\LearningEventRepository;

class HomeworkController extends Controller
{
    private $repo;
    private $learningEvents;

    public function __construct(HomeworkInterface $repo, LearningEventRepository $learningEvents)
    {
        $this->repo           = $repo;
        $this->learningEvents = $learningEvents;
    }

    // =========================================================================
    // HOMEWORK LIST
    // =========================================================================

    public function index()
    {
        $data['homeworks'] = $this->repo->index();
        $data['title']     = ___('examination.homework');

        return view('student-panel.homeworks', compact('data'));
    }

    // =========================================================================
    // STANDARD HOMEWORK SUBMISSION (file upload)
    // =========================================================================

    public function submit(HomeworkSubmit $request)
    {
        $result = $this->repo->submit($request);
        return response()->json($result);
    }

    public function homeworkAnswer($id)
    {
        $data = $this->repo->show($id);
        if (!$data) {
            return redirect()->route('student-panel-homeworks.index')
                ->with('error', 'This homework is not available.');
        }

        return view('student-panel.homework-question-view', compact('data'));
    }

    public function homeworkAnswerSubmit(HomeworkSubmit $request)
    {
        try {
            $this->repo->submit($request);
            return redirect()->route('student-panel-homeworks.index')
                ->with('success', 'Homework submitted successfully');
        } catch (\Throwable $th) {
            \Log::error('Homework Submit Error: ' . $th->getMessage());
            return redirect()->route('student-panel-homeworks.index')
                ->with('error', 'Something went wrong. Please try again.');
        }
    }

    // =========================================================================
    // QUIZ — TAKE OR REVIEW
    // =========================================================================

    /**
     * Load the quiz for a student.
     *
     * First-time visit  → interactive quiz (shuffle questions, start timer).
     * Already submitted → read-only review mode showing correct/incorrect answers.
     *
     * Students can NEVER reattempt a submitted quiz.
     */
    public function takeQuiz($id)
    {
        $student  = Auth::user()->student;
        $homework = DB::table('homework')->where('id', $id)->first();

        if (!$homework) {
            return redirect()->route('student-panel-homeworks.index')
                ->with('error', 'Quiz not found.');
        }

        // Check if already submitted
        $submission = HomeworkStudent::where('student_id', $student->id)
            ->where('homework_id', $id)
            ->first();

        // Inactive tasks are hidden from the list; allow access only to review a prior submission.
        if ((int) ($homework->status ?? 0) !== Status::ACTIVE && !$submission) {
            return redirect()->route('student-panel-homeworks.index')
                ->with('error', 'This quiz is not available.');
        }

        // Load questions — shuffled for live quiz, ordered for review
        if ($submission) {
            $questions = DB::table('homework_quiz_questions')
                ->where('homework_id', $id)
                ->orderBy('id')
                ->get();
        } else {
            $questions = DB::table('homework_quiz_questions')
                ->where('homework_id', $id)
                ->inRandomOrder()
                ->get();
        }

        $data['title']      = $homework->title ?? 'Quiz';
        $data['homework']   = $homework;
        $data['questions']  = $questions;
        $data['submission'] = $submission; // null = first attempt; object = already done
        $data['isReview']   = $submission !== null;

        return view('student-panel.take-quiz', compact('data'));
    }

    // =========================================================================
    // QUIZ SUBMISSION (AJAX)
    // =========================================================================

    /**
     * Receives quiz answers from the JS frontend, grades them server-side,
     * stores the result in homework_students, and awards Brainova E6 Points.
     *
     * Scoring: each correct answer earns (homework.marks / total_questions) marks.
     * Hint penalty: 50% deducted from that question's marks.
     * Total is rounded to 2 decimal places.
     */
    public function submitInteractiveQuiz(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student profile not found.',
            ], 403);
        }

        $student    = $user->student;
        $homeworkId = (int) $request->homework_id;

        if ($homeworkId < 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid quiz.',
            ], 422);
        }

        // Guard: prevent reattempt (before opening a transaction)
        $alreadySubmitted = HomeworkStudent::where('student_id', $student->id)
            ->where('homework_id', $homeworkId)
            ->exists();

        if ($alreadySubmitted) {
            return response()->json([
                'status'  => 'already_submitted',
                'message' => 'You have already submitted this quiz.',
            ]);
        }

        $homework = DB::table('homework')->where('id', $homeworkId)->first();
        if (!$homework || (int) ($homework->status ?? 0) !== Status::ACTIVE) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Quiz not found or no longer available.',
            ], 404);
        }

        $totalQuestions = DB::table('homework_quiz_questions')
            ->where('homework_id', $homeworkId)
            ->count();

        $maxMarks = isset($homework->marks) && is_numeric($homework->marks)
            ? (float) $homework->marks
            : 0.0;

        $marksPerQuestion = ($totalQuestions > 0 && $maxMarks > 0)
            ? $maxMarks / $totalQuestions
            : 1.0;

        // Grade answers sent from JS
        // $request->answers = [ quiz_question_id => 'selected_option_text', ... ]
        // $request->hints   = [ quiz_question_id => true, ... ]
        $answers     = is_array($request->answers) ? $request->answers : [];
        $hintsUsed   = is_array($request->hints) ? $request->hints : [];
        $earnedMarks = 0.0;

        $allQuestionRows = DB::table('homework_quiz_questions')
            ->where('homework_id', $homeworkId)
            ->orderBy('id')
            ->get();

        $quizAnswerRows = [];
        $skillEvents    = [];
        $now            = now();

        foreach ($allQuestionRows as $question) {
            $questionId = (int) $question->id;
            $rawAnswer  = $answers[$questionId] ?? $answers[(string) $questionId] ?? null;
            $selected   = is_string($rawAnswer) ? trim($rawAnswer) : '';

            $picked   = strtolower($selected);
            $expected = strtolower(trim((string) ($question->correct_answer ?? '')));

            $isCorrect = $picked !== '' && $picked === $expected;

            if ($isCorrect) {
                $points = $marksPerQuestion;
                $qKey   = (string) $questionId;
                if (!empty($hintsUsed[$questionId]) || !empty($hintsUsed[$qKey])) {
                    $points *= 0.5;
                }
                $earnedMarks += $points;
            }

            // If this question is tagged with a Skill (Website Setup → Skills,
            // tagged in the "View Questions" modal), a real answer here feeds
            // the same skill-mastery system Online Exam grading already does
            // — Brain Level, Mistake Bank, Badges etc. all pick it up for
            // free since they all read from the same shared event log.
            // Left-blank answers don't count as an attempt either way. Queued
            // here, recorded after the transaction commits below, so a failed
            // submission can never leave mastery data ahead of the official
            // homework record.
            if ($selected !== '' && !empty($question->skill_id)) {
                $skillEvents[] = ['skill_id' => (int) $question->skill_id, 'correct' => $isCorrect];
            }

            if (Schema::hasTable('homework_quiz_answers')) {
                $quizAnswerRows[] = [
                    'homework_id'      => $homeworkId,
                    'student_id'       => $student->id,
                    'question_id'      => $questionId,
                    'selected_answer'  => $selected !== '' ? $selected : null,
                    'is_correct'       => $isCorrect ? 1 : 0,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }

        $earnedMarks = round($earnedMarks, 2);

        DB::beginTransaction();
        try {
            // Record submission — no file upload for quizzes, homework column is nullable
            DB::table('homework_students')->insert([
                'student_id'  => $student->id,
                'homework_id' => $homeworkId,
                'homework'    => null,
                'marks'       => $earnedMarks,
                'date'        => now()->format('Y-m-d'),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            if ($quizAnswerRows !== []) {
                foreach (array_chunk($quizAnswerRows, 100) as $chunk) {
                    DB::table('homework_quiz_answers')->insert($chunk);
                }
            }

            // Brainova E6 Points Hook — skip if column missing (avoids SQL error on some DBs)
            $e6Points = (int) round($earnedMarks * (float) config('brainova.e6_points_per_mark', 10));
            if ($e6Points > 0 && Schema::hasColumn('students', 'total_score')) {
                DB::table('students')
                    ->where('id', $student->id)
                    ->increment('total_score', $e6Points);
            }

            DB::commit();

            // Only recorded once the real submission is safely committed —
            // record() does its own error handling internally anyway, so it
            // can't roll back this transaction; running it after commit is
            // what actually keeps skill-mastery data honest with what's
            // officially on the books.
            foreach ($skillEvents as $event) {
                $this->learningEvents->record(
                    $student->id,
                    LearningEventRepository::EVENT_ANSWER_SUBMITTED,
                    $event['skill_id'],
                    ['correct' => $event['correct'], 'source' => 'homework_quiz']
                );
            }

            return response()->json([
                'status'    => 'success',
                'message'   => 'Quiz submitted!',
                'earned'    => $earnedMarks,
                'total'     => $homework->marks,
                'e6_points' => $e6Points,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error('Quiz Submit Error: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    // =========================================================================
    // CSV TEMPLATE DOWNLOAD
    // =========================================================================

    public function downloadSample()
    {
        $path = public_path('Quiz_Template.csv');

        if (file_exists($path)) {
            return response()->download($path, 'Quiz_Template.csv', ['Content-Type' => 'text/csv']);
        }

        return redirect()->back()->with('error', 'Template file not found.');
    }
}
