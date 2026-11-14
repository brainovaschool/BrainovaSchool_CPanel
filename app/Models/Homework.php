<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Upload;
use App\Models\HomeworkStudent;
use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Models\Academic\Subject;
use App\Models\ClassContent\ClassContentLesson;
use App\Models\HomeworkQuestion;
use App\Models\Examination\ExamType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Homework extends BaseModel
{
    use HasFactory;

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'classes_id', 'id');
    }
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'document_id', 'id');
    }

    /** Optional - set from the Create/Edit Homework form's "Link to a class"
     *  dropdown, so the Course Details page can show this task under the
     *  right Class Content lesson. Null for every homework row created
     *  before this link existed, and for anyone who still leaves it blank. */
    public function lesson()
    {
        return $this->belongsTo(ClassContentLesson::class, 'class_content_lesson_id', 'id');
    }


    public function examQuestions(){

        return $this->hasMany(HomeworkQuestion::class, 'homework_id', 'id');
    }

    public function GetCheckSubmittedAttribute()
    {
        if (request()->student_id) {
            $studentId = request()->student_id;
        }else{
            $studentId = Auth::user()->student->id;
        }
        return HomeworkStudent::with('homeworkUpload')->where('student_id', $studentId)->where('homework_id', $this->id)->first();
    }
}
