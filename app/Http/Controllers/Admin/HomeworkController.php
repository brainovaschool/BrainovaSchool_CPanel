<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StudentInfo\Student;
use App\Http\Controllers\Controller;
use App\Models\StudentInfo\SessionClassStudent;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\Academic\SectionRepository;
use App\Repositories\Academic\SubjectRepository;
use App\Repositories\Homework\HomeworkRepository;
use App\Repositories\Academic\ClassSetupRepository;
use App\Repositories\Academic\SubjectAssignRepository;
use App\Repositories\OnlineExamination\QuestionGroupRepository;
use App\Http\Requests\Examination\Homework\HomeworkStoreRequest;
use App\Http\Requests\Examination\Homework\HomeworkUpdateRequest;
use App\Models\HomeworkStudent;
use App\Models\Homework;

class HomeworkController extends Controller
{
    private $repo;
    private $classRepo;
    private $classSetupRepo;
    private $sectionRepo;
    private $subjectRepo;
    private $assignSubjectRepo;
    private $quesgrouprepo;

    public function __construct(
        HomeworkRepository     $repo,
        ClassSetupRepository   $classSetupRepo,
        ClassesRepository      $classRepo,
        SectionRepository      $sectionRepo,
        SubjectRepository      $subjectRepo,
        SubjectAssignRepository $assignSubjectRepo,
        QuestionGroupRepository $quesgrouprepo
    ) {
        $this->repo              = $repo;
        $this->classRepo         = $classRepo;
        $this->classSetupRepo    = $classSetupRepo;
        $this->sectionRepo       = $sectionRepo;
        $this->subjectRepo       = $subjectRepo;
        $this->assignSubjectRepo = $assignSubjectRepo;
        $this->quesgrouprepo     = $quesgrouprepo;
    }

    // =========================================================================
    // HOMEWORK GROUPS (was: questionGroup)
    // -------------------------------------------------------------------------
    // Admin manages homework category groups here (e.g. "Weekly Homework",
    // "Science Project", "Reading Activity"). Teachers select a group when
    // creating homework. Groups are stored in question_groups with is_homework=1.
    // The create/edit/delete actions use the existing question-group routes
    // which are already correctly scoped to is_homework=1.
    // =========================================================================

    public function questionGroup()
    {
        $data['title']           = 'Homework Groups';
        $data['question_groups'] = $this->quesgrouprepo->getHomeworkGroup();

        return view('backend.homework.question_group', compact('data'));
    }

    // =========================================================================
    // HOMEWORK OVERVIEW (was: questionList)
    // -------------------------------------------------------------------------
    // Admin sees ALL homework across ALL teachers, classes and sessions.
    // Admin can delete any record. Provides a full audit view of the module.
    // =========================================================================

    public function questionList(Request $request)
    {
        $data['title']     = 'All Homework — Admin Overview';
        $data['homeworks'] = $this->repo->getAllForAdmin($request);
        $data['classes']   = $this->classSetupRepo->all();

        return view('backend.homework.homework_overview', compact('data'));
    }

    public function questionCreate()
    {
        return redirect()->route('homework.question-group')
            ->with('info', 'Questions are created automatically when you upload a Quiz CSV. To manage homework categories, use the Homework Groups page.');
    }

    public function index()
    {
        $data['title']     = ___('examination.homework');
        $data['classes']   = $this->classRepo->assignedForHomework();
        $data['homeworks'] = $this->repo->getPaginateAll();
        $data['stats']     = $this->repo->getStats();

        return view('backend.homework.index', compact('data'));
    }

    public function search(Request $request)
    {
        $data['title']     = ___('examination.homework');
        $data['classes']   = $this->classRepo->assignedForHomework();
        $data['homeworks'] = $this->repo->search($request);
        $data['stats']     = $this->repo->getStats();

        return view('backend.homework.index', compact('data'));
    }

    public function create()
    {
        $data['title']   = ___('examination.homework');
        $data['classes'] = $this->classRepo->assignedForHomework();

        return view('backend.homework.create', compact('data'));
    }

    public function store(HomeworkStoreRequest $request)
    {
        $result = $this->repo->store($request);

        if ($result['status']) {
            return redirect()->route('homework.index')->with('success', $result['message']);
        }

        return back()->with('danger', $result['message']);
    }

    public function edit($id, Request $request)
    {
        $data['homework'] = $this->repo->show($id);
        $data['classes']  = $this->classRepo->assignedForHomework();
        $classesId        = $data['homework']->classes_id;

        $user = auth()->user();
        if ($user && isHomeworkFilterAdmin()) {
            $data['sections'] = $this->classSetupRepo->getSections($classesId);
        } elseif ($user && $user->staff) {
            $data['sections'] = $this->classSetupRepo->getSectionsForTeacher($classesId, (int) $user->staff->id);
        } else {
            $data['sections'] = collect();
        }

        $data['title'] = ___('examination.homework');

        $request->merge([
            'classes_id' => $data['homework']->classes_id,
            'section_id' => $data['homework']->section_id,
        ]);

        $data['subjects'] = $this->assignSubjectRepo->getSubjects($request);

        return view('backend.homework.edit', compact('data'));
    }

