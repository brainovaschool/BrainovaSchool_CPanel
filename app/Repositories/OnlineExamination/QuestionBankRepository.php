<?php

namespace App\Repositories\OnlineExamination;

use App\Enums\Settings;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use App\Models\OnlineExamination\QuestionBank;
use App\Models\OnlineExamination\QuestionGroup;
use App\Models\OnlineExamination\QuestionBankChildren;
use App\Interfaces\OnlineExamination\QuestionBankInterface;

class QuestionBankRepository implements QuestionBankInterface
{
    use ReturnFormatTrait;
    private $model;

    public function __construct(QuestionBank $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->active()->get();
    }

    public function getAll()
    {
        return $this->model->latest()->where('is_homework',0)->paginate(Settings::PAGINATE);
    }
    public function getHomeworkQues()
    {
        return $this->model->whereHas('group', function ($query) {
            $query->where('is_homework', 1);
        })->latest()->paginate(Settings::PAGINATE);
    }

    public function search($request)
    {
        $result = $this->model;

        if($request->keyword != "") {
            $result = $result->where('question', 'LIKE', "%{$request->keyword}%");
        }

        return $result->latest()->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row                    = new $this->model;

            $row->session_id        = setting('session');
            $row->question_group_id = $request->question_group;
            $row->skill_id          = $request->skill_id != "" ? $request->skill_id : null;
            $row->type              = $request->type;
            $row->question          = $request->question;
            $row->mark              = $request->mark;
            $row->is_homework       = @$request->is_homework ?? 0;

            if($request->type == 1)
            {
                $row->total_option = $request->total_option;
                $row->answer       = $request->single_choice_ans;
            }
            if($request->type == 2)
            {
                $row->total_option = $request->total_option;
                $row->answer       = $request->multiple_choice_ans;
            }
            if($request->type == 3)
                $row->answer  = $request->true_false_ans;

            $row->status      = $request->status;
            $row->save();

            if($request->type == 1 || $request->type == 2)
                foreach ($request->option as $key => $item) {
                    QuestionBankChildren::create([
                        'question_bank_id' => $row->id,
                        'option' => $item,
                    ]);
                }

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row                    = $this->model->findOrfail($id);
            $row->question_group_id = $request->question_group;
            $row->skill_id          = $request->skill_id != "" ? $request->skill_id : null;
            $row->type              = $request->type;
            $row->question          = $request->question;
            $row->mark              = $request->mark;

            if($request->type == 1)
                $row->answer  = $request->single_choice_ans;
            if($request->type == 2)
                $row->answer  = $request->multiple_choice_ans;
            if($request->type == 3)
                $row->answer  = $request->true_false_ans;

            $row->status      = $request->status;
            $row->save();

            if($request->type == 1 || $request->type == 2){
                QuestionBankChildren::where('question_bank_id', $row->id)->delete();
                foreach ($request->option as $key => $item) {
                    QuestionBankChildren::create([
                        'question_bank_id' => $row->id,
                        'option' => $item,
                    ]);
                }
            }

            DB::commit();
            return $row;
        } catch (\Throwable $th) {
            DB::rollBack();
           
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

    public function getQuestionGroup($request)
    {
        return QuestionGroup::active()->where('name', 'like', '%' . $request->text . '%')->pluck('name','id')->take(10)->toArray();
    }
}
