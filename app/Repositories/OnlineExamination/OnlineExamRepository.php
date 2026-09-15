<?php

namespace App\Repositories\OnlineExamination;

use App\Enums\Settings;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use App\Models\OnlineExamination\Answer;
use App\Models\OnlineExamination\OnlineExam;
use App\Models\OnlineExamination\QuestionBank;
use App\Models\OnlineExamination\AnswerChildren;
use App\Interfaces\OnlineExamination\OnlineExamInterface;
use App\Models\OnlineExamination\OnlineExamChildrenStudents;
use App\Models\OnlineExamination\OnlineExamChildrenQuestions;
use App\Repositories\LearningEngine\LearningEventRepository;

class OnlineExamRepository implements OnlineExamInterface
{
    use ReturnFormatTrait;
    private $model;
    private $learningEvents;

    public function __construct(OnlineExam $model, LearningEventRepository $learningEvents)
    {
        $this->model          = $model;
        $this->learningEvents = $learningEvents;
    }

    public function all()
    {
        return $this->model->active()->get();
    }

    public function getAll()
    {
        return $this->model->latest()->paginate(Settings::PAGINATE);
    }

    public function search($request)
    {
        $result = $this->model;

        if($request->class != "") {
            $result = $result->where('classes_id', $request->class);
        }
        if($request->section != "") {
            $result = $result->where('section_id', $request->section);
        }
        if($request->subject != "") {
            $result = $result->where('subject_id', $request->subject);
        }
        if($request->keyword != "") {
            $result = $result ->where('name', 'LIKE', "%{$request->keyword}%");
            $result = $result ->orWhere('start', 'LIKE', "%{$request->keyword}%");
        }

        return $result->latest()->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        try {

            $row                    = new $this->model;
            $row->session_id        = setting('session');
            $row->classes_id        = $request->class;
            $row->section_id        = $request->section;
            if($request->subject != "")
                $row->subject_id    = $request->subject;
            $row->name              = $request->name;
            if($request->type != "")
                $row->exam_type_id  = $request->type;
            $row->total_mark        = $request->mark;
            $row->start             = $request->start;
            $row->end               = $request->end;
            $row->published         = $request->published;
            $row->question_group_id = $request->question_group;
            $row->save();

            foreach ($request->questions_ids as $key => $value) {
                OnlineExamChildrenQuestions::create([
                    'online_exam_id'   => $row->id,
                    'question_bank_id' => $value
                ]);
            }

            foreach ($request->student_ids as $key => $value) {
                OnlineExamChildrenStudents::create([
                    'online_exam_id' => $row->id,
                    'student_id'     => $value
                ]);
            }

            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function update($request, $id)
    {
        try {
            $row                    = $this->model->findOrfail($id);

            $row->classes_id        = $request->class;
            $row->section_id        = $request->section;
            if($request->subject != "")
                $row->subject_id    = $request->subject;
            $row->name              = $request->name;
            if($request->type != "")
                $row->exam_type_id  = $request->type;
            $row->total_mark        = $request->mark;
            $row->start             = $request->start;
            $row->end               = $request->end;
            $row->published         = $request->published;
            $row->question_group_id = $request->question_group;
            $row->save();

            OnlineExamChildrenQuestions::where('online_exam_id', $row->id)->delete();
            foreach ($request->questions_ids as $key => $value) {
                OnlineExamChildrenQuestions::create([
                    'online_exam_id'   => $row->id,
                    'question_bank_id' => $value
                ]);
            }

            OnlineExamChildrenStudents::where('online_exam_id', $row->id)->delete();
            foreach ($request->student_ids as $key => $value) {
                OnlineExamChildrenStudents::create([
                    'online_exam_id' => $row->id,
                    'student_id'     => $value
                ]);
            }

            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $row = $this->model->find($id);
            $row->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function getAllQuestions($id){
        return QuestionBank::active()->where('question_group_id', $id)->get();
    }

    public function answer($id, $student_id){
        return Answer::where('online_exam_id', $id)->where('student_id', $student_id)->first();
    }
    public function markSubmit($request){
        try {
            DB::transaction(function () use ($request) {
                $totalMark = 0;
                foreach ($request->answer_ids as $key => $value) {
                    $answerChild = AnswerChildren::find($value);
                    if ($answerChild) {
                        $answerChild->evaluation_mark = array_key_exists($key, $request->marks) ? (int) $request->marks[$key][0] : 0;
                        $answerChild->save();
                        $totalMark += $answerChild->evaluation_mark;

                        $this->logSkillEvent($answerChild, (int) $request->student_id);
                    }
                }

                $row         = Answer::where('online_exam_id', $request->online_exam_id)->where('student_id', $request->student_id)->first();
                $row->result = $totalMark;
                $row->save();
            });

            return $this->responseWithSuccess(___('alert.Evaluation successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** A question tagged with a skill (Website Setup → Skills) rolls its grading
     *  result into that skill's mastery for this student. Untagged questions are
     *  graded as normal but don't feed the skill-tracking system. */
    private function logSkillEvent(AnswerChildren $answerChild, int $studentId): void
    {
        $questionBank = QuestionBank::find($answerChild->question_bank_id);
        if (!$questionBank || !$questionBank->skill_id) {
            return;
        }

        $correct = $questionBank->mark > 0 && $answerChild->evaluation_mark >= $questionBank->mark;

        $this->learningEvents->record(
            $studentId,
            LearningEventRepository::EVENT_ANSWER_SUBMITTED,
            $questionBank->skill_id,
            ['correct' => $correct, 'source' => 'online_exam']
        );
    }
}