    public function update(HomeworkUpdateRequest $request, $id)
    {
        $result = $this->repo->update($request, $id);

        if ($result['status']) {
            return redirect()->route('homework.index')->with('success', $result['message']);
        }

        return back()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->repo->destroy($id);

        if ($result['status']) {
            return response()->json([
                $result['message'],
                'success',
                ___('alert.deleted'),
                ___('alert.OK'),
            ]);
        }

        return response()->json([
            $result['message'],
            'error',
            ___('alert.oops'),
        ]);
    }

    // =========================================================================
    // EVALUATION
    // =========================================================================

    /**
     * Opens the evaluation modal — loads the student list for a homework item.
     */
    public function students(Request $request)
    {
        $data['homework'] = $this->repo->show($request->homework_id);

        $data['students'] = SessionClassStudent::with(['homeworkStudent' => function ($query) use ($data) {
            $query->where('homework_id', $data['homework']->id);
        }])
            ->where('session_id', setting('session'))
            ->where('classes_id', $data['homework']->classes_id)
            ->where('section_id', $data['homework']->section_id)
            ->get();

        $data['view'] = view('backend.homework.evaluation', compact('data'))->render();

        return response()->json($data);
    }

    /**
     * Saves manually assigned marks for standard homework submissions.
     */
    public function evaluationSubmit(Request $request)
    {
        $result = $this->repo->evaluationSubmit($request);

        if ($result['status']) {
            return redirect()->route('homework.index')->with('success', $result['message']);
        }

        return back()->with('danger', $result['message']);
    }

    /**
     * Saves marks from the answer-sheet grading view.
     * All business logic is handled inside the Repository.
     */
    public function markSubmit(Request $request)
    {
        $result = $this->repo->markSubmit($request);

        if ($result['status']) {
            return redirect()->route('homework.index')->with('success', $result['message']);
        }

        return back()->with('danger', $result['message']);
    }

    // =========================================================================
    // SUPPLEMENTARY VIEWS
    // =========================================================================

    public function viewAnswerSheet($id, $student_id)
    {
        $data['homework'] = $this->repo->show($id);
        $data['student']  = Student::find($student_id);

        return view('backend.homework.answerSheet', compact('data'));
    }

    public function viewQuestions(Request $request)
    {
        $homework  = $this->repo->show($request->id);

        // Quiz questions live in homework_quiz_questions — entirely separate from
        // the online-examination question_banks table. Use the dedicated modal blade.
        if ($homework && $homework->task_type === 'quiz') {
            $questions = DB::table('homework_quiz_questions')
                ->where('homework_id', $homework->id)
                ->orderBy('id')
                ->get();

            $skills = \App\Models\LearningEngine\Skill::active()
                ->where('classes_id', $homework->classes_id)
                ->where('subject_id', $homework->subject_id)
                ->orderBy('title')
                ->get();

            return view('backend.homework.quiz_questions_modal', compact('homework', 'questions', 'skills'));
        }

        // Standard homework: fall back to the existing online-exam view
        $data = $homework;
        return view('backend.online-examination.online-exam.view_questions', compact('data'));
    }

