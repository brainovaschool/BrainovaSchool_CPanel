<?php

namespace App\Repositories\StudentPanel\Homework;

use App\Models\Homework;
use App\Models\HomeworkStudent;
use App\Repositories\LearningEngine\LearningEventRepository;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeworkRepository implements HomeworkInterface
{
    use ReturnFormatTrait, CommonHelperTrait;

    private $model;
    private $learningEvents;

    public function __construct(Homework $model, LearningEventRepository $learningEvents)
    {
        $this->model          = $model;
        $this->learningEvents = $learningEvents;
    }

    /**
     * Returns all homework for every class the student is enrolled in
     * this session (their main class plus any short courses).
     * Uses get() (not paginate) so the portal can group by subject and
     * build charts (completion by subject, overall score trend) on the full set.
     */
    public function index()
    {
        $student = Auth::user()->student;
        $pairs   = $student ? $student->enrolledClassSectionPairs() : collect();

        $query = $this->model::with(['subject', 'upload'])
            ->active()
            ->where('session_id', setting('session'))
            ->orderByDesc('id');

        return matchAnyClassSectionPair($query, $pairs)->get();
    }

    public function show($id)
    {
        // Note: examQuestions loads from homework_questions → question_banks (online-exam).
        // Homework quiz questions live in homework_quiz_questions — handled separately.
        return $this->model::active()->find($id);
    }

    /**
     * Standard homework file submission (non-quiz task types).
     * Stores the uploaded file and records the homework as submitted.
     *
     * IMPORTANT: UploadImageUpdate MUST be called — it was previously commented
     * out, which meant files were never stored and the submission status never
     * persisted across page reloads.
     */
    public function submit($request)
    {
        DB::beginTransaction();
        try {
            $student = Auth::user()->student;
            $pairs   = $student ? $student->enrolledClassSectionPairs() : collect();

            if (!$student || $pairs->isEmpty()) {
                DB::rollBack();
                throw new \RuntimeException('Homework is not available.');
            }

            $homework = matchAnyClassSectionPair(
                $this->model::active()
                    ->where('id', $request->homework_id)
                    ->where('session_id', setting('session')),
                $pairs
            )->first();

            if (!$homework) {
                DB::rollBack();
                throw new \RuntimeException('Homework is not available.');
            }

            $homework_student = HomeworkStudent::where('student_id', $student->id)
                ->where('homework_id', $request->homework_id)
                ->first();

            if (!$homework_student) {
                $homework_student           = new HomeworkStudent();
                $homework_student->homework = null;
            }

            $homework_student->student_id  = $student->id;
            $homework_student->homework_id = $request->homework_id;
            $homework_student->date        = date('Y-m-d');

            // File upload — was previously commented out, causing "submitted" status
            // to never persist (the record saved but the file was never stored,
            // and the next page load showed "Not Submitted Yet" again).
            if ($request->hasFile('homework')) {
                $homework_student->homework = $this->UploadImageUpdate(
                    $request->homework,
                    'backend/uploads/homeworks',
                    $homework_student->homework
                );
            }

            $homework_student->save();
            DB::commit();

            // A file-upload submission can't be auto-graded against a skill,
            // so it never reaches LearningEventRepository::record() — stamp
            // today's activity directly instead, so it still counts as the
            // day's learning for pet/tree care and the island's learning-first gate.
            $this->learningEvents->markTodayActive($student->id);

            return $homework_student;

        } catch (\Throwable $th) {
            DB::rollBack();
            // Never dd() in production — it dumps sensitive data to the browser.
            \Log::error('Student Homework Submit Error: ' . $th->getMessage());
            throw $th;
        }
    }
}
