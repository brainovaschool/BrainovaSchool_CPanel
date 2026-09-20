<?php

namespace App\Models\StudentInfo;

use App\Models\BaseModel;
use App\Models\WebsiteSetup\Program;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per program a student is enrolled in — a student can hold
 *  several at once (e.g. a Homeschooling grade programme plus a Tutoring
 *  subject plus an Electives club). */
class StudentProgramEnrollment extends BaseModel
{
    protected $guarded = ['id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }
}