    /**
     * Serves the blank Quiz CSV template for teachers to download.
     * Place "Quiz_Template.csv" inside /public/ for this to work.
     */
    public function downloadSample()
    {
        $path = public_path('Quiz_Template.csv');

        return response()->download($path, 'Quiz_Template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    // =========================================================================
    // TIER 2 — QUIZ ANALYTICS
    // =========================================================================

    /**
     * Renders the per-question analytics page for a quiz homework.
     * Shows accuracy %, most-chosen wrong options, and skipped rates per question.
     * Only available for task_type = 'quiz'.
     */
    public function quizAnalytics($id)
    {
        $homework = $this->repo->show($id);

        if (!$homework || $homework->task_type !== 'quiz') {
            return redirect()->route('homework.index')
                ->with('danger', 'Analytics are only available for Quiz task types.');
        }

        $data['title']    = 'Quiz Analytics — ' . ($homework->title ?? 'Quiz');
        $data['analytics'] = $this->repo->getQuizAnalytics((int) $id);

        return view('backend.homework.quiz_analytics', compact('data'));
    }

    // =========================================================================
    // TIER 2 — CSV EXPORT
    // =========================================================================

    /**
     * Streams a CSV file of all student results for a homework.
     * Works for both quiz (auto-graded) and standard (teacher-graded).
     */
    public function exportResults($id)
    {
        // Delegates entirely to the Repository which builds the streamed CSV response
        return $this->repo->exportResultsCsv((int) $id);
    }

    // =========================================================================
    // FILTERED REPORTING (AJAX endpoints for dashboard refactor)
    // =========================================================================

    /**
     * AJAX endpoint: Returns the 4 global stat cards data.
     * Response: JSON with totals for all classes.
     */
    public function getGlobalStats()
    {
        $stats = $this->repo->getGlobalStats();
        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * AJAX endpoint: Returns filtered report data (chart + table).
     * Accepts filters: class, section, subject, task_type.
     * Response: JSON with donut_data, evaluation_status, trend_data, table_html.
     */
    public function getFilteredReport(Request $request)
    {
        $filters = [
            'class'     => $request->class,
            'section'   => $request->section,
            'subject'   => $request->subject,
            'task_type' => $request->task_type,
        ];

        $result = $this->repo->getFilteredHomeworkReport($filters);
        return response()->json($result);
    }

    /**
     * AJAX endpoint: Returns sections for a given class.
     * Used by the Class → Section dependent dropdown.
     * Query param: ?class_id=123
     */
    public function getSectionsByClass(Request $request)
    {
        $classId = $request->query('class_id');

        if (!$classId) {
            return response()->json(['error' => 'class_id is required'], 400);
        }

        $sections = $this->repo->getSectionsByClass((int) $classId);
        return response()->json(['success' => true, 'data' => $sections]);
    }

    /**
     * AJAX endpoint: Returns subjects for a given class/section combo.
     * Used by the Section → Subject dependent dropdown.
     * Query params: ?class_id=123&section_id=456
     */
    public function getSubjectsBySection(Request $request)
    {
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');

        if (!$classId || !$sectionId) {
            return response()->json(['error' => 'class_id and section_id are required'], 400);
        }

        $subjects = $this->repo->getSubjectsByClassSection((int) $classId, (int) $sectionId);
        return response()->json(['success' => true, 'data' => $subjects]);
    }

    // =========================================================================
    // EXTERNAL API  (authenticated via X-Brainova-Token header)
    // =========================================================================

    /**
     * Allows an external Brainova service to push a quiz into the LMS via JSON.
     * Token is validated against config/brainova.php (set BRAINOVA_API_TOKEN in .env).
     * Quiz questions are written to homework_quiz_questions — NOT to question_banks.
     */
    public function storeExternalQuiz(Request $request)
    {
        if ($request->header('X-Brainova-Token') !== config('brainova.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'class_id'                    => 'required',
            'title'                       => 'required|string|max:500',
            'questions'                   => 'required|array',
            'questions.*.question_text'   => 'required',
            'questions.*.correct_answer'  => 'required',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $homework                  = new Homework();
                $homework->classes_id      = $request->class_id;
                $homework->section_id      = $request->section_id;
                $homework->subject_id      = $request->subject_id;
                $homework->title           = $request->title;
                $homework->topic           = $request->topic ?? $request->title;
                $homework->task_type       = 'quiz';
                $homework->marks           = $request->total_marks;
                $homework->date            = now()->format('Y-m-d');
                $homework->submission_date = $request->due_date;
                $homework->status          = 1;
                $homework->session_id      = setting('session');
                $homework->save();

                foreach ($request->questions as $q) {
                    DB::table('homework_quiz_questions')->insert([
                        'homework_id'    => $homework->id,
                        'question'       => $q['question_text'],
                        'option_a'       => $q['option_a'] ?? null,
                        'option_b'       => $q['option_b'] ?? null,
                        'option_c'       => $q['option_c'] ?? null,
                        'option_d'       => $q['option_d'] ?? null,
                        'correct_answer' => $q['correct_answer'],
                        'hint'           => $q['hint'] ?? null,
                        'explanation'    => $q['explanation'] ?? null,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }

                return response()->json([
                    'status'      => 'success',
                    'message'     => 'Quiz imported successfully',
                    'homework_id' => $homework->id,
                ]);
            });

        } catch (\Exception $e) {
            \Log::error('External Quiz Import Failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Database error occurred.'], 500);
        }
    }

    /**
     * Returns real DB IDs for classes, sections, and subjects.
     * Used by external Brainova tools to resolve names to IDs before pushing quizzes.
     */
    public function getInventoryIds(Request $request)
    {
        if ($request->header('X-Brainova-Token') !== config('brainova.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'classes'  => DB::table('classes')->select('id', 'name')->where('status', 1)->get(),
            'sections' => DB::table('sections')->select('id', 'name')->where('status', 1)->get(),
            'subjects' => DB::table('subjects')->select('id', 'name')->where('status', 1)->get(),
        ]);
    }

    /** Tags one homework quiz question with a Skill (or clears it) — this is
     *  what lets a graded answer to that question feed the skill-mastery
     *  system the same way a tagged Online Exam question already does. */
    public function updateQuizQuestionSkill(Request $request)
    {
        $questionId = (int) $request->input('question_id');
        $skillId    = $request->input('skill_id');

        if ($questionId < 1) {
            return response()->json(['status' => 'error', 'message' => 'Invalid question.'], 422);
        }

        DB::table('homework_quiz_questions')
            ->where('id', $questionId)
            ->update(['skill_id' => $skillId ?: null]);

        return response()->json(['status' => 'success']);
    }
}
