<?php

namespace App\Http\Controllers\StudentPanel;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClass;
use App\Models\WebsiteSetup\ProgramClassMark;
use App\Repositories\LearningEngine\LearningEventRepository;
use App\Repositories\WebsiteSetup\ProgramClassMarkRepository;
use App\Repositories\WebsiteSetup\ProgramClassRepository;
use App\Services\Quiz\QuizEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** The Program-Class-lesson counterpart to
 *  StudentPanel\HomeworkController::takeQuiz()/submitInteractiveQuiz() -
 *  deliberately a separate controller rather than changes to that one, so
 *  the existing, live Homework quiz path is never touched. Shares the
 *  question storage, grading math, and the take-quiz view itself via
 *  QuizEngineService - see that class and resources/views/student-panel/
 *  take-quiz.blade.php for how the two owner types stay in sync. */
class ProgramClassQuizController extends Controller
{
    private QuizEngineService $quizEngine;
    private ProgramClassMarkRepository $markRepo;
    private ProgramClassRepository $classRepo;
    private LearningEventRepository $learningEvents;

    public function __construct(
        QuizEngineService $quizEngine,
        ProgramClassMarkRepository $markRepo,
        ProgramClassRepository $classRepo,
        LearningEventRepository $learningEvents
    ) {
        $this->quizEngine     = $quizEngine;
        $this->markRepo       = $markRepo;
        $this->classRepo      = $classRepo;
        $this->learningEvents = $learningEvents;
    }

    public function takeQuiz($id)
    {
        $student = Auth::user()->student;
        $class   = ProgramClass::with('program')->find($id);

        if (!$class || !$class->hasQuiz() || !$class->hasQuizQuestions()) {
            return redirect()->route('dashboard')->with('danger', 'Quiz not found.');
        }

        $enrolled = $student && $student->programs()->active()->where('programs.id', $class->program_id)->exists();
        if (!$enrolled) {
            return redirect()->route('dashboard')->with('danger', "This isn't available to you.");
        }

        $mark = ProgramClassMark::where([
            'program_class_id' => $class->id,
            'assessment_type'  => ProgramClassMark::QUIZ,
            'student_id'       => $student->id,
        ])->first();

        $isReview = $mark && $mark->isGraded();

        // Inactive/unapproved lessons are hidden from Course Details; allow
        // access only to review an already-graded attempt, same rule the
        // Homework quiz uses for its own Active-status check.
        if (!$class->isApproved() && !$isReview) {
            return redirect($this->classRepo->courseDetailsUrl($class->program))
                ->with('danger', 'This quiz is not available.');
        }

        $questions = $this->quizEngine->questionsForOwner(
            QuizEngineService::OWNER_PROGRAM_CLASS,
            $class->id,
            shuffle: !$isReview
        );

        $data['title']      = $class->quiz_title ?: 'Quiz';
        // Duck-typed to look like the plain `homework` DB row the shared
        // take-quiz view already reads - same properties, same shapes.
        $data['homework']   = (object) [
            'id'          => $class->id,
            'title'       => $class->quiz_title,
            'description' => $class->quiz_description,
            'marks'       => $class->quiz_max_marks,
        ];
        $data['questions']  = $questions;
        $data['submission'] = $isReview ? $mark : null;
        $data['isReview']   = $isReview;
        $data['ownerType']  = 'program_class';
        $data['submitUrl']  = route('student-panel-program-class-quiz.submit-interactive-quiz');
        $data['backUrl']    = $this->classRepo->courseDetailsUrl($class->program);
        $data['backLabel']  = 'Back to Course';

        return view('student-panel.take-quiz', compact('data'));
    }

    public function submitInteractiveQuiz(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->student) {
            return response()->json(['status' => 'error', 'message' => 'Student profile not found.'], 403);
        }

        $student = $user->student;
        $classId = (int) $request->program_class_id;

        if ($classId < 1) {
            return response()->json(['status' => 'error', 'message' => 'Invalid quiz.'], 422);
        }

        // Guard: prevent reattempt (before opening a transaction) - same
        // convention as Homework's own guard, just against program_class_marks.
        $alreadySubmitted = ProgramClassMark::where([
            'program_class_id' => $classId,
            'assessment_type'  => ProgramClassMark::QUIZ,
            'student_id'       => $student->id,
        ])->whereNotNull('marks')->exists();

        if ($alreadySubmitted) {
            return response()->json(['status' => 'already_submitted', 'message' => 'You have already submitted this quiz.']);
        }

        $class = ProgramClass::find($classId);
        if (!$class || !$class->isApproved() || !$class->hasQuizQuestions()) {
            return response()->json(['status' => 'error', 'message' => 'Quiz not found or no longer available.'], 404);
        }

        $enrolled = $student->programs()->active()->where('programs.id', $class->program_id)->exists();
        if (!$enrolled) {
            return response()->json(['status' => 'error', 'message' => "This isn't available to you."], 403);
        }

        $questions = $this->quizEngine->questionsForOwner(QuizEngineService::OWNER_PROGRAM_CLASS, $classId);
        $maxMarks  = (float) ($class->quiz_max_marks ?? 0);

        $answers   = is_array($request->answers) ? $request->answers : [];
        $hintsUsed = is_array($request->hints) ? $request->hints : [];

        $graded = $this->quizEngine->grade($questions, $answers, $hintsUsed, $maxMarks);

        DB::beginTransaction();
        try {
            $this->markRepo->autoGradeQuiz($class, $student, $graded['earned']);
            $this->quizEngine->recordAnswers(QuizEngineService::OWNER_PROGRAM_CLASS, $classId, $student->id, $graded['answer_rows']);

            // Same Brainova E6 Points hook as the Homework quiz.
            $e6Points = (int) round($graded['earned'] * (float) config('brainova.e6_points_per_mark', 10));
            if ($e6Points > 0 && Schema::hasColumn('students', 'total_score')) {
                DB::table('students')->where('id', $student->id)->increment('total_score', $e6Points);
            }

            DB::commit();

            // Recorded after commit, same reasoning as the Homework quiz -
            // record() does its own error handling and can't roll this back,
            // so running it after is what keeps mastery data honest with
            // what's officially on the books.
            foreach ($graded['skill_events'] as $event) {
                $this->learningEvents->record(
                    $student->id,
                    LearningEventRepository::EVENT_ANSWER_SUBMITTED,
                    $event['skill_id'],
                    ['correct' => $event['correct'], 'source' => 'program_class_quiz']
                );
            }
            $this->learningEvents->markTodayActive($student->id);

            return response()->json([
                'status'    => 'success',
                'message'   => 'Quiz submitted!',
                'earned'    => $graded['earned'],
                'total'     => $maxMarks,
                'e6_points' => $e6Points,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error('Program Class Quiz Submit Error: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return response()->json(['status' => 'error', 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
