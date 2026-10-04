<?php

namespace App\Repositories\StudentPanel;

use App\Interfaces\StudentPanel\OnlineExaminationInterface;
use App\Models\OnlineExamination\Answer;
use App\Models\OnlineExamination\AnswerChildren;
use App\Models\OnlineExamination\OnlineExam;
use App\Models\OnlineExamination\OnlineExamChildrenStudents;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;
use App\Repositories\LearningEngine\LearningEventRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ReturnFormatTrait;

class OnlineExaminationRepository implements OnlineExaminationInterface
{
    use ReturnFormatTrait;

    private $learningEvents;

    public function __construct(LearningEventRepository $learningEvents)
    {
        $this->learningEvents = $learningEvents;
    }

    public function index(){
        $student        = Student::where('user_id', Auth::user()->id)->first();
        $pairs          = $student->enrolledClassSectionPairs();

        $now = Carbon::now(); // Get the current date and time using Carbon

        $data['exams'] = OnlineExamChildrenStudents::where('student_id', $student->id)
            ->whereHas('onlineExam', function ($query) use ($pairs, $now) {
                matchAnyClassSectionPair(
                    $query->where('session_id', setting('session'))
                        ->where('published', '<=', $now),
                    $pairs
                );
                    // ->where('end', '>=', $now);
            })
            ->get();
        $data['student'] = $student->id;
        return $data;

    }

    public function resultView($id){
        $student         = Student::where('user_id', Auth::user()->id)->first();
        $data['answer']  = Answer::where('online_exam_id', $id)->where('student_id', $student->id)->first();
        $data['exam']    = OnlineExam::where('id', $id)->first();
        return $data;
    }

    public function view($id){
        return OnlineExam::where('id', $id)->first();
    }

    public function answerSubmit($request){
        DB::beginTransaction();
        try {
            $student        = Student::where('user_id', Auth::user()->id)->first();

            $row                 = new Answer();
            $row->online_exam_id = $request->online_exam_id;
            $row->student_id     = $student->id;
            $row->save();

            foreach ($request->answer as $key => $value) {
                if($value != ""){
                    $child                   = new AnswerChildren();
                    $child->answer_id        = $row->id;
                    $child->question_bank_id = $key;
                    
                    if(is_string($value))
                        $child->answer           = $value;
                    else
                        $child->answer           = array_values($value);
    
                    $child->save();
                }
            }

            DB::commit();

            // Grading (and any skill-mastery credit) happens later when a
            // teacher marks it — but sitting the exam is itself the day's
            // learning activity, so it counts right away for pet/tree care
            // and the island's learning-first gate.
            $this->learningEvents->markTodayActive($student->id);

            return $this->responseWithSuccess(___('alert.Submitted successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }

    }
}
