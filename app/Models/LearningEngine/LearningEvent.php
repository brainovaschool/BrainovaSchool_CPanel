<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningEvent extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'id');
    }
}
